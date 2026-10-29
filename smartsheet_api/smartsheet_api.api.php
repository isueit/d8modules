<?php

/**
 * @file
 * Hooks provided by the Smartsheet API module.
 */

/**
 * Sync content from Smartsheet
 *
 * Called on cron (at the interval set on the Smartsheet API settings page), from the
 * settings page's Sync now option, and by drush smartsheet-sync.
 *
 * @param bool $dry_run
 *   Count what would change, without saving anything
 *
 * @return array
 *   Keyed by a label for the sync. Each value is an array of counts (created, updated,
 *   unchanged, unpublished), or null if the sync couldn't run.
 */
function hook_smartsheet_api_sync(bool $dry_run)
{
  return ['My Sheet' => mymodule_sync($dry_run)];
}
