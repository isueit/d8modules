<?php

/**
 * @file
 * Hooks related to Layout Builder Base and its plugins.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the background options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the background.
 */
function hook_layout_builder_base_background_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the background attachment options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the background attachment.
 */
function hook_layout_builder_base_background_attachment_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the background position options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the background position.
 */
function hook_layout_builder_base_background_position_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the background size options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the background size.
 */
function hook_layout_builder_base_background_size_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the background overlay options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the background overlay.
 */
function hook_layout_builder_base_background_overlay_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the top margin options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the top margin.
 */
function hook_layout_builder_base_top_margin_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the bottom margin options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the bottom margin.
 */
function hook_layout_builder_base_bottom_margin_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the left margin options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the left margin.
 */
function hook_layout_builder_base_left_margin_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the right margin options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the right margin.
 */
function hook_layout_builder_base_right_margin_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the top bottom margins options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the top bottom margins.
 */
function hook_layout_builder_base_top_bottom_margins_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the left right margins options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the left right margins.
 */
function hook_layout_builder_base_left_right_margins_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the top padding options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the top padding.
 */
function hook_layout_builder_base_top_padding_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the bottom padding options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the bottom padding.
 */
function hook_layout_builder_base_bottom_padding_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the left padding options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the left padding.
 */
function hook_layout_builder_base_left_padding_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the right padding options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the right padding.
 */
function hook_layout_builder_base_right_padding_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the top bottom paddings options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the top bottom paddings.
 */
function hook_layout_builder_base_top_bottom_paddings_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the left right paddings options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the left right paddings.
 */
function hook_layout_builder_base_left_right_paddings_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the container options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the container.
 */
function hook_layout_builder_base_container_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the content container options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the content container.
 */
function hook_layout_builder_base_content_container_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the height options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the height.
 */
function hook_layout_builder_base_height_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the color options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the color.
 */
function hook_layout_builder_base_color_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the alignment options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the alignment.
 */
function hook_layout_builder_base_alignment_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the column gap options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseMultipleColumnsLayout.
 *
 * @param array $options
 *   The array of options for the column gap.
 */
function hook_layout_builder_base_column_gap_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the row gap options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseMultipleColumnsLayout.
 *
 * @param array $options
 *   The array of options for the row gap.
 */
function hook_layout_builder_base_row_gap_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the column breakpoint options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseMultipleColumnsLayout.
 *
 * @param array $options
 *   The array of options for the column breakpoint.
 */
function hook_layout_builder_base_column_breakpoint_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the align items options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseMultipleColumnsLayout.
 *
 * @param array $options
 *   The array of options for the align items.
 */
function hook_layout_builder_base_align_items_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the column width options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseTwoColumnsLayout.
 *
 * @param array $options
 *   The array of options for the column width.
 */
function hook_layout_builder_base_two_column_width_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the column width options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseThreeColumnsLayout.
 *
 * @param array $options
 *   The array of options for the column width.
 */
function hook_layout_builder_base_three_column_width_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the customizable columns options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseCustomizableColumnsLayout.
 *
 * @param array $options
 *   The array of options for the customizable columns options.
 */
function hook_layout_builder_base_customizable_columns_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * Alters the modifiers options provided in \Drupal\layout_builder_base_library\Plugin\Layout\BaseOneColumnLayout.
 *
 * @param array $options
 *   The array of options for the modifiers options.
 */
function hook_layout_builder_base_modifiers_alter(array &$options) {
  $options['option-class'] = 'Option';
}

/**
 * @} End of "addtogroup hooks".
 */
