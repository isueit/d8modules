<?php

namespace Drupal\layout_builder_base\Plugin\Layout;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\layout_builder\Plugin\Layout\MultiWidthLayoutBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configurable layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
abstract class BaseLayoutBase extends MultiWidthLayoutBase {

  /**
   * The layout builder base settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $layoutBuilderBaseSettings;

  /**
   * Constructs a BaseOneColumnLayout object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *    The config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    $this->layoutBuilderBaseSettings = $config_factory->get('layout_builder_base.settings');
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [
      'id' => '',
      'classes' => '',
      'column_gap' => $this->getDefaultColumnGap(),
      'row_gap' => $this->getDefaultRowGap(),
      'column_width' => $this->getDefaultColumnWidth(),
      'column_breakpoint' => $this->getDefaultColumnBreakpoint(),
      'align_items' => $this->getDefaultAlignItems(),
      'background' => $this->getDefaultBackground(),
      'background_image' => $this->getDefaultImageBackground(),
      'background_image_style' => $this->getDefaultBackgroundImageStyle(),
      'background_attachment' => $this->getDefaultBackgroundAttachment(),
      'background_position' => $this->getDefaultBackgroundPosition(),
      'background_size' => $this->getDefaultBackgroundSize(),
      'background_overlay' => $this->getDefaultBackgroundOverlay(),
      'equal_top_bottom_margins' => $this->getDefaultEqualTopBottomMargin(),
      'equal_left_right_margins' => $this->getDefaultEqualLeftRightMargin(),
      'top_margin' => $this->getDefaultTopMargin(),
      'right_margin' => $this->getDefaultRightMargin(),
      'bottom_margin' => $this->getDefaultBottomMargin(),
      'left_margin' => $this->getDefaultLeftMargin(),
      'equal_top_bottom_paddings' => $this->getDefaultEqualTopBottomPadding(),
      'equal_left_right_paddings' => $this->getDefaultEqualLeftRightPadding(),
      'top_padding' => $this->getDefaultTopPadding(),
      'right_padding' => $this->getDefaultRightPadding(),
      'bottom_padding' => $this->getDefaultBottomPadding(),
      'left_padding' => $this->getDefaultLeftPadding(),
      'container' => $this->getDefaultContainer(),
      'content_container' => $this->getDefaultContentContainer(),
      'height' => $this->getDefaultHeight(),
      'color' => $this->getDefaultColor(),
      'alignment' => $this->getDefaultAlignment(),
      'modifier' => $this->getDefaultModifiers(),
      'customizable_columns' => $this->getDefaultCustomizableColumns(),
    ];

    return array_merge(parent::defaultConfiguration(), $config);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Since we extend MultiWidthLayoutBase, remove column_widths when empty.
    if (empty($this->getWidthOptions())) {
      unset($form['column_widths']);
    }

    $form['advanced'] = [
      '#type'  => 'details',
      '#title' => $this->t('Advanced properties'),
    ];

    $form['advanced']['id'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Id'),
      '#default_value' => $this->configuration['id'],
      '#description' => $this->t('The ID attribute on the layout\'s &lt;div&gt; element.'),
    ];

    $form['advanced']['classes'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Classes'),
      '#default_value' => $this->configuration['classes'],
      '#description' => $this->t('Classes on the layout\'s &lt;div&gt; element.'),
    ];

    if ($this->isMultipleColumnsLayout()) {
      $form['grid'] = [
        '#type'  => 'details',
        '#title' => $this->t('Grid properties'),
      ];

      if (!empty($this->getColumnGapOptions())) {
        $form['grid']['column_gap'] = [
          '#type' => 'select',
          '#title' => $this->t('Column gap'),
          '#default_value' => $this->configuration['column_gap'],
          '#options' => $this->getColumnGapOptions(),
          '#description' => $this->t('Choose the column gap for this layout.'),
        ];
      }

      if (!empty($this->getRowGapOptions())) {
        $form['grid']['row_gap'] = [
          '#type' => 'select',
          '#title' => $this->t('Row gap'),
          '#default_value' => $this->configuration['row_gap'],
          '#options' => $this->getRowGapOptions(),
          '#description' => $this->t('Choose the row gap for this layout.'),
        ];
      }

      if (!empty($this->getColumnBreakpointOptions())) {
        $form['grid']['column_breakpoint'] = [
          '#type' => 'select',
          '#title' => $this->t('Column breakpoint'),
          '#default_value' => $this->configuration['column_breakpoint'],
          '#options' => $this->getColumnBreakpointOptions(),
          '#description' => $this->t('Choose the column breakpoint where it comes back to one column for this layout.'),
        ];
      }

      if (!empty($this->getColumnWidthOptions())) {
        $form['grid']['column_width'] = [
          '#type' => 'select',
          '#title' => $this->t('Column width'),
          '#default_value' => $this->configuration['column_width'],
          '#options' => $this->getColumnWidthOptions(),
          '#description' => $this->t('Choose the column width for this layout.'),
        ];
      }

      if (!empty($this->getAlignItemsOptions())) {
        $form['grid']['align_items'] = [
          '#type' => 'select',
          '#title' => $this->t('Align items'),
          '#default_value' => $this->configuration['align_items'],
          '#options' => $this->getAlignItemsOptions(),
          '#description' => $this->t('Choose the align items logic for this layout.'),
        ];
      }

      if (!empty($this->getCustomizableColumnsOptions())) {
        $form['grid']['customizable_columns'] = [
          '#type' => 'select',
          '#title' => $this->t('Customizable columns'),
          '#default_value' => $this->configuration['customizable_columns'],
          '#options' => $this->getCustomizableColumnsOptions(),
          '#description' => $this->t('Choose the type of column layout.'),
        ];
      }
    }

    $background_implemented = $this->enableImageBackground() || !empty($this->getBackgroundOptions()) || !empty($this->getBackgroundAttachmentOptions()) || !empty($this->getBackgroundPositionOptions()) || !empty($this->getBackgroundSizeOptions()) || !empty($this->getBackgroundOverlayOptions());
    if ($background_implemented) {
      $form['background_detail'] = [
        '#type'  => 'details',
        '#title' => $this->t('Background'),
      ];
    }

    if ($this->enableImageBackground()) {
      $form['background_detail']['background_image'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Background image'),
        '#default_value' => (!empty($this->configuration['background_image'])) ? [$this->configuration['background_image']] : '',
        '#description' => $this->t('Choose a background image for this layout. This feature is using inline CSS.'),
        '#upload_validators' => [
          'file_validate_is_image' => [],
          'file_validate_extensions' => ['gif png jpg jpeg'],
        ],
        '#upload_location' => 'public://layout_background/',
      ];

      $form['background_detail']['background_image_style'] = [
        '#type' => 'select',
        '#title' => $this->t('Background Image Style'),
        '#default_value' => $this->configuration['background_image_style'],
        '#options' => $this->getBackgroundImageStyleOptions(),
      ];
    }

    if (!empty($this->getBackgroundOptions())) {
      $form['background_detail']['background'] = [
        '#type' => 'select',
        '#title' => $this->t('Background'),
        '#default_value' => $this->configuration['background'],
        '#options' => $this->getBackgroundOptions(),
        '#description' => $this->t('Choose the background for this layout.'),
      ];
    }

    if (!empty($this->getBackgroundAttachmentOptions())) {
      $form['background_detail']['background_attachment'] = [
        '#type' => 'select',
        '#title' => $this->t('Background Attachment'),
        '#default_value' => $this->configuration['background_attachment'],
        '#options' => $this->getBackgroundAttachmentOptions(),
        '#description' => $this->t('Choose the background attachment for this layout.'),
      ];
    }

    if (!empty($this->getBackgroundPositionOptions())) {
      $form['background_detail']['background_position'] = [
        '#type' => 'select',
        '#title' => $this->t('Background Position'),
        '#default_value' => $this->configuration['background_position'],
        '#options' => $this->getBackgroundPositionOptions(),
        '#description' => $this->t('Choose the background position for this layout.'),
      ];
    }

    if (!empty($this->getBackgroundSizeOptions())) {
      $form['background_detail']['background_size'] = [
        '#type' => 'select',
        '#title' => $this->t('Background Size'),
        '#default_value' => $this->configuration['background_size'],
        '#options' => $this->getBackgroundSizeOptions(),
        '#description' => $this->t('Choose the background size for this layout.'),
      ];
    }

    if (!empty($this->getBackgroundOverlayOptions())) {
      $form['background_detail']['background_overlay'] = [
        '#type' => 'select',
        '#title' => $this->t('Background Overlay'),
        '#default_value' => $this->configuration['background_overlay'],
        '#options' => $this->getBackgroundOverlayOptions(),
        '#description' => $this->t('Choose the background overlay for this layout.'),
      ];
    }

    $margin_implemented = !empty($this->getTopMarginOptions()) || !empty($this->getBottomMarginOptions()) || !empty($this->getLeftMarginOptions()) || !empty($this->getRightMarginOptions()) || !empty($this->getEqualTopBottomMarginsOptions()) || !empty($this->getEqualLeftRightMarginsOptions());
    if ($margin_implemented) {
      $form['margin'] = [
        '#type'  => 'details',
        '#title' => $this->t('Margins'),
      ];
    }

    if (!empty($this->getTopMarginOptions())) {
      $form['margin']['top_margin'] = [
        '#type' => 'select',
        '#title' => $this->t('Top Margin'),
        '#default_value' => $this->configuration['top_margin'],
        '#options' => $this->getTopMarginOptions(),
        '#description' => $this->t('Choose the top margin for this layout.'),
      ];
    }

    if (!empty($this->getBottomMarginOptions())) {
      $form['margin']['bottom_margin'] = [
        '#type' => 'select',
        '#title' => $this->t('Bottom Margin'),
        '#default_value' => $this->configuration['bottom_margin'],
        '#options' => $this->getBottomMarginOptions(),
        '#description' => $this->t('Choose the bottom margin for this layout.'),
      ];
    }

    if (!empty($this->getLeftMarginOptions())) {
      $form['margin']['left_margin'] = [
        '#type' => 'select',
        '#title' => $this->t('Left Margin'),
        '#default_value' => $this->configuration['left_margin'],
        '#options' => $this->getLeftMarginOptions(),
        '#description' => $this->t('Choose the left margin for this layout.'),
      ];
    }

    if (!empty($this->getRightMarginOptions())) {
      $form['margin']['right_margin'] = [
        '#type' => 'select',
        '#title' => $this->t('Right Margin'),
        '#default_value' => $this->configuration['right_margin'],
        '#options' => $this->getRightMarginOptions(),
        '#description' => $this->t('Choose the right margin for this layout.'),
      ];
    }

    if (!empty($this->getEqualTopBottomMarginsOptions())) {
      $form['margin']['equal_top_bottom_margins'] = [
        '#type' => 'select',
        '#title' => $this->t('Top Bottom Margins'),
        '#default_value' => $this->configuration['equal_top_bottom_margins'],
        '#options' => $this->getEqualTopBottomMarginsOptions(),
        '#description' => $this->t('Choose the top/bottom margins for this layout.'),
      ];
    }

    if (!empty($this->getEqualLeftRightMarginsOptions())) {
      $form['margin']['equal_left_right_margins'] = [
        '#type' => 'select',
        '#title' => $this->t('Left Right Margins'),
        '#default_value' => $this->configuration['equal_left_right_margins'],
        '#options' => $this->getEqualLeftRightMarginsOptions(),
        '#description' => $this->t('Choose the left/right margins for this layout.'),
      ];
    }

    $padding_implemented = !empty($this->getTopPaddingOptions()) || !empty($this->getBottomPaddingOptions()) || !empty($this->getLeftPaddingOptions()) || !empty($this->getRightPaddingOptions()) || !empty($this->getEqualTopBottomPaddingsOptions()) || !empty($this->getEqualLeftRightPaddingsOptions());
    if ($padding_implemented) {
      $form['padding'] = [
        '#type'  => 'details',
        '#title' => $this->t('Paddings'),
      ];
    }

    if (!empty($this->getTopPaddingOptions())) {
      $form['padding']['top_padding'] = [
        '#type' => 'select',
        '#title' => $this->t('Top Padding'),
        '#default_value' => $this->configuration['top_padding'],
        '#options' => $this->getTopPaddingOptions(),
        '#description' => $this->t('Choose the top padding for this layout.'),
      ];
    }

    if (!empty($this->getBottomPaddingOptions())) {
      $form['padding']['bottom_padding'] = [
        '#type' => 'select',
        '#title' => $this->t('Bottom Padding'),
        '#default_value' => $this->configuration['bottom_padding'],
        '#options' => $this->getBottomPaddingOptions(),
        '#description' => $this->t('Choose the bottom padding for this layout.'),
      ];
    }

    if (!empty($this->getLeftPaddingOptions())) {
      $form['padding']['left_padding'] = [
        '#type' => 'select',
        '#title' => $this->t('Left Padding'),
        '#default_value' => $this->configuration['left_padding'],
        '#options' => $this->getLeftPaddingOptions(),
        '#description' => $this->t('Choose the left padding for this layout.'),
      ];
    }

    if (!empty($this->getRightPaddingOptions())) {
      $form['padding']['right_padding'] = [
        '#type' => 'select',
        '#title' => $this->t('Right Padding'),
        '#default_value' => $this->configuration['right_padding'],
        '#options' => $this->getRightPaddingOptions(),
        '#description' => $this->t('Choose the right padding for this layout.'),
      ];
    }

    if (!empty($this->getEqualTopBottomPaddingsOptions())) {
      $form['padding']['equal_top_bottom_paddings'] = [
        '#type' => 'select',
        '#title' => $this->t('Top Bottom Paddings'),
        '#default_value' => $this->configuration['equal_top_bottom_paddings'],
        '#options' => $this->getEqualTopBottomPaddingsOptions(),
        '#description' => $this->t('Choose the top/bottom paddings for this layout.'),
      ];
    }

    if (!empty($this->getEqualLeftRightPaddingsOptions())) {
      $form['padding']['equal_left_right_paddings'] = [
        '#type' => 'select',
        '#title' => $this->t('Left Right Paddings'),
        '#default_value' => $this->configuration['equal_left_right_paddings'],
        '#options' => $this->getEqualLeftRightPaddingsOptions(),
        '#description' => $this->t('Choose the left/right paddings for this layout.'),
      ];
    }

    $container_implemented = !empty($this->getHeightOptions()) || !empty($this->getContainerOptions()) || !empty($this->getContentContainerOptions());
    if (!empty($container_implemented)) {
      $form['container_detail'] = [
        '#type'  => 'details',
        '#title' => $this->t('Container'),
      ];
    }

    if (!empty($this->getHeightOptions())) {
      $form['container_detail']['height'] = [
        '#type' => 'select',
        '#title' => $this->t('Height'),
        '#default_value' => $this->configuration['height'],
        '#options' => $this->getHeightOptions(),
        '#description' => $this->t('Choose the height for this layout. It will change the minimum height of your layout.'),
      ];
    }

    if (!empty($this->getContainerOptions())) {
      $form['container_detail']['container'] = [
        '#type' => 'select',
        '#title' => $this->t('Container'),
        '#default_value' => $this->configuration['container'],
        '#options' => $this->getContainerOptions(),
        '#description' => $this->t('Choose the size of the container for this layout. It will change the maximum width of your layout.'),
      ];
    }

    if (!empty($this->getContentContainerOptions())) {
      $form['container_detail']['content_container'] = [
        '#type' => 'select',
        '#title' => $this->t('Content container'),
        '#default_value' => $this->configuration['content_container'],
        '#options' => $this->getContentContainerOptions(),
        '#description' => $this->t('Choose the size of the container for the content of this layout. It is pretty usefull if you want a full width background but contains your content in the middle.'),
      ];
    }

    $style_implemented = !empty($this->getColorsOptions()) || !empty($this->getAlignmentOptions()) || !empty($this->getModifiersOptions());
    if ($style_implemented) {
      $form['styles'] = [
        '#type'  => 'details',
        '#title' => $this->t('Styles'),
      ];
    }

    if (!empty($this->getColorsOptions())) {
      $form['styles']['color'] = [
        '#type' => 'select',
        '#title' => $this->t('Default color'),
        '#default_value' => $this->configuration['color'],
        '#options' => $this->getColorsOptions(),
        '#description' => $this->t('Choose the default color for the text of this layout.'),
      ];
    }

    if (!empty($this->getAlignmentOptions())) {
      $form['styles']['alignment'] = [
        '#type' => 'select',
        '#title' => $this->t('Default alignment'),
        '#default_value' => $this->configuration['alignment'],
        '#options' => $this->getAlignmentOptions(),
        '#description' => $this->t('Choose the default alignment for the text of this layout.'),
      ];
    }

    if (!empty($this->getModifiersOptions())) {
      $form['styles']['modifiers'] = [
        '#type' => 'select',
        '#title' => $this->t('Modifiers'),
        '#default_value' => $this->configuration['modifiers'],
        '#options' => $this->getModifiersOptions(),
        '#description' => $this->t('Choose the modifiers for this layout.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $advanced = $form_state->getValue('advanced');
    $grid_values = $form_state->getValue('grid');
    $background_values = $form_state->getValue('background_detail');
    $padding_values = $form_state->getValue('padding');
    $margin_values = $form_state->getValue('margin');
    $container_values = $form_state->getValue('container_detail');
    $style_values = $form_state->getValue('styles');

    $background_file = !empty($background_values['background_image']) ? $background_values['background_image'] : 0;
    if (isset($background_file[0]) && !empty($background_file[0])) {
      $file = File::load($background_file[0]);
      $file->setPermanent();
      $file->save();
    }

    $image_background_style = $background_values['background_image_style'];

    $this->configuration['id'] = $advanced['id'];
    $this->configuration['classes'] =  $advanced['classes'];
    $this->configuration['background_image'] = !empty($file) ? $file->id() : '';
    $this->configuration['background_image_style'] = !empty($image_background_style) ? $image_background_style : '';
    $this->configuration['background'] = $background_values['background'] ?? '';
    $this->configuration['background_attachment'] = $background_values['background_attachment'] ?? '';
    $this->configuration['background_position'] = $background_values['background_position'] ?? '';
    $this->configuration['background_size'] = $background_values['background_size'] ?? '';
    $this->configuration['background_overlay'] = $background_values['background_overlay'] ?? '';
    $this->configuration['top_margin'] = $margin_values['top_margin'] ?? '';
    $this->configuration['bottom_margin'] = $margin_values['bottom_margin'] ?? '';
    $this->configuration['left_margin'] = $margin_values['left_margin'] ?? '';
    $this->configuration['right_margin'] = $margin_values['right_margin'] ?? '';
    $this->configuration['equal_top_bottom_margins'] = $margin_values['equal_top_bottom_margins'] ?? '';
    $this->configuration['equal_left_right_margins'] = $margin_values['equal_left_right_margins'] ?? '';
    $this->configuration['equal_top_bottom_paddings'] = $padding_values['equal_top_bottom_paddings'] ?? '';
    $this->configuration['equal_left_right_paddings'] = $padding_values['equal_left_right_paddings'] ?? '';
    $this->configuration['top_padding'] = $padding_values['top_padding'] ?? '';
    $this->configuration['bottom_padding'] = $padding_values['bottom_padding'] ?? '';
    $this->configuration['left_padding'] = $padding_values['left_padding'] ?? '';
    $this->configuration['right_padding'] = $padding_values['right_padding'] ?? '';
    $this->configuration['container'] = $container_values['container'] ?? '';
    $this->configuration['content_container'] = $container_values['content_container'] ?? '';
    $this->configuration['height'] = $container_values['height'] ?? '';
    $this->configuration['color'] = $style_values['color'] ?? '';
    $this->configuration['alignment'] = $style_values['alignment'] ?? '';
    $this->configuration['column_gap'] = $grid_values['column_gap'] ?? '';
    $this->configuration['row_gap'] = $grid_values['row_gap'] ?? '';
    $this->configuration['column_width'] = $grid_values['column_width'] ?? '';
    $this->configuration['column_breakpoint'] = $grid_values['column_breakpoint'] ?? '';
    $this->configuration['align_items'] = $grid_values['align_items'] ?? '';
    $this->configuration['modifiers'] = $style_values['modifiers'] ?? '';
    $this->configuration['customizable_columns'] = $grid_values['customizable_columns'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($values['advanced']['id'] != Html::cleanCssIdentifier($values['advanced']['id'])) {
      $form_state->setError($form['advanced']['id'], $this->t('Element ID must be a valid CSS ID'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);

    if (!empty($this->configuration['id'])) {
      $build['#attributes']['id'] = $this->configuration['id'];
    }

    $classes = [
      $this->configuration['classes'],
      $this->configuration['background'],
      $this->configuration['background_attachment'],
      $this->configuration['background_position'],
      $this->configuration['background_size'],
      $this->configuration['background_overlay'],
      $this->configuration['top_margin'],
      $this->configuration['bottom_margin'],
      $this->configuration['left_margin'],
      $this->configuration['right_margin'],
      $this->configuration['equal_top_bottom_margins'],
      $this->configuration['equal_left_right_margins'],
      $this->configuration['top_padding'],
      $this->configuration['bottom_padding'],
      $this->configuration['left_padding'],
      $this->configuration['right_padding'],
      $this->configuration['equal_top_bottom_paddings'],
      $this->configuration['equal_left_right_paddings'],
      $this->configuration['container'],
      $this->configuration['content_container'],
      $this->configuration['height'],
      $this->configuration['color'],
      $this->configuration['alignment'],
      $this->configuration['column_gap'],
      $this->configuration['row_gap'],
      $this->configuration['column_breakpoint'],
      $this->configuration['column_width'],
      $this->configuration['align_items'],
      $this->configuration['modifiers'],
      $this->configuration['customizable_columns'],
    ];
    $build_classes = array_merge($build['#attributes']['class'], $classes);
    $build['#attributes']['class'] = $build_classes;

    if (!empty($this->configuration['background_image'])) {
      $image = File::load($this->configuration['background_image']);
      if (!empty($image)) {
        $uri = $image->uri->value;
        $style = $this->configuration['background_image_style'];
        if (!empty($style)) {
          $uri = ImageStyle::load($style)->buildUrl($image->getFileUri());
        }
        $url = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);

        $build['#attributes']['style'] = 'background-image: url("' . $url . '")';
      }
    }

    return $build;
  }

  /**
   * Default behavior to get the default config option.
   *
   * @return string
   *   Return the first option.
   */
  protected function getDefaultConfigOption($config_key, $options) {
    $default = $this->layoutBuilderBaseSettings->get($config_key);
    $default_value = !empty($default) ? $default : '';

    if (empty($default_value)) {
      $options_keys = array_keys($options);
      $default_value = !empty($options_keys) ? array_shift($options_keys) : '';
    }
    return $default_value;
  }

