# PTC Class Series

Submodule of Smartsheet API. Creates the PTC Class Series content type and keeps it in
sync with the "PTC Caregiver Class Series (2021-present)" Smartsheet.

The sheet ID and minimum row count are in the PTC Class Series section of
`/admin/config/smartsheet_api_settings`.

Each class series is matched to its Smartsheet row by row ID, which is stored in
`field_ptc_row_id`. Rows are created, updated only when their values change, and
unpublished when they no longer belong on the website.

A row is published when:

- "Add to PTC Website" is not "No, thanks" (blank counts as yes), and
- its End Date (or Start Date, if there's no End Date) is today or later, or
- for Cancelled or Postponed classes, its Start Date is today or later.

Each class is one of three workshops (`field_ptc_workshop`): Caregivers of Adults,
Caregivers of Children, or Managing Stress Workshop. It comes from the Length column,
or from Audience when Length is blank, since a Managing Stress Workshop can list either
audience. Each workshop's description is edited on the settings page and shown on every
class page for that workshop. The sheet's per-class "Class Description" column goes in
the Other Information field.

The "Past or Current Class Series" column isn't used. It's a `TODAY()` formula
that Smartsheet only recalculates when someone opens the sheet.

Smartsheet is the source of truth, so edits made to synced fields in Drupal are
overwritten on the next sync. If a column used by the sync is renamed in
Smartsheet, the sync stops and logs which columns are missing.

Uninstalling this module deletes all PTC Class Series content, along with its content
type, fields and vocabularies. The content can be recreated from Smartsheet.
