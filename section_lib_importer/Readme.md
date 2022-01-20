# Section Library Importer
## Imports section library templates

## Note
This module is able to copy block library blocks that exist on both the site that was exported from and on the destination site. Inline blocks created in the layout builder only have their titles, the body is not copied to the new site.

## Adding new Templates
Navigate to the admin/config/content/layout_library_exporter page

Select which layout needs to be exported using the dropdown

Add layout thumbnail to the resources folder of this module

Set the filename for the template to the image intended to be used as its thumbnail by changing "INSERT FILENAME"

To the 'section_lib_importer.install' file add a new update function `section_lib_importer_update_N()` where N is the Drupal and module version, i.e. 8201 for Drupal 8.2, module version 0.1

Add exported array text into an array, multiple exported templates/sections can be added to this array, separated by commas, and pass it to the function `addSectionTemplates()`

New layouts will be added after the site is updated, ie. `drush updatedb`

### Example:
This update adds 'One Column Layout' and 'Two Column Layout' that are provided with installation of this module.

New layouts must use unique labels, duplicated labels will be ignored.

[section_lib_importer.install](section_lib_importer.install)
```
function section_lib_importer_update_8201() {
  $templates = [
  
  ["label" => "One Column Layout","type" => "section","filename" => "oneColumn.png","sections" => [["label" => "One Column Layout","type" => "section","section_id" => "layout_base_onecol","section" => ["label" => "1 Column Layout","column_widths" => null,"column_gap" => "","row_gap" => "","column_width" => "","column_breakpoint" => "","align_items" => "","background" => "layout--background--none","background_image" => "","background_image_style" => "","background_attachment" => "layout--background-attachment--default","background_position" => "layout--background-position--center","background_size" => "layout--background-size--cover","background_overlay" => "layout--background-overlay--none","equal_top_bottom_margins" => "","equal_left_right_margins" => "","top_margin" => "","right_margin" => "","bottom_margin" => "","left_margin" => "","equal_top_bottom_paddings" => "layout--top-bottom-padding--big","equal_left_right_paddings" => "layout--left-right-padding--small","top_padding" => "","right_padding" => "","bottom_padding" => "","left_padding" => "","container" => "layout--container--none","content_container" => "layout--content-container--default","height" => "layout--height--default","color" => "layout--color--default","alignment" => "","modifier" => "","customizable_columns" => "","modifiers" => ""],"blocks" => [["region" => "content","id" => "inline_block:basic_content","label" => "Placeholder Content","provider" => "layout_builder","label_display" => 0,"view_mode" => "full","info" => "Placeholder Content","body" => "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>"]]]]],
  
  ["label" => "Two Column Layout","type" => "section","filename" => "twoColumn.png","sections" => [["label" => "Two Column Layout","type" => "section","section_id" => "layout_base_twocol","section" => ["label" => "2 Column Layout","column_widths" => null,"column_gap" => "layout--column-gap--none","row_gap" => "layout--row-gap--none","column_width" => "layout--column-width--default","column_breakpoint" => "layout--column-breakpoint--medium","align_items" => "layout--align-items--normal","background" => "layout--background--none","background_image" => "","background_image_style" => "","background_attachment" => "layout--background-attachment--default","background_position" => "layout--background-position--center","background_size" => "layout--background-size--cover","background_overlay" => "layout--background-overlay--none","equal_top_bottom_margins" => "","equal_left_right_margins" => "","top_margin" => "","right_margin" => "","bottom_margin" => "","left_margin" => "","equal_top_bottom_paddings" => "layout--top-bottom-padding--big","equal_left_right_paddings" => "layout--left-right-padding--small","top_padding" => "","right_padding" => "","bottom_padding" => "","left_padding" => "","container" => "layout--container--none","content_container" => "layout--content-container--default","height" => "layout--height--default","color" => "layout--color--default","alignment" => "","modifier" => "","customizable_columns" => "","modifiers" => ""],"blocks" => [["region" => "first","id" => "inline_block:basic_content","label" => "What is Lorem Ipsum","provider" => "layout_builder","label_display" => "visible","view_mode" => "full","info" => "What is Lorem Ipsum","body" => "<p><strong>Lorem Ipsum</strong>&nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>"],["region" => "second","id" => "inline_block:basic_content","label" => "Where does it come from?","provider" => "layout_builder","label_display" => "visible","view_mode" => "full","info" => "Where does it come from?","body" => "<p>Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source. Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of \"de Finibus Bonorum et Malorum\" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance. The first line of Lorem Ipsum, \"Lorem ipsum dolor sit amet..\", comes from a line in section 1.10.32.</p>"]]]]],
  
  ];
  addSectionTemplates($templates);
}
```
