<?php

namespace Drupal\smugmug_media_type;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Gathers the provider plugins.
 */
class ProviderManager extends DefaultPluginManager implements ProviderManagerInterface, MapperInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/smugmug_media_type/Provider', $namespaces, $module_handler, 'Drupal\smugmug_media_type\ProviderPluginInterface', 'Drupal\smugmug_media_type\Annotation\ImageEmbedProvider');
    $this->alterInfo('smugmug_media_type_provider_info');
  }

  /**
   * {@inheritdoc}
   */
  public function getProvidersOptionList() {
    $options = [];
    foreach ($this->getDefinitions() as $definition) {
      $options[$definition['id']] = $definition['title'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefinitionsFromOptionList($options) {
    $definitions = [];
    // When no options are selected, all plugins are applicable.
    if (count(array_keys($options, '0')) == count($options) || empty($options)) {
      return $this->getDefinitions();
    }
    else {
      foreach ($options as $provider_id => $enabled) {
        if ($enabled) {
          $definitions[$provider_id] = $this->getDefinition($provider_id);
        }
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function filterApplicableDefinitions(array $definitions, $user_input) {
    foreach ($definitions as $definition) {
      $is_applicable = $definition['class']::isApplicable($user_input);
      if ($is_applicable) {
        return $definition;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadProviderFromInput($input) {
    $definition = $this->loadDefinitionFromInput($input);
    return $definition ? $this->createInstance($definition['id'], ['input' => $input]) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefinitionFromInput($input) {
    return $this->filterApplicableDefinitions($this->getDefinitions(), $input);
  }

}
