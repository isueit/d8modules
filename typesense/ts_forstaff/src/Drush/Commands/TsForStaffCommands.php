<?php

namespace Drupal\ts_forstaff\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class TsForStaffCommands extends DrushCommands {

  /**
   * Constructs a TsExtensionContentCommands object.
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
   * Rebuild this site's pages in the collection.
   */
  #[CLI\Command(name: 'ts_forstaff:rebuild', aliases: [])]
  #[CLI\Usage(name: 'ts_forstaff:rebuild', description: 'Rebuild this site\'s pages in the collection')]
  public function rebuild($options = []) {
    ts_forstaff_index_all_nodes();
    $log_message = dt('Rebuilt nodes in the For Staff collection for this site');
    $this->logger()->success($log_message);
  }

  /**
   * Delete this site's pages from the collection.
   */
  #[CLI\Command(name: 'ts_forstaff:delete', aliases: [])]
  #[CLI\Usage(name: 'ts_forstaff:delete', description: 'Delete this site\'s nodes from the collection')]
  public function delete($sitename='', $options = []) {
    ts_forstaff_delete_all_from_collection();
    $log_message = dt('Deleted nodes from the For Staff collection for this site');
    $this->logger()->success($log_message);
  }
}
