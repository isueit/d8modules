<?php

namespace Drupal\microsites\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Entity\GroupInterface;
use Drupal\microsites\Context\GroupContextPathPrefix;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the current group entity in a configurable view mode.
 *
 * Resolves the group via the microsites URL path-prefix context, so this block
 * works on any page type (nodes, views, etc.) within the group's URL prefix —
 * not only on /group/{id} routes.
 *
 * Template convention (most specific wins):
 *   group--{bundle}--{view-mode}.html.twig
 *
 * @Block(
 *   id = "microsites_group_view_block",
 *   admin_label = @Translation("Microsite Group View Block"),
 * )
 */
#[Block(
  id: 'microsites_group_view_block',
  admin_label: new TranslatableMarkup('Microsite Group View Block'),
)]
class GroupViewBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  protected EntityDisplayRepositoryInterface $entityDisplayRepository;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected GroupContextPathPrefix $pathPrefixContext;

  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    EntityDisplayRepositoryInterface $entity_display_repository,
    EntityTypeManagerInterface $entity_type_manager,
    GroupContextPathPrefix $path_prefix_context,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeManager       = $entity_type_manager;
    $this->pathPrefixContext        = $path_prefix_context;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager'),
      $container->get('microsites.path_prefix_context'),
    );
  }

  protected function resolveGroup(): array {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['url.path']);

    try {
      $contexts = $this->pathPrefixContext->getRuntimeContexts(['group']);
    }
    catch (\Throwable $e) {
      return ['group' => NULL, 'cache' => $cache];
    }

    if (empty($contexts['group'])) {
      return ['group' => NULL, 'cache' => $cache];
    }

    $context = $contexts['group'];
    $cache->addCacheableDependency($context);

    try {
      $group = $context->getContextValue();
    }
    catch (\Throwable $e) {
      return ['group' => NULL, 'cache' => $cache];
    }

    return [
      'group' => $group instanceof GroupInterface ? $group : NULL,
      'cache' => $cache,
    ];
  }

  public function defaultConfiguration(): array {
    return ['view_mode' => 'full'] + parent::defaultConfiguration();
  }

  public function blockForm($form, FormStateInterface $form_state): array {
    $form       = parent::blockForm($form, $form_state);
    $config     = $this->getConfiguration();
    $view_modes = $this->entityDisplayRepository->getViewModes('group');
    $options    = ['full' => $this->t('Full content')];
    foreach ($view_modes as $id => $view_mode) {
      $options[$id] = $view_mode['label'];
    }
    $form['view_mode'] = [
      '#type'          => 'select',
      '#title'         => $this->t('View mode'),
      '#description'   => $this->t('Template convention: <code>group--{bundle}--{view-mode}.html.twig</code>'),
      '#options'       => $options,
      '#default_value' => $config['view_mode'] ?? 'full',
    ];
    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->setConfigurationValue('view_mode', $form_state->getValue('view_mode'));
  }

  public function build(): array {
    ['group' => $group, 'cache' => $cache] = $this->resolveGroup();

    if (!$group) {
      $build = [];
      $cache->applyTo($build);
      return $build;
    }

    $view_mode = $this->getConfiguration()['view_mode'] ?: 'full';
    $build     = $this->entityTypeManager
      ->getViewBuilder('group')
      ->view($group, $view_mode);
    $cache->applyTo($build);
    return $build;
  }

  public function getCacheContexts(): array {
    return array_merge(parent::getCacheContexts(), ['url.path']);
  }

  public function getCacheTags(): array {
    ['group' => $group] = $this->resolveGroup();
    return $group ? $group->getCacheTags() : parent::getCacheTags();
  }

  public function access(AccountInterface $account, $return_as_object = FALSE) {
    ['group' => $group] = $this->resolveGroup();
    if ($group) {
      return $group->access('view', $account, $return_as_object);
    }
    return parent::access($account, $return_as_object);
  }

}
