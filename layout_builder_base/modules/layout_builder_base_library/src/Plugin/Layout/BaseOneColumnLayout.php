<?php

namespace Drupal\layout_builder_base_library\Plugin\Layout;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_builder_base\Plugin\Layout\DefaultLayoutBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configurable layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class BaseOneColumnLayout extends DefaultLayoutBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $account
   *    The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getBackgroundOptions() {
    $options = [
      'layout--background--none' => $this->t('None'),
      'layout--background--white' => $this->t('White'),
      'layout--background--grey' => $this->t('Grey'),
      'layout--background--black' => $this->t('Black'),
    ];
    $this->moduleHandler->alter('layout_builder_base_background', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBackgroundAttachmentOptions() {
    $options = [
      'layout--background-attachment--default' => $this->t('Default'),
      'layout--background-attachment--fixed' => $this->t('Fixed'),
    ];

    $this->moduleHandler->alter('layout_builder_base_background_attachment', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBackgroundPositionOptions() {
    $options = [
      'layout--background-position--default' => $this->t('Default'),
      'layout--background-position--center' => $this->t('Center'),
    ];
    $this->moduleHandler->alter('layout_builder_base_background_position', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBackgroundSizeOptions() {
    $options = [
      'layout--background-size--default' => $this->t('Default'),
      'layout--background-size--cover' => $this->t('Cover'),
      'layout--background-size--contain' => $this->t('Contain'),
    ];
    $this->moduleHandler->alter('layout_builder_base_background_size', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBackgroundOverlayOptions() {
    $options = [
      'layout--background-overlay--none' => $this->t('None'),
      'layout--background-overlay--dark-light' => $this->t('Light Dark'),
      'layout--background-overlay--dark' => $this->t('Dark'),
      'layout--background-overlay--darker' => $this->t('Darker'),
    ];
    $this->moduleHandler->alter('layout_builder_base_background_overlay', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTopMarginOptions() {
    $options = [
      '' => $this->t('Choose'),
      'layout--top-margin--none' => $this->t('None'),
      'layout--top-margin--small' => $this->t('Small margin'),
      'layout--top-margin--default' => $this->t('Default margin'),
      'layout--top-margin--big' => $this->t('Big margin'),
    ];
    $this->moduleHandler->alter('layout_builder_base_top_margin', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBottomMarginOptions() {
    $options = [
      '' => $this->t('Choose'),
      'layout--bottom-margin--none' => $this->t('None'),
      'layout--bottom-margin--small' => $this->t('Small margin'),
      'layout--bottom-margin--default' => $this->t('Default margin'),
      'layout--bottom-margin--big' => $this->t('Big margin'),
    ];
    $this->moduleHandler->alter('layout_builder_base_bottom_margin', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLeftMarginOptions() {
    $options = [
      '' => $this->t('Choose'),
      'layout--left-margin--none' => $this->t('None'),
      'layout--left-margin--small' => $this->t('Small margin'),
      'layout--left-margin--default' => $this->t('Default margin'),
      'layout--left-margin--big' => $this->t('Big margin'),
    ];
    $this->moduleHandler->alter('layout_builder_base_left_margin', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRightMarginOptions() {
    $options = [
      '' => $this->t('Choose'),
      'layout--right-margin--none' => $this->t('None'),
      'layout--right-margin--small' => $this->t('Small margin'),
      'layout--right-margin--default' => $this->t('Default margin'),
      'layout--right-margin--big' => $this->t('Big margin'),
    ];
    $this->moduleHandler->alter('layout_builder_base_right_margin', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEqualTopBottomMarginsOptions() {
    $options = [
      '' => $this->t('Choose'),
      'layout--top-bottom-margin--none' => $this->t('None'),
      'layout--top-bottom-margin--small' => $this->t('Small margins'),
      'layout--top-bottom-margin--default' => $this->t('Default margins'),
      'layout--top-bottom-margin--big' => $this->t('Big margins'),
    ];
    $this->moduleHandler->alter('layout_builder_base_top_bottom_margins', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEqualLeftRightMarginsOptions() {
    $options = [
      '' => $this->t('Choose'),
      'layout--left-right-margin--none' => $this->t('None'),
      'layout--left-right-margin--small' => $this->t('Small margins'),
      'layout--left-right-margin--default' => $this->t('Default margins'),
      'layout--left-right-margin--big' => $this->t('Big margins'),
    ];
    $this->moduleHandler->alter('layout_builder_base_left_right_margins', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTopPaddingOptions() {
    $options = [
      '' => $this->t('Choose'),
      'layout--top-padding--none' => $this->t('None'),
      'layout--top-padding--small' => $this->t('Small padding'),
      'layout--top-padding--default' => $this->t('Default padding'),
      'layout--top-padding--big' => $this->t('Big padding'),
    ];
    $this->moduleHandler->alter('layout_builder_base_top_padding', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBottomPaddingOptions() {
    $options = [
      '' => $this->t('Choose'),
      'layout--bottom-padding--none' => $this->t('None'),
      'layout--bottom-padding--small' => $this->t('Small padding'),
      'layout--bottom-padding--default' => $this->t('Default padding'),
      'layout--bottom-padding--big' => $this->t('Big padding'),
    ];
    $this->moduleHandler->alter('layout_builder_base_bottom_padding', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLeftPaddingOptions() {
    $options = [
      '' => $this->t('Choose'),
      'layout--left-padding--none' => $this->t('None'),
      'layout--left-padding--small' => $this->t('Small padding'),
      'layout--left-padding--default' => $this->t('Default padding'),
      'layout--left-padding--big' => $this->t('Big padding'),
    ];
    $this->moduleHandler->alter('layout_builder_base_left_padding', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRightPaddingOptions() {
    $options = [
      '' => $this->t('Choose'),
      'layout--right-padding--none' => $this->t('None'),
      'layout--right-padding--small' => $this->t('Small padding'),
      'layout--right-padding--default' => $this->t('Default padding'),
      'layout--right-padding--big' => $this->t('Big padding'),
    ];
    $this->moduleHandler->alter('layout_builder_base_right_padding', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEqualTopBottomPaddingsOptions() {
    $options = [
      '' => $this->t('Choose'),
      'layout--top-bottom-padding--none' => $this->t('None'),
      'layout--top-bottom-padding--small' => $this->t('Small paddings'),
      'layout--top-bottom-padding--default' => $this->t('Default paddings'),
      'layout--top-bottom-padding--big' => $this->t('Big paddings'),
    ];
    $this->moduleHandler->alter('layout_builder_base_top_bottom_paddings', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEqualLeftRightPaddingsOptions() {
    $options = [
      '' => $this->t('Choose'),
      'layout--left-right-padding--none' => $this->t('None'),
      'layout--left-right-padding--small' => $this->t('Small paddings'),
      'layout--left-right-padding--default' => $this->t('Default paddings'),
      'layout--left-right-padding--big' => $this->t('Big paddings'),
    ];
    $this->moduleHandler->alter('layout_builder_base_left_right_paddings', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getContainerOptions() {
    $options = [
      'layout--container--default' => $this->t('Default'),
      'layout--container--none' => $this->t('None'),
      'layout--container--small' => $this->t('Small'),
      'layout--container--large' => $this->t('Large'),
    ];
    $this->moduleHandler->alter('layout_builder_base_container', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getContentContainerOptions() {
    $options = [
      'layout--content-container--none' => $this->t('None'),
      'layout--content-container--small' => $this->t('Small'),
      'layout--content-container--default' => $this->t('Default'),
      'layout--content-container--large' => $this->t('Large'),
    ];
    $this->moduleHandler->alter('layout_builder_base_content_container', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHeightOptions() {
    $options = [
      'layout--height--default' => $this->t('Default'),
      'layout--height--100vh' => $this->t('100vh'),
      'layout--height--80vh' => $this->t('80vh'),
    ];
    $this->moduleHandler->alter('layout_builder_base_height', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getColorsOptions() {
    $options = [
      'layout--color--default' => $this->t('Default'),
      'layout--color--black' => $this->t('Black'),
      'layout--color--white' => $this->t('White'),
      'layout--color--grey' => $this->t('Grey'),
    ];
    $this->moduleHandler->alter('layout_builder_base_color', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAlignmentOptions() {
    $options = [
      '' => $this->t('Default'),
      'layout--alignment--left' => $this->t('Left'),
      'layout--alignment--right' => $this->t('Right'),
      'layout--alignment--center' => $this->t('Center'),
      'layout--alignment--justify' => $this->t('Justify'),
    ];
    $this->moduleHandler->alter('layout_builder_base_alignment', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getModifiersOptions() {
    $options = [];
    $this->moduleHandler->alter('layout_builder_base_modifiers', $options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function enableImageBackground() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['#attached']['library'][] = 'layout_builder_base_library/layout-builder-base-library';
    return $build;
  }

}
