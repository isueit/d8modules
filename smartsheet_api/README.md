# Smartsheet API

Reads sheets from Smartsheet. On its own it doesn't create any content; enable a
submodule to sync a sheet into Drupal.

## Submodules

- **PTC Class Series** (`ptc_class_series`) syncs the "PTC Caregiver Class Series
  (2021-present)" sheet into the PTC Class Series content type. See its README.

## Setup

Add the Smartsheet API token to `settings.php` (don't commit it):

```php
$settings['smartsheet_api_token'] = 'your-token';
```

Settings (token status, sync frequency, Sync now) are at
`/admin/config/smartsheet_api_settings`. Each submodule has its own settings page.

## Syncing

Syncs run on cron (hourly by default), from the settings page, or with Drush:

```
drush smartsheet-sync --dry-run
drush smartsheet-sync
```

## Adding another sheet

Make a submodule that implements `hook_smartsheet_api_sync()` (see
`smartsheet_api.api.php`). It can use these helpers:

- `smartsheet_api_get_rows($sheet_id, $required_columns)` returns the sheet's rows keyed
  by row ID, with each row's cells keyed by column title.
- `smartsheet_api_date()`, `smartsheet_api_url()` and `smartsheet_api_term_id()` clean up
  dates, links and taxonomy terms.
