<?php

namespace Drupal\layout_builder_base_library\Plugin\Layout;

/**
 * Configurable layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class BaseMultipleColumnsLayout extends BaseOneColumnLayout {

  /**
   * {@inheritdoc}
   */
  protected function isMultipleColumnsLayout() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getColumnGapOptions() {
    $options = [
      'layout--column-gap--none' => $this->t('None'),
      'layout--column-gap--small' => $this->t('Small'),
      'layout--column-gap--default' => $this->t('Default'),
      'layout--column-gap--big' => $this->t('Big'),
    ];
    $this->moduleHandler->alter('layout_builder_base_column_gap', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRowGapOptions() {
    $options = [
      'layout--row-gap--none' => $this->t('None'),
      'layout--row-gap--small' => $this->t('Small'),
      'layout--row-gap--default' => $this->t('Default'),
      'layout--row-gap--big' => $this->t('Big'),
    ];
    $this->moduleHandler->alter('layout_builder_base_row_gap', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getColumnBreakpointOptions() {
    $options = [
      'layout--column-breakpoint--none' => $this->t('None'),
      'layout--column-breakpoint--small' => $this->t('Small'),
      'layout--column-breakpoint--medium' => $this->t('Medium'),
      'layout--column-breakpoint--standard' => $this->t('Standard'),
      'layout--column-breakpoint--large' => $this->t('Large'),
    ];
    $this->moduleHandler->alter('layout_builder_base_column_breakpoint', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAlignItemsOptions() {
    $options = [
      'layout--align-items--normal' => $this->t('Normal'),
      'layout--align-items--stretch' => $this->t('Stretch'),
      'layout--align-items--center' => $this->t('Center'),
      'layout--align-items--start' => $this->t('Start'),
      'layout--align-items--end' => $this->t('End'),
    ];
    $this->moduleHandler->alter('layout_builder_base_align_items', $options);

    return $options;
  }
}