  /**
   * Get the default background option.
   *
   * @return string
   *   Return the default background option.
   */
  protected function getDefaultBackground() {
    $options = $this->getBackgroundOptions();
    return $this->getDefaultConfigOption('background', $options);
  }

  /**
   * Get the default background option.
   *
   * @return string
   *   Return the default background option.
   */
  protected function getDefaultImageBackground() {
    return '';
  }

  /**
   * Get the default image style background option.
   *
   * @return string
   *   Return the default image style background option.
   */
  protected function getDefaultBackgroundImageStyle() {
    return '';
  }

  /**
   * Get the default background attachment option.
   *
   * @return string
   *   Return the default background attachment option.
   */
  protected function getDefaultBackgroundAttachment() {
    $options = $this->getBackgroundAttachmentOptions();
    return $this->getDefaultConfigOption('background_attachment', $options);
  }

  /**
   * Get the default background position option.
   *
   * @return string
   *   Return the default background position option.
   */
  protected function getDefaultBackgroundPosition() {
    $options = $this->getBackgroundPositionOptions();
    return $this->getDefaultConfigOption('background_position', $options);
  }

  /**
   * Get the default background size option.
   *
   * @return string
   *   Return the default background size option.
   */
  protected function getDefaultBackgroundSize() {
    $options = $this->getBackgroundSizeOptions();
    return $this->getDefaultConfigOption('background_size', $options);
  }

