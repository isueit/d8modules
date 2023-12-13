# Section Library Importer
### Imports section library templates

## Adding new Templates
Navigate to the admin/config/content/layout_library_exporter page

Select which layout needs to be exported using the dropdown

Add layout thumbnail to the resources folder of this module

Set the filename for the template to the image intended to be used as its thumbnail by changing "INSERT FILENAME"

To the 'section_lib_importer.install' file add a new update function `section_lib_importer_update_N()` where N is the Drupal and module version, i.e. 8201 for Drupal 8.2, module version 0.1

Add exported array text into an array, multiple exported templates/sections can be added to this array, separated by commas, and pass it to the function `addSectionTemplates()`

New layouts will be added after the site is updated, i.e. `drush updatedb`, new layout will also be added when the module is reinstalled with the new layout in the HOOK_install() function.

### Example:
This update adds 'One Column Layout' and 'Two Column Layout' that are provided with installation of this module.

New layouts must use unique labels, duplicated labels will be ignored.

[section_lib_importer.install](section_lib_importer.install)
```
function section_lib_importer_update_8201() {
  $templates = [
  
  array (
    'label' => 'One Column Layout',
    'type' => 'section',
    'filename' => 'oneColumn.png',
    'sections' => 
    array (
      0 => 
      array (
        'label' => 'One Column Layout',
        'type' => 'section',
        'section_id' => 'layout_base_onecol',
        'section' => 
      array (
        'label' => '1 Column Layout',
        'column_widths' => NULL,
        'column_gap' => '',
        'row_gap' => '',
      'column_width' => '',
        'column_breakpoint' => '',
        'align_items' => '',
        'background' => 'layout--background--none',
        'background_image' => '',
        'background_image_style' => '',
        'background_attachment' => 'layout--background-attachment--default',
        'background_position' => 'layout--background-position--center',
        'background_size' => 'layout--background-size--cover',
        'background_overlay' => 'layout--background-overlay--none',
        'equal_top_bottom_margins' => '',
        'equal_left_right_margins' => '',
        'top_margin' => '',
        'right_margin' => '',
        'bottom_margin' => '',
        'left_margin' => '',
        'equal_top_bottom_paddings' => 'layout--top-bottom-padding--big',
        'equal_left_right_paddings' => 'layout--left-right-padding--small',
        'top_padding' => '',
        'right_padding' => '',
        'bottom_padding' => '',
        'left_padding' => '',
        'container' => 'layout--container--none',
        'content_container' => 'layout--content-container--default',
        'height' => 'layout--height--default',
        'color' => 'layout--color--default',
        'alignment' => '',
        'modifier' => '',
        'customizable_columns' => '',
        'modifiers' => '',
      ),
      'blocks' => 
      array (
        0 => 
        array (
          'uuid' => 'c84dfabd-2059-42b0-9f33-0f1b174b2dd8',
          'region' => 'content',
          'configuration' => 
          array (
            'id' => 'inline_block:basic_content',
            'label' => 'Placeholder Content',
            'label_display' => 'visible',
            'provider' => 'layout_builder',
            'view_mode' => 'full',
            'block_revision_id' => NULL,
            'block_serialized' => 'Tzo0MDoiRHJ1cGFsXGJsb2NrX2NvbnRlbnRcRW50aXR5XEJsb2NrQ29udGVudCI6MzA6e3M6ODoiACoAdGhlbWUiO047czo5OiIAKgB2YWx1ZXMiO2E6MTc6e3M6ODoibGFuZ2NvZGUiO2E6MTp7czo5OiJ4LWRlZmF1bHQiO2E6MTp7aTowO2E6MTp7czo1OiJ2YWx1ZSI7czoyOiJlbiI7fX19czoxMToicmV2aXNpb25faWQiO2E6MTp7czo5OiJ4LWRlZmF1bHQiO2E6MTp7aTowO2E6MTp7czo1OiJ2YWx1ZSI7Tjt9fX1zOjI6ImlkIjthOjE6e3M6OToieC1kZWZhdWx0IjthOjE6e2k6MDthOjE6e3M6NToidmFsdWUiO047fX19czo0OiJ1dWlkIjthOjE6e3M6OToieC1kZWZhdWx0IjthOjE6e2k6MDthOjE6e3M6NToidmFsdWUiO3M6MzY6ImNjM2U3NjE4LTBjOTAtNGY0OC04ZmQzLWFmMGFiN2U3ZjY1MyI7fX19czo0OiJ0eXBlIjthOjE6e3M6OToieC1kZWZhdWx0IjthOjE6e2k6MDthOjE6e3M6OToidGFyZ2V0X2lkIjtzOjEzOiJiYXNpY19jb250ZW50Ijt9fX1zOjE2OiJyZXZpc2lvbl9jcmVhdGVkIjthOjE6e3M6OToieC1kZWZhdWx0IjthOjE6e2k6MDthOjE6e3M6NToidmFsdWUiO2k6MTY0MzMxODEyOTt9fX1zOjEzOiJyZXZpc2lvbl91c2VyIjthOjE6e3M6OToieC1kZWZhdWx0IjthOjA6e319czoxMjoicmV2aXNpb25fbG9nIjthOjE6e3M6OToieC1kZWZhdWx0IjthOjA6e319czo2OiJzdGF0dXMiO2E6MTp7czo5OiJ4LWRlZmF1bHQiO2E6MTp7aTowO2E6MTp7czo1OiJ2YWx1ZSI7YjoxO319fXM6NDoiaW5mbyI7YToxOntzOjk6IngtZGVmYXVsdCI7YToxOntpOjA7YToxOntzOjU6InZhbHVlIjtzOjE5OiJQbGFjZWhvbGRlciBDb250ZW50Ijt9fX1zOjc6ImNoYW5nZWQiO2E6MTp7czo5OiJ4LWRlZmF1bHQiO2E6MTp7aTowO2E6MTp7czo1OiJ2YWx1ZSI7aToxNjQzMzE4MTI5O319fXM6ODoicmV1c2FibGUiO2E6MTp7czo5OiJ4LWRlZmF1bHQiO2E6MTp7aTowO2E6MTp7czo1OiJ2YWx1ZSI7YjowO319fXM6MTY6ImRlZmF1bHRfbGFuZ2NvZGUiO2E6MTp7czo5OiJ4LWRlZmF1bHQiO2E6MTp7aTowO2E6MTp7czo1OiJ2YWx1ZSI7YjoxO319fXM6MTY6InJldmlzaW9uX2RlZmF1bHQiO2E6MTp7czo5OiJ4LWRlZmF1bHQiO2E6MDp7fX1zOjI5OiJyZXZpc2lvbl90cmFuc2xhdGlvbl9hZmZlY3RlZCI7YToxOntzOjk6IngtZGVmYXVsdCI7YTowOnt9fXM6NzoibWV0YXRhZyI7YToxOntzOjk6IngtZGVmYXVsdCI7YTowOnt9fXM6NDoiYm9keSI7YToxOntzOjk6IngtZGVmYXVsdCI7YToxOntpOjA7YTozOntzOjc6InN1bW1hcnkiO3M6MDoiIjtzOjU6InZhbHVlIjtzOjQ1NDoiPHA+TG9yZW0gaXBzdW0gZG9sb3Igc2l0IGFtZXQsIGNvbnNlY3RldHVyIGFkaXBpc2NpbmcgZWxpdCwgc2VkIGRvIGVpdXNtb2QgdGVtcG9yIGluY2lkaWR1bnQgdXQgbGFib3JlIGV0IGRvbG9yZSBtYWduYSBhbGlxdWEuIFV0IGVuaW0gYWQgbWluaW0gdmVuaWFtLCBxdWlzIG5vc3RydWQgZXhlcmNpdGF0aW9uIHVsbGFtY28gbGFib3JpcyBuaXNpIHV0IGFsaXF1aXAgZXggZWEgY29tbW9kbyBjb25zZXF1YXQuIER1aXMgYXV0ZSBpcnVyZSBkb2xvciBpbiByZXByZWhlbmRlcml0IGluIHZvbHVwdGF0ZSB2ZWxpdCBlc3NlIGNpbGx1bSBkb2xvcmUgZXUgZnVnaWF0IG51bGxhIHBhcmlhdHVyLiBFeGNlcHRldXIgc2ludCBvY2NhZWNhdCBjdXBpZGF0YXQgbm9uIHByb2lkZW50LCBzdW50IGluIGN1bHBhIHF1aSBvZmZpY2lhIGRlc2VydW50IG1vbGxpdCBhbmltIGlkIGVzdCBsYWJvcnVtLjwvcD4NCiI7czo2OiJmb3JtYXQiO3M6Nzoid3lzaXd5ZyI7fX19fXM6OToiACoAZmllbGRzIjthOjA6e31zOjE5OiIAKgBmaWVsZERlZmluaXRpb25zIjtOO3M6MTI6IgAqAGxhbmd1YWdlcyI7TjtzOjE0OiIAKgBsYW5nY29kZUtleSI7czo4OiJsYW5nY29kZSI7czoyMToiACoAZGVmYXVsdExhbmdjb2RlS2V5IjtzOjE2OiJkZWZhdWx0X2xhbmdjb2RlIjtzOjE3OiIAKgBhY3RpdmVMYW5nY29kZSI7czo5OiJ4LWRlZmF1bHQiO3M6MTg6IgAqAGRlZmF1bHRMYW5nY29kZSI7czoyOiJlbiI7czoxNToiACoAdHJhbnNsYXRpb25zIjthOjE6e3M6OToieC1kZWZhdWx0IjthOjE6e3M6Njoic3RhdHVzIjtpOjI7fX1zOjI0OiIAKgB0cmFuc2xhdGlvbkluaXRpYWxpemUiO2I6MDtzOjE0OiIAKgBuZXdSZXZpc2lvbiI7YjoxO3M6MjA6IgAqAGlzRGVmYXVsdFJldmlzaW9uIjtiOjE7czoxMzoiACoAZW50aXR5S2V5cyI7YToyOntzOjY6ImJ1bmRsZSI7czoxMzoiYmFzaWNfY29udGVudCI7czo4OiJyZXZpc2lvbiI7Tjt9czoyNToiACoAdHJhbnNsYXRhYmxlRW50aXR5S2V5cyI7YToxOntzOjg6Imxhbmdjb2RlIjthOjE6e3M6OToieC1kZWZhdWx0IjtzOjI6ImVuIjt9fXM6MTI6IgAqAHZhbGlkYXRlZCI7YjoxO3M6MjE6IgAqAHZhbGlkYXRpb25SZXF1aXJlZCI7YjowO3M6MTk6IgAqAGxvYWRlZFJldmlzaW9uSWQiO047czozMzoiACoAcmV2aXNpb25UcmFuc2xhdGlvbkFmZmVjdGVkS2V5IjtzOjI5OiJyZXZpc2lvbl90cmFuc2xhdGlvbl9hZmZlY3RlZCI7czozNzoiACoAZW5mb3JjZVJldmlzaW9uVHJhbnNsYXRpb25BZmZlY3RlZCI7YTowOnt9czoxNToiACoAZW50aXR5VHlwZUlkIjtzOjEzOiJibG9ja19jb250ZW50IjtzOjE1OiIAKgBlbmZvcmNlSXNOZXciO2I6MTtzOjEyOiIAKgB0eXBlZERhdGEiO047czoxNjoiACoAY2FjaGVDb250ZXh0cyI7YTowOnt9czoxMjoiACoAY2FjaGVUYWdzIjthOjA6e31zOjE0OiIAKgBjYWNoZU1heEFnZSI7aTotMTtzOjE0OiIAKgBfc2VydmljZUlkcyI7YTowOnt9czoxODoiACoAX2VudGl0eVN0b3JhZ2VzIjthOjA6e31zOjEyOiIAKgBpc1N5bmNpbmciO2I6MDtzOjE5OiIAKgBhY2Nlc3NEZXBlbmRlbmN5IjtOO30=',
            'context_mapping' => 
            array (
            ),
            'block_uuid' => NULL,
          ),
          'weight' => 1,
          'additional' => 
          array (
          ),
        ),
      ),
    ),
  ),
),
  ];
  addSectionTemplates($templates);
}
```
