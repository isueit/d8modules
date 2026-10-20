<?php

declare(strict_types=1);

namespace Drupal\microsites\Context;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\microsites\GroupPathPrefixRepositoryInterface;
use Drupal\microsites\Trait\PathPrefixMatcherTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Context provider that resolves the current group from the URL path prefix.
 */
final class GroupContextPathPrefix implements ContextProviderInterface {

  use StringTranslationTrait;
  use PathPrefixMatcherTrait;

  public function __construct(
    private readonly RequestStack $requestStack,
    private readonly CurrentRouteMatch $routeMatch,
    private readonly GroupPathPrefixRepositoryInterface $prefixRepository,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids): array {
    $request_path = $this->requestStack->getCurrentRequest()?->getPathInfo();
    $route_match  = $this->routeMatch->getCurrentRouteMatch();
    assert(is_string($request_path));

    $cacheability = new BubbleableMetadata();
    $group        = $this->prefixRepository->getGroupByRequestPathPrefix($request_path);

    if (is_null($group) && !is_null($route_match->getRouteObject())) {
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
    $context    = EntityContext::fromEntityTypeId('group', (string) $this->t('Group from URL path prefix (microsites)'));
    $definition = $context->getContextDefinition();
    $definition->setDescription('Returns the group whose homepage node alias is a prefix of the current URL path.');
    return ['group' => $context];
  }

}