  /**
   * Get the default background overlay option.
   *
   * @return string
   *   Return the default background overlay option.
   */
  protected function getDefaultBackgroundOverlay() {
    $options = $this->getBackgroundOverlayOptions();
    return $this->getDefaultConfigOption('background_overlay', $options);
  }

  /**
   * Get the default equal top bottom margin option.
   *
   * @return string
   *   Return the default equal top bottom margin option.
   */
  protected function getDefaultEqualTopBottomMargin() {
    $options = $this->getEqualTopBottomMarginsOptions();
    return $this->getDefaultConfigOption('equal_top_bottom_margins', $options);
  }

  /**
   * Get the default equal left right margin option.
   *
   * @return string
   *   Return the default equal left right margin option.
   */
  protected function getDefaultEqualLeftRightMargin() {
    $options = $this->getEqualLeftRightMarginsOptions();
    return $this->getDefaultConfigOption('equal_left_right_margins', $options);
  }

  /**
   * Get the default top margin option.
   *
   * @return string
   *   Return the default top margin option.
   */
  protected function getDefaultTopMargin() {
    $options = $this->getTopMarginOptions();
    return $this->getDefaultConfigOption('top_margin', $options);
  }

  /**
   * Get the default right margin option.
   *
   * @return string
   *   Return the default right margin option.
   */
  protected function getDefaultRightMargin() {
    $options = $this->getRightMarginOptions();
    return $this->getDefaultConfigOption('right_margin', $options);
  }

