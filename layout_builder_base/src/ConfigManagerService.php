<?php

namespace Drupal\layout_builder_base;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\layout_builder_base\Form\SettingsForm;

/**
 * Class ConfigManagerService.
 */
class ConfigManagerService implements ConfigManagerServiceInterface {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The layout builder base settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $layoutBuilderBaseSettings;

  /**
   * Constructs a new ConfigManagerService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->layoutBuilderBaseSettings = $config_factory->get('layout_builder_base.settings');
  }

  /**
   * Get the override options from the config by property.
   *
   * @param string $property
   *   The property.
   *
   * @return array
   *   THe override options.
   */
  public function getOverrideOptionsFromPropertyName($property) {
    $options = [];
    $config_name = SettingsForm::getOverrideConfigName($property);
    $config_value = $this->layoutBuilderBaseSettings->get($config_name);
    if (!empty($config_value)) {
      $options_values = explode(PHP_EOL, $config_value);
      foreach ($options_values as $option_value) {
        $data = array_map('trim', explode('|', $option_value));
        $options[$data[0]] = count($data) === 2 ? $data[1] : $data[0];
      }
    }
    return $options;
  }

  /**
   * Function get the options merged with the possible overrides in the config.
   *
   * @param array $options
   *   The default options.
   * @param string $property
   *   The property associated to the options.
   *
   * @return array
   *   The merged options that will be displayed in the interface.
   */
  public function getMergedOptionsWithOverrides(array $options, $property) {
    $override_options = $this->getOverrideOptionsFromPropertyName($property);
    // If the reserved value none value is used, just return no options in
    // order to hide the interface.
    if (in_array('<none>', $override_options)) {
      $options = [];
    }
    else {
      $options = !empty($override_options) ? $override_options : $options;
    }
    return $options;
  }

}

