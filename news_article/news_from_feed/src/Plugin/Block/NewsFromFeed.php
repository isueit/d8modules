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

    $obj = $this->news_from_feed_parse_json();

    //if (count($obj) == 0) {
    //  return;
    //}

    $results = PHP_EOL . '<div id="news_from_feed">' . PHP_EOL;
    $results .= '  <p class="header" style="text-align:center;">Learn how ISU Extension and Outreach engages Iowans in solving today\'s problems<br>and preparing for a thriving future.</p>' . PHP_EOL;
    $results .= '  <div class="item-list">' . PHP_EOL;
    $results .= '    <ul class="list-unstyled row">' . PHP_EOL;
    $count = 0;
    foreach ($obj->nodes as $node) {
      $count++;
      if ($count > 3) {
        break;
      }

      $results .= '      <li class="col-md-6 col-lg-4 mb-3">' . PHP_EOL;
      $results .= '        <div class="card">' . PHP_EOL;
      $results .= '          <a href="https://www.extension.iastate.edu' . $node->node->Path . '">' . PHP_EOL;
      $results .= '            <img src="' . $node->node->ThumbnailImage->src . '" alt="' . $node->node->ThumbnailImage->alt . '" loading="lazy" />' . PHP_EOL;
      $results .= '            <div class="card-body">' . PHP_EOL;
      $results .= '              <h3 class="card-title">' . $node->node->title . '</h3>' . PHP_EOL;
      $results .= '              <div><p>' . $node->node->Body . '</p></div>' . PHP_EOL;
      $results .= '            </div>' . PHP_EOL;
      $results .= '          </a>' . PHP_EOL;
      $results .= '          <div class="card-footer">' . PHP_EOL;
      $results .= '            <a href="https://www.extension.iastate.edu' . $node->node->Path . '" class="btn btn-outline-danger" aria-label="' . $node->node->title . '">Read More</a>' . PHP_EOL;
      $results .= '          </div>' . PHP_EOL;
      $results .= '        </div>' . PHP_EOL;
      $results .= '      </li>' . PHP_EOL;

    }
    $results .= '    </ul>' . PHP_EOL;
    $results .= '  </div>' . PHP_EOL;
    $results .= '</div>' . PHP_EOL;

    return [
      '#markup' => $this->t($results),
    ];
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
      $json = file_get_contents('https://www.extension.iastate.edu/news/json-feed');
      $parsed_json = json_decode($json, false);
    }
    return $parsed_json;
  }

}
