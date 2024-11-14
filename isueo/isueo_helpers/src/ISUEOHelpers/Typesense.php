<?php

namespace Drupal\isueo_helpers\ISUEOHelpers;

use Drupal;
use Exception;
use Symfony\Component\HttpClient\HttplugClient;
use Typesense\Client;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;

class Typesense
{
  // Get the Client for our Typesense search server
  public static function getClient(string $api_key = '')
  {
    $config = \Drupal::config('isueo_helpers.settings');
    if ($config == null || empty($config->get('typesense.api_key'))) {
      Drupal::logger('isueo_helpers')->alert('Please enter a Typesense API Key');
    }

    $api_key = !empty($api_key) ? $api_key : $config->get('typesense.api_key');

    $number_of_blocks = $config->get('number_of_blocks');
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
    $client = self::getClient();
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
    return($client->collections[$collection]->getDocuments()->search($query_array));
    }
    } catch (Exception $e) {
      Drupal::logger('isueo_helpers')->info('Error in searchCollection: ' . $e->getMessage());
    }
    return (null);
  }

  //public static function index_node(int $nid, string $api_key, string $collection, string $site_name, string $base_url)
  public static function index_node(EntityInterface $node, string $api_key, string $collection, string $site_name, string $base_url)
  {
    try {
//      $node = Drupal::entityTypeManager()->getStorage('node')->load($nid);

      if ($node) {
        $client = Typesense::getClient($api_key);
        $render_array = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, 'default');
        $record = [
          'id' => $site_name . ':' . $node->id(),
          'title' => $node->getTitle(),
          'site_name' => $site_name,
          'url' => $base_url . \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id()),
          'changed' => date('c', $node->changed->value),
          'created' => date('c', $node->created->value),
          'content_type' => $node->bundle(),
          'summary' => empty($node->body->summary) ? '' : $node->body->summary,
          'rendered_content' => \Drupal::service('renderer')->renderPlain($render_array),
          'published' => $node->isPublished(),
        ];
        $client->collections[$collection]->documents->upsert($record);
      }
    } catch (Exception $e) {
      Drupal::logger('ts_extension_content')->error('Saving a node: ' . $e->getMessage());
    }
  }
}
