<?php
namespace Drupal\smartsheet_api\Commands;
use Drush\Commands\DrushCommands;

/**
 * Drush Commands
 * @package Drupal\smartsheet_api\Commands
 */
class SmartsheetApiCommands extends DrushCommands {

  /**
   * Sync content from Smartsheet
   * @command smartsheet_api:sync
   *
   * @aliases smartsheet-sync
   *
   * @options dry-run
   *  Show what would change, without saving anything
   *
   * @usage smartsheet_api:sync
   *  Create, update, and unpublish PTC Class Series from Smartsheet
   * @usage smartsheet-sync --dry-run
   *  Show what the sync would do
   */
  public function sync($options = ['dry-run' => FALSE]) {
    $summary = smartsheet_api_ptc_sync((bool) $options['dry-run']);
    if ($summary === NULL) {
      $this->logger()->error('PTC Class Series sync did not run, check the Recent log messages for smartsheet_api');
      return;
    }

    $prefix = $options['dry-run'] ? 'Dry run, would have: ' : '';
    $this->output()->writeln('PTC Class Series: ' . $prefix . $summary['created'] . ' created, ' . $summary['updated'] . ' updated, ' . $summary['unchanged'] . ' unchanged, ' . $summary['unpublished'] . ' unpublished');
  }
}
