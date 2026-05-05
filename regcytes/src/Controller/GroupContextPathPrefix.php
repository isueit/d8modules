<?php

declare(strict_types=1);

namespace Drupal\regcytes\Context;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\regcytes\GroupPathPrefixRepositoryInterface;
use Drupal\regcytes\Trait\PathPrefixMatcherTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a plugin context which provides a group based on a URL path prefix.
 *
 * The context's machine name is: regcytes.path_prefix_context.
 */
final class GroupContextPathPrefix implements ContextProviderInterface {

  use StringTranslationTrait;
  use PathPrefixMatcherTrait;

  /**
   * Constructs a new GroupContextPathPrefix object.
   */
  public function __construct(
    private readonly RequestStack $requestStack,
    private readonly CurrentRouteMatch $routeMatch,
    private readonly GroupPathPrefixRepositoryInterface $prefixRepository,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids): array {
    // Get the current request path.
    $request_path = $this->requestStack->getCurrentRequest()?->getPathInfo();
    $route_match = $this->routeMatch->getCurrentRouteMatch();
    \assert(\is_string($request_path));
    $cacheability = new BubbleableMetadata();
    $group = $this->prefixRepository->getGroupByRequestPathPrefix($request_path);
    if (\is_null($group) && !\is_null($route_match->getRouteObject())) {
      $internal_path = Url::fromRouteMatch($route_match)->toString();
      $group = $this->prefixRepository->getGroupByInternalPath($internal_path, $cacheability);
    }
    $context_definition = EntityContextDefinition::fromEntityTypeId('group');
    $context_definition->setRequired(FALSE);
    $context = new Context($context_definition, $group);
    $context->addCacheableDependency($cacheability->addCacheContexts(['url.path']));
    return ['group' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts(): array {
    $context = EntityContext::fromEntityTypeId('group', (string) $this->t('Group from URL path prefix (regcytes)'));
    $definition = $context->getContextDefinition();
    $definition->setDescription('Returns a group based on the URL path prefix, if one can be identified.');
    return ['group' => $context];
  }

}