  /**
   * Get the default bottom margin option.
   *
   * @return string
   *   Return the default bottom margin option.
   */
  protected function getDefaultBottomMargin() {
    $options = $this->getBottomMarginOptions();
    return $this->getDefaultConfigOption('bottom_margin', $options);
  }

  /**
   * Get the default left margin option.
   *
   * @return string
   *   Return the default left margin option.
   */
  protected function getDefaultLeftMargin() {
    $options = $this->getLeftMarginOptions();
    return $this->getDefaultConfigOption('left_margin', $options);
  }

  /**
   * Get the default equal top bottom padding option.
   *
   * @return string
   *   Return the default equal top bottom padding option.
   */
  protected function getDefaultEqualTopBottomPadding() {
    $options = $this->getEqualTopBottomPaddingsOptions();
    return $this->getDefaultConfigOption('equal_top_bottom_paddings', $options);
  }

  /**
   * Get the default equal left right padding option.
   *
   * @return string
   *   Return the default equal left right padding option.
   */
  protected function getDefaultEqualLeftRightPadding() {
    $options = $this->getEqualLeftRightPaddingsOptions();
    return $this->getDefaultConfigOption('equal_left_right_paddings', $options);
  }

  /**
   * Get the default top padding option.
   *
   * @return string
   *   Return the default top padding option.
   */
  protected function getDefaultTopPadding() {
    $options = $this->getTopPaddingOptions();
    return $this->getDefaultConfigOption('top_padding', $options);
  }

