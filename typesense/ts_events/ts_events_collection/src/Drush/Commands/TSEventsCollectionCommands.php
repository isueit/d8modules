<?php

namespace Drupal\ts_events_collection\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class TSEventsCollectionCommands extends DrushCommands {

  /**
   * Constructs TSEventsCollectionCommands object.
   */
  public function __construct(
    private readonly Token $token,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token'),
    );
  }

  /**
   * Reindex the events into the collection
   */
  #[CLI\Command(name: 'ts_events_collection:index_events', aliases: [])]
  #[CLI\Usage(name: 'ts_events_collection:index_events', description: 'Reindex the events into the collection')]
  public function index_events($options = []) {
    ts_events_collection_index_events();
    $log_message = dt('Reindexed the events');
    $this->logger()->success($log_message);
  }

  /**
   * Delete the events collection.
   */
  #[CLI\Command(name: 'ts_events_collection:delete_collection', aliases: [])]
  #[CLI\Usage(name: 'ts_events_collection:delete_collection', description: 'Delete the events collection')]
  public function delete_collection($sitename='', $options = []) {
    ts_events_collection_delete_collection();
    $log_message = dt('Deleted the events collection');
    $this->logger()->success($log_message);
  }

  /**
   * Create the events collection.
   */
  #[CLI\Command(name: 'ts_events_collection:create_collection', aliases: [])]
  #[CLI\Usage(name: 'ts_events_collection:create_collection', description: 'Create and populate the events collection')]
  public function create_collection($sitename='', $options = []) {
    ts_events_collection_create_collection();
    $log_message = dt('Created and populated the events collection');
    $this->logger()->success($log_message);
  }
}
