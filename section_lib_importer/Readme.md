# Section Library Importer
### Imports section library templates

## Note
This module is able to copy block library blocks that exist on both the site that was exported from and on the destination site. Inline blocks created in the layout builder only have their titles, the body is not copied to the new site.

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

As blocks do not yet load their body, commenting them out will prevent them from being added when setting up the site. This can be found in `$templates['sections'][#]['blocks']`

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
              'block_serialized' => 'O:40:"Drupal\\block_content\\Entity\\BlockContent":30:{s:8:"' . "\0" . '*' . "\0" . 'theme";N;s:9:"' . "\0" . '*' . "\0" . 'values";a:18:{s:2:"id";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";N;}}}s:11:"revision_id";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";N;}}}s:4:"type";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:9:"target_id";s:13:"basic_content";}}}s:4:"uuid";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:36:"248dd925-3cd2-49fb-a27e-f3a78562feda";}}}s:8:"langcode";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:2:"en";}}}s:13:"revision_user";a:1:{s:9:"x-default";a:0:{}}s:16:"revision_created";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:10:"1643314613";}}}s:12:"revision_log";a:1:{s:9:"x-default";a:0:{}}s:16:"revision_default";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"1";}}}s:17:"isDefaultRevision";a:1:{s:9:"x-default";s:1:"1";}s:6:"status";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"1";}}}s:4:"info";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:19:"Placeholder Content";}}}s:7:"changed";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:10:"1643314613";}}}s:16:"default_langcode";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"1";}}}s:29:"revision_translation_affected";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"1";}}}s:8:"reusable";a:1:{s:9:"x-default";a:1:{i:0;a:1:{s:5:"value";s:1:"0";}}}s:4:"body";a:1:{s:9:"x-default";a:1:{i:0;a:3:{s:5:"value";s:454:"<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
";s:7:"summary";s:0:"";s:6:"format";s:7:"wysiwyg";}}}s:7:"metatag";a:1:{s:9:"x-default";a:0:{}}}s:9:"' . "\0" . '*' . "\0" . 'fields";a:0:{}s:19:"' . "\0" . '*' . "\0" . 'fieldDefinitions";N;s:12:"' . "\0" . '*' . "\0" . 'languages";N;s:14:"' . "\0" . '*' . "\0" . 'langcodeKey";s:8:"langcode";s:21:"' . "\0" . '*' . "\0" . 'defaultLangcodeKey";s:16:"default_langcode";s:17:"' . "\0" . '*' . "\0" . 'activeLangcode";s:9:"x-default";s:18:"' . "\0" . '*' . "\0" . 'defaultLangcode";s:2:"en";s:15:"' . "\0" . '*' . "\0" . 'translations";a:1:{s:9:"x-default";a:1:{s:6:"status";i:1;}}s:24:"' . "\0" . '*' . "\0" . 'translationInitialize";b:0;s:14:"' . "\0" . '*' . "\0" . 'newRevision";b:0;s:20:"' . "\0" . '*' . "\0" . 'isDefaultRevision";s:1:"1";s:13:"' . "\0" . '*' . "\0" . 'entityKeys";a:2:{s:6:"bundle";s:13:"basic_content";s:8:"revision";N;}s:25:"' . "\0" . '*' . "\0" . 'translatableEntityKeys";a:5:{s:5:"label";a:1:{s:9:"x-default";s:19:"Placeholder Content";}s:8:"langcode";a:1:{s:9:"x-default";s:2:"en";}s:9:"published";a:1:{s:9:"x-default";s:1:"1";}s:16:"default_langcode";a:1:{s:9:"x-default";s:1:"1";}s:29:"revision_translation_affected";a:1:{s:9:"x-default";s:1:"1";}}s:12:"' . "\0" . '*' . "\0" . 'validated";b:0;s:21:"' . "\0" . '*' . "\0" . 'validationRequired";b:0;s:19:"' . "\0" . '*' . "\0" . 'loadedRevisionId";N;s:33:"' . "\0" . '*' . "\0" . 'revisionTranslationAffectedKey";s:29:"revision_translation_affected";s:37:"' . "\0" . '*' . "\0" . 'enforceRevisionTranslationAffected";a:0:{}s:15:"' . "\0" . '*' . "\0" . 'entityTypeId";s:13:"block_content";s:15:"' . "\0" . '*' . "\0" . 'enforceIsNew";b:1;s:12:"' . "\0" . '*' . "\0" . 'typedData";N;s:16:"' . "\0" . '*' . "\0" . 'cacheContexts";a:0:{}s:12:"' . "\0" . '*' . "\0" . 'cacheTags";a:0:{}s:14:"' . "\0" . '*' . "\0" . 'cacheMaxAge";i:-1;s:14:"' . "\0" . '*' . "\0" . '_serviceIds";a:0:{}s:18:"' . "\0" . '*' . "\0" . '_entityStorages";a:0:{}s:12:"' . "\0" . '*' . "\0" . 'isSyncing";b:0;s:19:"' . "\0" . '*' . "\0" . 'accessDependency";N;}',
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
  )
  ];
  addSectionTemplates($templates);
}
```