  /**
   * Get the default right padding option.
   *
   * @return string
   *   Return the default right padding option.
   */
  protected function getDefaultRightPadding() {
    $options = $this->getRightPaddingOptions();
    return $this->getDefaultConfigOption('right_padding', $options);
  }

  /**
   * Get the default bottom padding option.
   *
   * @return string
   *   Return the default bottom padding option.
   */
  protected function getDefaultBottomPadding() {
    $options = $this->getBottomPaddingOptions();
    return $this->getDefaultConfigOption('bottom_padding', $options);
  }

  /**
   * Get the default left padding option.
   *
   * @return string
   *   Return the default left padding option.
   */
  protected function getDefaultLeftPadding() {
    $options = $this->getLeftPaddingOptions();
    return $this->getDefaultConfigOption('left_padding', $options);
  }

  /**
   * Get the default container option.
   *
   * @return string
   *   Return the default container option.
   */
  protected function getDefaultContainer() {
    $options = $this->getContainerOptions();
    return $this->getDefaultConfigOption('container', $options);
  }

  /**
   * Get the default content container option.
   *
   * @return string
   *   Return the default content container option.
   */
  protected function getDefaultContentContainer() {
    $options = $this->getContentContainerOptions();
    return $this->getDefaultConfigOption('content_container', $options);
  }

