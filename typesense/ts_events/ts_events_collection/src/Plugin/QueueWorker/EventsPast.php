<?php

namespace Drupal\ts_events_collection\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\isueo_helpers\ISUEOHelpers\Typesense;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Typesense\Client;

/**
 * @QueueWorker(
 *   id = "ts_events_collection_events_programs",
 *   title = @Translation("Put past events into Typesense Collection"),
 *   cron = {"time" = 60}
 * )
 */
class EventsPast extends QueueWorkerBase implements ContainerFactoryPluginInterface
{
  /**
   * Lazily-built, reused across every item this worker instance processes.
   */
  protected ?Client $client = NULL;

  protected string $collectionName = 'events_programs';

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  protected function getClient(): Client
  {
    if ($this->client === NULL) {
      $this->client = Typesense::getClient($this->collectionName);
    }
    return $this->client;
  }

  public function processItem($data)
  {
    $client = $this->getClient();
    $results = $client->collections[$this->collectionName]->documents->import($data, ['action' => 'upsert']);
  }
}
