<?php
namespace Drupal\smugmug_media_type\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;
use Drupal\smugmug_media_type\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media source plugin for image embed field.
 *
 * @MediaSource(
 *   id = "smugmug_media_type",
 *   label = @Translation("Smugmug embed field"),
 *   description = @Translation("Enables smugmug_media_type integration with media."),
 *   allowed_field_types = {"smugmug_media_type"},
 *   default_thumbnail_filename = "no-thumbnail.png"
 * )
 */
class SmugmugEmbedField extends MediaSourceBase {

  /**
   * The image provider manager.
   *
   * @var \Drupal\smugmug_media_type\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * The media settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $mediaSettings;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   Config field type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\smugmug_media_type\ProviderManagerInterface $provider_manager
   *   The video provider manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, ProviderManagerInterface $provider_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->providerManager = $provider_manager;
    $this->mediaSettings = $config_factory->get('media.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('smugmug_media_type.provider_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => 'field_media_smugmug_media_type',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $url = $this->getImageUrl($media);

    switch ($attribute_name) {
      case 'title':
      case 'default_name':
        if ($provider = $this->providerManager->loadProviderFromInput($url)) {
          return $provider->getName();
        }
        return parent::getMetadata($media, 'default_name');

      case 'id':
        if ($provider = $this->providerManager->loadProviderFromInput($url)) {
          return $provider->getIdFromInput($url);
        }
        return FALSE;
        
      case 'alternative_text':
      case 'alt_text':
        $alt_field = $this->getAltText();
        if ($alt_field && $alt_field != '') {
          return $alt_field;
        } else if ($provider = $this->providerManager->loadProviderFromInput($url)) {
          return $provider->getAltText();
        }

      case 'source':
      case 'source_name':
        $definition = $this->providerManager->loadDefinitionFromInput($url);
        if (!empty($definition)) {
          return $definition['id'];
        }
        return FALSE;

      case 'image_local':
      case 'image_local_uri':
        $thumbnail_uri = $this->getMetadata($media, 'thumbnail_uri');
        if (!empty($thumbnail_uri) && file_exists($thumbnail_uri)) {
          return $thumbnail_uri;
        }
        return parent::getMetadata($media, 'thumbnail_uri');

      case 'thumbnail_uri':
        if ($provider = $this->providerManager->loadProviderFromInput($url)) {
          $provider->downloadThumbnail();
          $thumbnail_uri = $provider->getLocalThumbnailUri();
          if (!empty($thumbnail_uri)) {
            return $thumbnail_uri;
          }
        }
        return parent::getMetadata($media, 'thumbnail_uri');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'id' => $this->t('Image ID.'),
      'title' => $this->t('Resource title'),
      'alt_text' => $this->t('Alternative text'),
      'image_local' => $this->t('Copies thumbnail image to the local filesystem and returns the URI.'),
      'image_local_uri' => $this->t('Gets URI of the locally saved image.'),
    ];
  }

  /**
   * Get the image URL from a media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return string|bool
   *   A image URL or FALSE on failure.
   */
  protected function getImageUrl(MediaInterface $media) {
    $media_type = $this->entityTypeManager
      ->getStorage('media_type')
      ->load($media->bundle());
    $source_field = $this->getSourceFieldDefinition($media_type);
    $field_name = $source_field->getName();
    $image_url = $media->{$field_name}->value;
    return !empty($image_url) ? $image_url : FALSE;
  }
  
  /**
   * Get the image alt text from a media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return string|bool
   *   A image alt text or FALSE on failure.
   */
  protected function getAltText(MediaInterface $media) {
    $media_type = $this->entityTypeManager
      ->getStorage('media_type')
      ->load($media->bundle());
    $source_field = $this->getSourceFieldDefinition($media_type);
    $field_name = $source_field->getName();
    $image_alt = $media->{$field_name}->alt;
    return !empty($image_alt) ? $image_alt : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('label', 'Image Url');
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldDefinition(MediaTypeInterface $type) {
    $field = !empty($this->configuration['source_field']) ? $this->configuration['source_field'] : 'field_media_smugmug_media_type';
    if ($field) {
      // Be sure that the suggested source field actually exists.
      $fields = $this->entityFieldManager->getFieldDefinitions('media', $type->id());
      return isset($fields[$field]) ? $fields[$field] : NULL;
    }
    return NULL;
  }
  
  public function getProviders() {
    return $this->providerManager->getProvidersOptionList();
  }
  
  public function getDefinitions($input) {
    return $this->providerManager->loadDefinitionFromInput($input);
  }

}