  /**
   * Get the default height option.
   *
   * @return string
   *   Return the default height option.
   */
  protected function getDefaultHeight() {
    $options = $this->getHeightOptions();
    return $this->getDefaultConfigOption('height', $options);
  }

  /**
   * Get the default color option.
   *
   * @return string
   *   Return the default color option.
   */
  protected function getDefaultColor() {
    $options = $this->getColorsOptions();
    return $this->getDefaultConfigOption('color', $options);
  }

  /**
   * Get the default alignment option.
   *
   * @return string
   *   Return the default alignment option.
   */
  protected function getDefaultAlignment() {
    $options = $this->getAlignmentOptions();
    return $this->getDefaultConfigOption('alignment', $options);
  }

  /**
   * Get the default column gap option.
   *
   * @return string
   *   Return the default column gap option.
   */
  protected function getDefaultColumnGap() {
    $options = $this->getColumnGapOptions();
    return $this->getDefaultConfigOption('column_gap', $options);
  }

  /**
   * Get the default row gap option.
   *
   * @return string
   *   Return the default row gap option.
   */
  protected function getDefaultRowGap() {
    $options = $this->getRowGapOptions();
    return $this->getDefaultConfigOption('row_gap', $options);
  }

  /**
   * Get the default column breakpoint option.
   *
   * @return string
   *   Return the default column breakpoint option.
   */
  protected function getDefaultColumnBreakpoint() {
    $options = $this->getColumnBreakpointOptions();
    return $this->getDefaultConfigOption('column_breakpoint', $options);
  }

