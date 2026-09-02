<?php

namespace Drupal\porkbot\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Porkbot Chat Widget' block.
 *
 * @Block(
 *   id = "porkbot_widget",
 *   admin_label = @Translation("Porkbot Chat Widget"),
 *   category = @Translation("Widgets"),
 * )
 */
class PorkbotBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly ModuleExtensionList $moduleExtensionList,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('extension.list.module'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'porkbot_widget',
      '#module_path' => $this->moduleExtensionList->getPath('porkbot'),
      '#attached' => [
        'library' => [
          'porkbot/widget',
        ],
      ],
    ];
  }

}
