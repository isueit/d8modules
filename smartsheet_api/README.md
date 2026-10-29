# Smartsheet API

Reads sheets from Smartsheet and keeps Drupal content in sync with them.

Currently it syncs one sheet: "PTC Caregiver Class Series (2021-present)" into the
PTC Class Series content type (`ptc_class_series`).

## Setup

Add the Smartsheet API token to `settings.php` (don't commit it):

```php
$settings['smartsheet_api_token'] = 'your-token';
```

Settings (sync frequency, sheet IDs, minimum rows) are at
`/admin/config/smartsheet_api_settings`.

## Syncing

Syncs run on cron (hourly by default), from the settings form, or with Drush:

```
drush smartsheet-sync --dry-run
drush smartsheet-sync
```

## Adding another sheet

`smartsheet_api_get_rows($sheet_id, $required_columns)` returns a sheet's rows keyed by
row ID, with each row's cells keyed by column title. Write a sync function like
`smartsheet_api_ptc_sync()` that maps those rows to content, then call it from
`smartsheet_api_cron()` and the Drush command.

## PTC Class Series

Each class series is matched to its Smartsheet row by row ID, which is stored in
`field_ptc_row_id`. Rows are created, updated only when their values change, and
unpublished when they no longer belong on the website. Nodes are never deleted.

A row is published when:

- "Add to PTC Website" is not "No, thanks" (blank counts as yes), and
- its End Date (or Start Date, if there's no End Date) is today or later, or
- for Cancelled or Postponed classes, its Start Date is today or later.

The "Past or Current Class Series" column isn't used. It's a `TODAY()` formula
that Smartsheet only recalculates when someone opens the sheet.

Smartsheet is the source of truth, so edits made to synced fields in Drupal are
overwritten on the next sync. If a column used by the sync is renamed in
Smartsheet, the sync stops and logs which columns are missing.
