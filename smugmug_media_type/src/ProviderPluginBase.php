<?php

namespace Drupal\smugmug_media_type;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base for the provider plugins.
 */
abstract class ProviderPluginBase extends PluginBase implements ProviderPluginInterface, ContainerFactoryPluginInterface {

  protected $thumbsDirectory = 'public://smugmug_thumbnails';

  protected $imageId;

  protected $input;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Create a plugin with the given input.
   *
   * @param array $configuration
   *   The configuration of the plugin.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *    An HTTP client.
   *
   * @throws \Exception
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!static::isApplicable($configuration['input'])) {
      throw new \Exception('Tried to create a image provider plugin with invalid input.');
    }
    $this->input = $configuration['input'];
    $this->imageId = $this->getIdFromInput($configuration['input']);
    $this->httpClient = $http_client;
  }

  protected function getImageId() {
    return $this->imageId;
  }

  protected function getFileSystem() {
    if (!isset($this->fileSystem)) {
      $this->fileSystem = \Drupal::service('file_system');
    }
    return $this->fileSystem;
  }

  protected function getInput() {
    return $this->input;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input) {
    $id = static::getIdFromInput($input);
    return !empty($id);
  }

  /**
   * {@inheritdoc}
   */
  public function renderThumbnail($image_style, $link_url) {
    $output = [
      '#theme' => 'image',
      '#uri' => $this->getLocalThumbnailUri(),
    ];

    if (!empty($image_style)) {
      $output['#theme'] = 'image_style';
      $output['#style_name'] = $image_style;
    }

    if ($link_url) {
      $output = [
        '#type' => 'link',
        '#title' => $output,
        '#url' => $link_url,
      ];
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function downloadThumbnail() {
    $local_uri = $this->getLocalThumbnailUri();
    if (!file_exists($local_uri)) {
      $this->getFileSystem()->prepareDirectory($this->thumbsDirectory, FileSystemInterface::CREATE_DIRECTORY);
      try {
        $thumbnail = $this->httpClient->request('GET', $this->getRemoteThumbnailUrl());
        $this->getFileSystem()->saveData((string) $thumbnail->getBody(), $local_uri);
      }
      catch (\Exception $e) {
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalThumbnailUri() {
    return $this->thumbsDirectory . '/' . $this->getImageId() . '.jpg';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('http_client'));
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('@provider Image (@id)', ['@provider' => $this->getPluginDefinition()['title'], '@id' => $this->getImageId()]);
  }

}
