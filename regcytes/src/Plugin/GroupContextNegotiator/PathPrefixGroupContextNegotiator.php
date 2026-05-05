<?php

namespace Drupal\regcytes\Plugin\GroupContextNegotiator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Context\GroupContextNegotiatorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Determines the group context from the URL path prefix.
 *
 * @GroupContextNegotiator(
 *   id = "regcytes_path_prefix",
 *   label = @Translation("Group from URL path prefix (regcytes)"),
 *   description = @Translation("Determines the group context from the URL path prefix."),
 * )
 */
class PathPrefixGroupContextNegotiator extends GroupContextNegotiatorBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The request stack.
   */
  protected RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup(): ?GroupInterface {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return NULL;
    }

    // Get the raw path (e.g. "/my-prefix/some/page").
    $path = $request->getPathInfo();

    // Strip the leading slash and grab the first path segment.
    $parts = explode('/', ltrim($path, '/'));
    $prefix = '/' . $parts[0] ?? '';

    if (empty($prefix)) {
      return NULL;
    }

    // Load a group whose path prefix field matches.
    // Adjust 'field_path_prefix' to your actual field name.
    $groups = $this->entityTypeManager
      ->getStorage('group')
      ->loadByProperties(['url_prefix' => $prefix]);

    return !empty($groups) ? reset($groups) : NULL;
  }

}
