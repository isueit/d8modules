<?php
namespace Drupal\smartsheet_api\Commands;
use Drush\Commands\DrushCommands;

/**
 * Drush Commands
 * @package Drupal\smartsheet_api\Commands
 */
class SmartsheetApiCommands extends DrushCommands {

  /**
   * Run every enabled Smartsheet sync
   * @command smartsheet_api:sync
   *
   * @aliases smartsheet-sync
   *
   * @options dry-run
   *  Show what would change, without saving anything
   *
   * @usage smartsheet_api:sync
   *  Create, update, and unpublish content from Smartsheet
   * @usage smartsheet-sync --dry-run
   *  Show what the syncs would do
   */
  public function sync($options = ['dry-run' => FALSE]) {
    $results = smartsheet_api_sync((bool) $options['dry-run']);
    if (empty($results)) {
      $this->output()->writeln('No Smartsheet syncs are enabled. Enable a submodule, like PTC Class Series.');
      return;
    }

    $prefix = $options['dry-run'] ? 'Dry run, would have: ' : '';
    foreach ($results as $label => $summary) {
      if ($summary === NULL) {
        $this->logger()->error($label . ' sync did not run, check the Recent log messages');
      } else {
        $this->output()->writeln($label . ': ' . $prefix . smartsheet_api_summary_text($summary));
      }
    }
  }
}
