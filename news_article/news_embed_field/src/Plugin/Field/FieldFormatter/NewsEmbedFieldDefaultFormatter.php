<?php

/**
 * @file
 * Contains \Drupal\news_embed_field\Plugin\field\formatter\SnippetsDefaultFormatter.
 */

namespace Drupal\news_embed_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use DOMDocument;


/**
 * Plugin implementation of the 'news_embed_field_default' formatter.
 *
 * @FieldFormatter(
 *   id = "news_embed_field_default",
 *   label = @Translation("News embed field default"),
 *   field_types = {
 *     "news_embed_field"
 *   }
 * )
 */
class NewsEmbedFieldDefaultFormatter extends FormatterBase {

  public static $canonicalURL = '';

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    // Do NOT cache a page with this block on it.
    \Drupal::service('page_cache_kill_switch')->trigger();
    $elements['#cache']['max-age'] = 0;
    $elements['#cache']['contexts'] = [];
    $elements['#cache']['tags'] = [];

    foreach ($items as $delta => $item) {
      // Render output
      if (filter_var($item->url, FILTER_VALIDATE_URL)) {
        $output = PHP_EOL;
        $embeddedPage = $this->parseEmbeddedPage($item->url);
        if ($embeddedPage['response_code'] == 200 && !empty($embeddedPage['article'])) {
          if (!empty($item->local_info)) {
            $output .= '<div class="local_info">' . $item->local_info . '</div>' . PHP_EOL;
          }
          $output .= '<div class="embedded_article">' . $embeddedPage['article'] . '</div>' . PHP_EOL;
          //$output .= '<div class="embedded_article">' . htmlentities($embeddedPage['article']) . '</div>' . PHP_EOL;
        }
        $tags = FieldFilteredMarkup::allowedTags();
        array_push($tags, 'iframe', 'div', 'h2', 'h3', 'h4', 'h5', 'h5', 'h6', 'footer', 'article', 'table', 'tbody', 'th', 'td', 'tr');
        while (preg_match('/<iframe[a-zA-Z0-9\" =\/\._\?\%]+\/>/', $output, $matches, PREG_OFFSET_CAPTURE)) {
          $output = substr_replace($output, "> </iframe>", strlen($matches[0][0])+$matches[0][1]-2, 11);
        }
        $elements[$delta] = array('#markup' => $output, '#allowed_tags' => $tags);
      }
    }
    return $elements;
  }

  private function parseEmbeddedPage($url) {
    $results = array();

    //open connection
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL, $url);

    //So that curl_exec returns the contents of the cURL; rather than echoing it
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

    //execute post
    $html = curl_exec($ch);

    $results['url'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $results['response_code'] = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $results['html'] = $html;
    $results['canonical'] = $this->getCanonicalURL($html, $results['url']);
    $results['article'] = $this->getArticle($html, $results['url']);

    self::$canonicalURL = $results['canonical'];

    return $results;
  }

  private function getCanonicalURL($html, $url) {
    if($html) {
      $dom = $this->load_html($html);
      if($dom) {
        $links = $dom->getElementsByTagName('link');
        foreach($links as $link) {
          $rels = [];
          if($link->hasAttribute('rel') && ($relAtt = $link->getAttribute('rel')) !== '') {
            $rels = preg_split('/\s+/', trim($relAtt));
          }
          if(in_array('canonical', $rels)) {
            $url = $link->getAttribute('href');
          }
        }
      }
    }
    return $url;
  }

  private function getArticle($html, $url) {
    $results = '';
    $parsedURL = parse_url($url);
    if($html) {
      $dom = $this->load_html($html);
      if($dom) {
        $articles = $dom->getElementsByTagName('article');
        if (count($articles)) {

          // Check if page already has a div with class of embedded_article
          $divs = $dom->getElementsByTagName('div');
          $hasEmbeddedArticle = FALSE;
          foreach($divs as $div) {
            $classes = $div->getAttribute('class');
            if ($classes == 'embedded_article') {
              $hasEmbeddedArticle = TRUE;
              break;
            }
          }

          // It already has an embedded article, grab the second one, otherwise grab the first
          if (count($articles) > 1 && $hasEmbeddedArticle) {
            $results = $articles[0]->ownerDocument->saveXML($articles[1]);
          } else {
            $results = $articles[0]->ownerDocument->saveXML($articles[0]);
          }

        }
      }
    }

    $results = str_replace('src="//', 'src="deleteme//', $results);
    $results = str_replace('href="//', 'href="deleteme//', $results);
    $results = str_replace('src="/', 'src="' . $parsedURL['scheme'] . '://' . $parsedURL['host'] . '/', $results);
    $results = str_replace('href="/', 'href="' . $parsedURL['scheme'] . '://' . $parsedURL['host'] . '/', $results);
    $results = str_replace('src="deleteme//', 'src="//', $results);
    $results = str_replace('href="deleteme//', 'href="//', $results);

    return $results;
  }

  private function load_html($html) {
    $dom = new DOMDocument;
    libxml_use_internal_errors(true); // suppress parse errors and warnings
    // Force interpreting this as UTF-8
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING|LIBXML_NOERROR);
    libxml_clear_errors();
    return $dom;
  }
}
