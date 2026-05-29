<?php

namespace Drupal\news_from_feed\Plugin\Block;

use DateInterval;
use DateTime;
use Drupal;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;


use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\isueo_helpers\ISUEOHelpers;

/**
 * Provides a 'NewsFromFeed' block plugin.
 *
 * @Block(
 *   id = "news_from_feed",
 *   admin_label = @Translation("News From Feed"),
 * )
 */


class NewsFromFeed extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    // Do NOT cache a page with this block on it
    \Drupal::service('page_cache_kill_switch')->trigger();

    if (is_null($this->configuration['max_articles']) || empty($this->configuration['max_articles'])) {
      $max_articles = PHP_INT_MAX;
    } else {
      $max_articles = intval($this->configuration['max_articles']);
    }

    $program_area = $this->configuration['program_area'];
    if ($program_area == 'All') {
      $program_area = '';
    }

    $categories = $this->get_categories();
    $obj = $this->news_from_feed_parse_json();
    $articles = [];
    $count = 0;

    foreach ($obj as $node) {
      /*
       * categories is commented out because the new news feed uses program area, but doesn't includ categories.
       * However, it's left here in case we want to use catagories again.
       *
      */
      //$node_categories = empty($node->node->Category) ? [] : explode(', ', $node->node->Category);
      //if (count($categories) > 0 && count($node_categories) > 0 && count(array_intersect($categories, $node_categories)) == 0) {
      //  continue;
      //}

      if (!empty($program_area) && $node->field_program_area != $program_area) {
        continue;
      }
      $count++;
      if ($count > $max_articles) {
        break;
      }

      $base_url = 'https://www.extension.iastate.edu';
      $article = [];
      $article['thumbnail_url'] = $this->make_absolute($node->thumbnail__title, $base_url);
      $article['thumbnail_alt'] = $node->thumbnail__alt;
      $article['title'] = $node->title;
      $article['teaser'] = $node->field_teaser;
      $article['url'] = $this->make_absolute($node->view_node, $base_url);
      $article['created'] = !empty($node->created) ? date('F j, Y', $node->created) : '';
      $articles[] = $article;
    }

    return [
      '#attached' => [
        'library' => [
          'news_from_feed/news_from_feed',
        ],
      ],
      '#articles' => $articles,
      '#header' => $this->configuration['header'],
      '#all_news_url' => $this->configuration['all_news_url'] ?? 'https://www.extension.iastate.edu/news',
      '#theme' => 'news_from_feed',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {
    $form['header'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Header'),
      '#description' => $this->t('Text to be displayed at the top of the block, before the articles'),
      '#default_value' => $this->configuration['header'],
    ];
    $form['max_articles'] = [
      '#type' => 'number',
      '#title' => $this->t('Articles to display'),
      '#description' => $this->t('Maximum number of articles, blank or 0 means display them all'),
      '#default_value' => is_null($this->configuration['max_articles']) ? 5 : $this->configuration['max_articles'],
    ];
    $form['categories'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Categories'),
      '#description' => $this->t('Categories to include, separate with commas, blank means display them all<br/>Must match exactly what comes from the news feed<br/>Example: Crops, Livestock, Environment'),
      '#default_value' => $this->configuration['categories'],
      '#size' => 256,
      '#maxlength' => 256,
    ];
    $form['program_area'] = [
      '#type' => 'select',
      '#title' => $this->t('Program Area'),
      '#options' => [
        'All' => $this->t('All'),
        '4-H Youth Development' => $this->t('4-H Youth Development'),
        'Administration' => $this->t('Administration'),
        'Agriculture and Natural Resources' => $this->t('Agriculture and Natural Resources'),
        'Community and Economic Development' => $this->t('Community and Economic Development'),
        'County Services' => $this->t('County Services'),
        'Health and Human Sciences' => $this->t('Health and Human Sciences'),
      ],
      '#default_value' => empty($this->configuration['program_area']) ? 'All' : $this->configuration['program_area'],
    ];
    $form['all_news_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('All News URL'),
      '#description' => $this->t('URL for the "All News" footer link. Defaults to https://www.extension.iastate.edu/news'),
      '#default_value' => $this->configuration['all_news_url'] ?? 'https://www.extension.iastate.edu/news',
      '#size' => 256,
      '#maxlength' => 256,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    $values = $form_state->getValues();
    $this->configuration['max_articles'] = $values['max_articles'];
    $this->configuration['header'] = $values['header'];
    $this->configuration['categories'] = $values['categories'];
    $this->configuration['program_area'] = $values['program_area'];
    $this->configuration['all_news_url'] = $values['all_news_url'];
  }


  /**
   * @return int
   */
  public function getCacheMaxAge()
  {
    return 0;
  }


  /**
   * Parse the json file
   */
  function news_from_feed_parse_json()
  {
    static $parsed_json;
    if (!isset($parsed_json)) {
      $json = ISUEOHelpers\Files::fetch_url('https://www.extension.iastate.edu/news/json-feed');
      $parsed_json = json_decode($json, false);
    }
    return $parsed_json;
  }

  function make_absolute($url, $base_url)
  {
    if (empty($url)) {
      return '';
    }
    return str_starts_with($url, 'http') ? $url : $base_url . $url;
  }

  function get_categories()
  {
    $categories = [];
    if (empty(trim($this->configuration['categories']))) {
      return $categories;
    }

    foreach (explode(',', trim($this->configuration['categories'])) as $category) {
      $category = trim($category);
      if (!empty($category)) {
        $categories[] = $category;
      }
    }

    return $categories;
  }
}