  /**
   * Get the default column width option.
   *
   * @return string
   *   Return the default column width option.
   */
  protected function getDefaultColumnWidth() {
    $options = $this->getColumnWidthOptions();
    return $this->getDefaultConfigOption('column_width', $options);
  }

  /**
   * Get the default align items option.
   *
   * @return string
   *   Return the default align items option.
   */
  protected function getDefaultAlignItems() {
    $options = $this->getColumnWidthOptions();
    return $this->getDefaultConfigOption('align_items', $options);
  }

  /**
   * Get the default modifiers option.
   *
   * @return string
   *   Return the default modifiers option.
   */
  protected function getDefaultModifiers() {
    $options = $this->getModifiersOptions();
    return $this->getDefaultConfigOption('modifier', $options);
  }

  /**
   * Get the default customizable columns option.
   *
   * @return string
   *   Return the default customizable columns option.
   */
  protected function getDefaultCustomizableColumns() {
    $options = $this->getCustomizableColumnsOptions();
    return $this->getDefaultConfigOption('customizable_columns', $options);
  }

  /**
   * Function to enable the multiple columns features.
   *
   * Override this function and return TRUE in order to enable the multiple
   * columns features.
   *
   * @return bool
   *   Boolean to know if the multiple columns features are enabled or not.
   */
  protected function isMultipleColumnsLayout() {
    return FALSE;
  }

  /**
   * Function to enable the background image feature.
   *
   * Override this function and return TRUE in order to enable the image
   * background feature.
   *
   * @return bool
   *   Boolean to know if the image background feature is enabled or not.
   */
  protected function enableImageBackground() {
    return FALSE;
  }

  /**
   * Gets the background options for the configuration form.
   *
   * @return string[]
   *   The background options array where the keys are strings that will
   *   be added to the CSS classes and the values are the human readable labels.
   */
  abstract protected function getBackgroundOptions();

  /**
   * Gets the background image style options for the configuration form.
   *
   * @return string[]
   *   The background image style options array where the keys are strings that
   *   will be added to the CSS classes and the values are the human readable
   *   labels.
   */
  protected function getBackgroundImageStyleOptions() {
    $image_styles = ImageStyle::loadMultiple();
    $options = [
      '' => $this->t('None'),
    ];
    foreach ($image_styles as $style) {
      $options[$style->id()] = $style->label();
    }

    return $options;
  }

  /**
   * Gets the background attachment options for the configuration form.
   *
   * @return string[]
   *   The background attachment options array where the keys are strings that
   *   will be added to the CSS classes and the values are the human readable
   *   labels.
   */
  abstract protected function getBackgroundAttachmentOptions();

  /**
   * Gets the background position options for the configuration form.
   *
   * @return string[]
   *   The background position options array where the keys are strings that
   *   will be added to the CSS classes and the values are the human readable
   *   labels.
   */
  abstract protected function getBackgroundPositionOptions();

  /**
   * Gets the background size options for the configuration form.
   *
   * @return string[]
   *   The background size options array where the keys are strings that
   *   will be added to the CSS classes and the values are the human readable
   *   labels.
   */
  abstract protected function getBackgroundSizeOptions();

  /**
   * Gets the background overlay options for the configuration form.
   *
   * @return string[]
   *   The background overlay options array where the keys are strings that
   *   will be added to the CSS classes and the values are the human readable
   *   labels.
   */
  abstract protected function getBackgroundOverlayOptions();

  /**
   * Gets the top and bottom equal margin options for the configuration form.
   *
   * @return string[]
   *   The top and bottom equal margin options array where the keys are strings
   *   that will be added to the CSS classes and the values are the human
   *   readable labels.
   */
  abstract protected function getEqualTopBottomMarginsOptions();

  /**
   * Gets the left and right equal margin options for the configuration form.
   *
   * @return string[]
   *   The left and right equal margin options array where the keys are strings
   *   that will be added to the CSS classes and the values are the human
   *   readable labels.
   */
  abstract protected function getEqualLeftRightMarginsOptions();

  /**
   * Gets the top margin options for the configuration form.
   *
   * @return string[]
   *   The top margin options array where the keys are strings
   *   that will be added to the CSS classes and the values are the human
   *   readable labels.
   */
  abstract protected function getTopMarginOptions();

  /**
   * Gets the right margin options for the configuration form.
   *
   * @return string[]
   *   The right margin options array where the keys are strings
   *   that will be added to the CSS classes and the values are the human
   *   readable labels.
   */
  abstract protected function getRightMarginOptions();

  /**
   * Gets the bottom margin options for the configuration form.
   *
   * @return string[]
   *   The bottom margin options array where the keys are strings
   *   that will be added to the CSS classes and the values are the human
   *   readable labels.
   */
  abstract protected function getBottomMarginOptions();

