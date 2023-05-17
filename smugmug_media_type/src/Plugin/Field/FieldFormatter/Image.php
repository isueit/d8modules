<?php

namespace Drupal\smugmug_media_type\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\smugmug_media_type\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Url;


/**
 * Plugin implementation of the image field formatter.
 *
 * @FieldFormatter(
 *   id = "smugmug_media_type_image",
 *   label = @Translation("Image"),
 *   field_types = {
 *     "smugmug_media_type"
 *   }
 * )
 */
class Image extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The embed provider plugin manager.
   *
   * @var \Drupal\smugmug_media_type\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * The logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;
  
  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;
  
  /**
   * Class constant for linking to content.
   */
  const LINK_CONTENT = 'content';

  /**
   * Class constant for linking to the provider URL.
   */
  const LINK_PROVIDER = 'provider';

  /**
   * Constructs a new instance of the plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\smugmug_media_type\ProviderManagerInterface $provider_manager
   *   The image embed provider manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The logged in user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, $settings, $label, $view_mode, $third_party_settings, ProviderManagerInterface $provider_manager, AccountInterface $current_user, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->providerManager = $provider_manager;
    $this->currentUser = $current_user;
    $this->imageStyleStorage = $image_style_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('smugmug_media_type.provider_manager'),
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $provider = $this->providerManager->loadProviderFromInput($item->value);
      
      //Use media provided alt text if available otherwise use the metadata alt text
      $alt = $item->alt;
      if (!isset($alt) || $alt == '') {
        $alt = $provider->getAltText();
      }
      $element[$delta] = $provider->renderEmbedCode($this->getSetting('image_style'), $alt);
      $element[$delta]['#cache']['contexts'][] = 'user.permissions';

      $element[$delta] = [
        '#type' => 'container',
        '#attributes' => ['class' => [Html::cleanCssIdentifier(sprintf('smugmug-embed-field-provider-%s', $provider->getPluginId()))]],
        'children' => $element[$delta],
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'link_image_to' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['image_style'] = [
      '#title' => $this->t('Image Style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#required' => FALSE,
      '#options' => image_style_options(),
    ];
    $elements['link_image_to'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->getSetting('link_image_to'),
      '#options' => [
        static::LINK_CONTENT => $this->t('Content'),
        static::LINK_PROVIDER => $this->t('Provider URL'),
      ],
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $linked = '';
    if (!empty($this->getSetting('link_image_to'))) {
      $linked = $this->getSetting('link_image_to') == static::LINK_CONTENT ? $this->t(', linked to content') : $this->t(', linked to provider');
    }
    $summary[] = $this->t('Image (@style@linked).', [
      '@style' => $this->getSetting('image_style') ? $this->getSetting('image_style') : $this->t('no image style'),
      '@linked' => $linked,
    ]);
    return $summary;
  }
  
  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $style_id = $this->getSetting('image_style');
    if ($style_id && $style = ImageStyle::load($style_id)) {
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    $style_id = $this->getSetting('image_style');
    if ($style_id && $style = ImageStyle::load($style_id)) {
      if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
        $replacement_id = $this->imageStyleStorage->getReplacementId($style_id);
        if ($replacement_id && ImageStyle::load($replacement_id)) {
          $this->setSetting('image_style', $replacement_id);
          $changed = TRUE;
        }
      }
    }
    return $changed;
  }

  /**
   * Get an instance of the Smugmug field formatter plugin.
   *
   * @param array $settings
   *   The settings to pass to the plugin.
   *
   * @return static
   *   The formatter plugin.
   */
  public static function mockInstance($settings) {
    return \Drupal::service('plugin.manager.field.formatter')->createInstance('smugmug_media_type_image', [
      'settings' => !empty($settings) ? $settings : [],
      'third_party_settings' => [],
      'field_definition' => new FieldConfig([
        'field_name' => 'mock',
        'entity_type' => 'mock',
        'bundle' => 'mock',
      ]),
      'label' => '',
      'view_mode' => '',
    ]);
  }

}
