<?php

namespace Drupal\isueo_helpers\ISUEOHelpers;

use Drupal;
use Exception;
use Symfony\Component\HttpClient\HttplugClient;
use Typesense\Client;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\isueo_helpers\ISUEOHelpers\TypesenseCollectionSchemas;

class Typesense
{
  // Get the Client for our Typesense search server
  public static function getClient(string $collection_name = '')
  {
    $config = \Drupal::config('isueo_helpers.settings');
    if (!$config) {
      Drupal::logger('isueo_helpers')->alert('Please enter a Typesense API Key');
      return null;
    }
    $api_key = '';

    switch ($collection_name) {
      case 'events':
        $api_key = 'lxNsXNmctmYuG3TQUpk6CpiPkF7dU8YI'; // Admin events
        break;
      case 'extension_content':
        $api_key = 'eS90dAFa47TIaOa1gm21fskmfTgwAUBE'; // Admin extension_content
        break;
      case 'ForStaff':
        $api_key = 'Zdlpn5NWOD2eCsCU6MCA9xLphzGlNn0A'; // Admin ForStaff
        break;
      case 'plp_programs':
        $api_key = 'KPwl7XwGfNLPjKdRtZSL1H0Rb1YeApcD'; // Admin plp_programs
        break;
      case 'products':
        break;
      case 'Admin':
        $api_key = 'O1tfLS2ZsKlYlDpLq16ZYaiB2m2doa9o'; // Admin
        break;
      case 'SearchAll':
        $api_key = 'bilLvsiWoO1EqcM21L8XrzofmVBYfyB9'; // search all
        break;
      case 'deleteme_brian':
        $api_key = 'FcwLwSWecQh91ElQtZjm0lGRv8cW2t1T'; // Admin deleteme_brian
        break;
      default:
        $api_key = $config->get('typesense.api_key');
        break;
    }

    $client = new Client(
      [
        'api_key' => $api_key,
        'nodes' => [
          [
            'host' => $config->get('typesense.host'),
            'port' => $config->get('typesense.port'),
            'protocol' => $config->get('typesense.protocol'),
          ],
        ],
        'client' => new HttplugClient(),
      ]
    );
    return $client;
  }

  public static function searchCollection(string $collection, string $q = '*', string $query_by = '*', string $sort_by = '', int $per_page = 10, int $page = 1, string $filter_by = '', bool $exhaustive_search = false)
  {
    try {
      $client = self::getClient('');
      if ($client) {
        $query_array = [
          'q' => $q,
          'query_by' => $query_by,
          'sort_by' => $sort_by,
          'per_page' => $per_page,
          'page' => $page,
          'filter_by' => $filter_by,
          'exhaustive_search' => $exhaustive_search,
        ];
        return ($client->collections[$collection]->getDocuments()->search($query_array));
      }
    } catch (Exception $e) {
      Drupal::logger('isueo_helpers')->info('Error in searchCollection: ' . $e->getMessage());
    }
    return (null);
  }

  public static function createCollection(string $collection)
  {
    $client = self::getClient($collection);
    $schema = TypesenseCollectionSchemas::getSchema($collection);
    try {
      $client->collections->create($schema);
    }
    catch (Exception $e) {
      Drupal::messenger()->addError($e->getMessage());
    }
  }

  public static function index_node(EntityInterface $node, string $collection, string $site_name, string $base_url)
  {
    try {
      //      $node = Drupal::entityTypeManager()->getStorage('node')->load($nid);

      if ($node) {
        $client = Typesense::getClient($collection);
        $render_array = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, 'default');
        $content = \Drupal::service('renderer')->renderInIsolation($render_array);
        $record = [
          'id' => $site_name . ':' . $node->id(),
          'title' => $node->getTitle(),
          'site_name' => $site_name,
          'url' => $base_url . \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id()),
          'changed' => date('c', $node->changed->value),
          'created' => date('c', $node->created->value),
          'content_type' => $node->bundle(),
          'summary' => empty($node->body->summary) ? '' : $node->body->summary,
          'rendered_content' => $content,
          'text_content' => static::clean_html_string(strip_tags($content)),
          'published' => $node->isPublished(),
        ];
        $client->collections[$collection]->documents->upsert($record);
      }
    } catch (Exception $e) {
      Drupal::logger('ts_extension_content')->error('Saving a node: ' . $e->getMessage());
    }
  }

  private static function clean_html_string(string $mystring)
  {
    if (!empty($mystring)) {
      $mystring = str_replace('&nbsp;', ' ', $mystring);
      $mystring = str_replace(PHP_EOL, '<br>', $mystring);
      $mystring = str_replace('EditDeleteManage', '', $mystring);
      $mystring = str_replace('Add to Favorites', '', $mystring);
      $mystring = str_replace('  display  ', '', $mystring);
      while (str_contains($mystring, '  ')) {
        $mystring = str_replace('  ', ' ', $mystring);
      }
      while (str_contains($mystring, '<br> ')) {
        $mystring = str_replace('<br> ', '<br>', $mystring);
      }
    }

    return $mystring;
  }
}