  /**
   * Gets the left margin options for the configuration form.
   *
   * @return string[]
   *   The left margin options array where the keys are strings
   *   that will be added to the CSS classes and the values are the human
   *   readable labels.
   */
  abstract protected function getLeftMarginOptions();

  /**
   * Gets the top and bottom equal padding options for the configuration form.
   *
   * @return string[]
   *   The top and bottom equal padding options array where the keys are strings
   *   that will be added to the CSS classes and the values are the human
   *   readable labels.
   */
  abstract protected function getEqualTopBottomPaddingsOptions();

  /**
   * Gets the left and right equal padding options for the configuration form.
   *
   * @return string[]
   *   The left and right equal padding options array where the keys are strings
   *   that will be added to the CSS classes and the values are the human
   *   readable labels.
   */
  abstract protected function getEqualLeftRightPaddingsOptions();

  /**
   * Gets the top padding options for the configuration form.
   *
   * @return string[]
   *   The top padding options array where the keys are strings
   *   that will be added to the CSS classes and the values are the human
   *   readable labels.
   */
  abstract protected function getTopPaddingOptions();

  /**
   * Gets the right padding options for the configuration form.
   *
   * @return string[]
   *   The right padding options array where the keys are strings
   *   that will be added to the CSS classes and the values are the human
   *   readable labels.
   */
  abstract protected function getRightPaddingOptions();

  /**
   * Gets the bottom padding options for the configuration form.
   *
   * @return string[]
   *   The bottom padding options array where the keys are strings
   *   that will be added to the CSS classes and the values are the human
   *   readable labels.
   */
  abstract protected function getBottomPaddingOptions();

  /**
   * Gets the left padding options for the configuration form.
   *
   * @return string[]
   *   The left padding options array where the keys are strings
   *   that will be added to the CSS classes and the values are the human
   *   readable labels.
   */
  abstract protected function getLeftPaddingOptions();

  /**
   * Gets the container options for the configuration form.
   *
   * @return string[]
   *   The container options array where the keys are strings that will be added
   *   to the CSS classes and the values are the human readable labels.
   */
  abstract protected function getContainerOptions();

  /**
   * Gets the content container options for the configuration form.
   *
   * @return string[]
   *   The content container options array where the keys are strings that will
   *   be added to the CSS classes and the values are the human readable labels.
   */
  abstract protected function getContentContainerOptions();

  /**
   * Gets the height options for the configuration form.
   *
   * @return string[]
   *   The height options array where the keys are strings that will be added
   *   to the CSS classes and the values are the human readable labels.
   */
  abstract protected function getHeightOptions();

  /**
   * Gets the colors options for the configuration form.
   *
   * @return string[]
   *   The colors options array where the keys are strings that will be added to
   *   the CSS classes and the values are the human readable labels.
   */
  abstract protected function getColorsOptions();

  /**
   * Gets the alignment options for the configuration form.
   *
   * @return string[]
   *   The alignment options array where the keys are strings that will be added
   *   to the CSS classes and the values are the human readable labels.
   */
  abstract protected function getAlignmentOptions();

  /**
   * Gets the column gap options for the configuration form.
   *
   * @return string[]
   *   The column gap options array where the keys are strings that will be
   *   added to the CSS classes and the values are the human readable labels.
   */
  abstract protected function getColumnGapOptions();

  /**
   * Gets the row gap options for the configuration form.
   *
   * @return string[]
   *   The row gap options array where the keys are strings that will be
   *   added to the CSS classes and the values are the human readable labels.
   */
  abstract protected function getRowGapOptions();

  /**
   * Gets the column breakpoint options for the configuration form.
   *
   * @return string[]
   *   The column breakpoint options array where the keys are strings that will
   *   be added to the CSS classes and the values are the human readable labels.
   */
  abstract protected function getColumnBreakpointOptions();

  /**
   * Gets the column width options for the configuration form.
   *
   * @return string[]
   *   The column width options array where the keys are strings that will
   *   be added to the CSS classes and the values are the human readable labels.
   */
  abstract protected function getColumnWidthOptions();

  /**
   * Gets the align items options for the configuration form.
   *
   * @return string[]
   *   The align items options array where the keys are strings that will
   *   be added to the CSS classes and the values are the human readable labels.
   */
  abstract protected function getAlignItemsOptions();

  /**
   * Gets the modifiers options for the configuration form.
   *
   * @return string[]
   *   The modifiers options array where the keys are strings that will
   *   be added to the CSS classes and the values are the human readable labels.
   *   The modifiers are there for developers in order to implement special
   *   logic.
   */
  abstract protected function getModifiersOptions();

  /**
   * Gets the customizable columns options for the configuration form.
   *
   * @return string[]
   *   The customizable columns  options array where the keys are strings that
   *   will be added to the CSS classes and the values are the human readable
   *   labels. The modifiers are there for developers in order to implement
   *   special logic.
   */
  abstract protected function getCustomizableColumnsOptions();

}

