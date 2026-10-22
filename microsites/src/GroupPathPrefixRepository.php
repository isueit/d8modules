<?php

declare(strict_types=1);

namespace Drupal\microsites;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\microsites\Trait\PathPrefixMatcherTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves a group from a URL path using node path aliases.
 *
 * Groups themselves hold no Drupal path alias. Instead, a group's URL
 * "prefix" is the path alias of its homepage node (MICROSITES_HOMEPAGE_BUNDLE).
 * Other nodes in the group receive aliases under that prefix.
 */
final class GroupPathPrefixRepository implements GroupPathPrefixRepositoryInterface {

  use PathPrefixMatcherTrait;

  /**
   * Route name patterns that identify a primary entity from the route.
   */
  private static array $recognizedRouteNamePatterns = [
    'entity.*.canonical',
    'entity.*.edit_form',
    'entity.*.delete_form',
    'entity.*.clone_form',
    'entity.*.revision',
    'entity.*.revision_revert_form',
    'entity.*.version_history',
    'entity.*.revision_delete_form',
    'entity.*.content_translation_overview',
    'entity.*.content_translation_add',
    'entity.*.content_translation_edit',
    'entity.*.content_translation_delete',
    'layout_builder.overrides.*.view',
  ];

  public function __construct(
    private readonly Connection $database,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityFieldManagerInterface $entityFieldManager,
    private readonly EntityRepositoryInterface $entityRepository,
    private readonly RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   *
   * Queries the path_alias table for node aliases that are prefixes of the
   * request path, then resolves the owning group via group relationships.
   */
  public function getGroupByRequestPathPrefix(string $request_path): ?GroupInterface {
    $request_path = rtrim($request_path, '/');

    // Find all node path aliases where the alias string appears at position 1
    // of the request path (i.e., is a leading prefix).
    $results = $this->database
      ->select('path_alias', 'pa')
      ->fields('pa', ['path', 'alias'])
      ->condition('pa.path', '/node/%', 'LIKE')
      ->condition('pa.status', 1)
      ->where("INSTR(:request_path, pa.alias) = 1", [':request_path' => $request_path])
      ->execute()
      ?->fetchAll();

    if (empty($results)) {
      return NULL;
    }

    // Build parallel arrays so matchPathPrefix can return an index.
    $haystack = [];
    $system_paths = [];
    foreach ($results as $row) {
      $haystack[]     = $row->alias;
      $system_paths[] = $row->path;
    }

    $best = self::matchPathPrefix($request_path, $haystack);
    if ($best === FALSE) {
      return NULL;
    }

    // Extract node ID from '/node/{nid}'.
    $nid = (int) substr($system_paths[$best], 6);
    if ($nid <= 0) {
      return NULL;
    }

    $relationships = $this->entityTypeManager
      ->getStorage('group_relationship')
      ->loadByProperties(['entity_id' => $nid]);

    if (empty($relationships)) {
      return NULL;
    }

    return reset($relationships)->getGroup();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupByInternalPath(string $path, ?BubbleableMetadata $bubbleable_metadata = NULL): ?GroupInterface {
    if (is_null($bubbleable_metadata)) {
      $bubbleable_metadata = new BubbleableMetadata();
    }
    if (UrlHelper::isExternal($path)) {
      return NULL;
    }
    $url = Url::fromUri('internal:' . $path);
    if (!$url->isRouted()) {
      $bubbleable_metadata->addCacheContexts(['url']);
      return NULL;
    }
    $bubbleable_metadata->addCacheContexts(['route.name']);

    $entity_type_id = self::getRecognizedRouteNameEntityTypeId($url->getRouteName()) ?? 'group';
    $entity_id      = $url->getRouteParameters()[$entity_type_id] ?? NULL;
    $bubbleable_metadata->addCacheContexts(['route']);

    if (empty($entity_id)) {
      return NULL;
    }

    $entity = $this->entityRepository->getCanonical($entity_type_id, $entity_id);
    if (!$entity instanceof EntityInterface) {
      return NULL;
    }
    $bubbleable_metadata->addCacheableDependency($entity);

    if ($entity instanceof GroupInterface) {
      return $entity;
    }

    $relationships = GroupRelationship::loadByEntity($entity);

    // Prefer the group already identified from the request path prefix.
    $request = $this->requestStack->getCurrentRequest();
    if ($request instanceof Request) {
      $group = $this->getGroupByRequestPathPrefix($request->getPathInfo());
      if ($group instanceof GroupInterface) {
        foreach ($relationships as $relationship) {
          if ($relationship->getGroupId() === $group->id()) {
            $bubbleable_metadata->addCacheableDependency($group);
            return $group;
          }
        }
      }
    }

    $group_relationship = array_shift($relationships);
    if (!$group_relationship instanceof GroupRelationshipInterface) {
      return NULL;
    }
    return $group_relationship->getGroup();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPrefixSites(): array {
    // Collect unique group IDs that have at least one node with a path alias.
    $nid_results = $this->database
      ->select('path_alias', 'pa')
      ->fields('pa', ['path'])
      ->condition('pa.path', '/node/%', 'LIKE')
      ->condition('pa.status', 1)
      ->execute()
      ?->fetchCol();

    if (empty($nid_results)) {
      return [];
    }

    $nids = array_filter(
      array_map(static fn($p) => (int) substr($p, 6), $nid_results)
    );

    if (empty($nids)) {
      return [];
    }

    $ids = $this->entityTypeManager
      ->getStorage('group_relationship')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_id', $nids, 'IN')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $group_ids = [];
    foreach (
      $this->entityTypeManager
        ->getStorage('group_relationship')
        ->loadMultiple($ids) as $rel
    ) {
      $group_ids[$rel->getGroupId()] = TRUE;
    }

    return $this->entityTypeManager
      ->getStorage('group')
      ->loadMultiple(array_keys($group_ids));
  }

  /**
   * Returns the entity type ID for recognized route name patterns, or NULL.
   */
  private static function getRecognizedRouteNameEntityTypeId(string $route_name): ?string {
    $is_pattern_match = static function (string $route_name, string $pattern): bool {
      $route_parts   = explode('.', $route_name);
      $pattern_parts = explode('.', $pattern);
      if (count($route_parts) !== count($pattern_parts)) {
        return FALSE;
      }
      foreach ($route_parts as $index => $route_part) {
        if ($pattern_parts[$index] !== '*' && $route_part !== $pattern_parts[$index]) {
          return FALSE;
        }
      }
      return TRUE;
    };

    foreach (self::$recognizedRouteNamePatterns as $pattern) {
      $entity_type_id_index = array_search('*', explode('.', $pattern), TRUE);
      assert(is_int($entity_type_id_index));
      if ($is_pattern_match($route_name, $pattern)) {
        return explode('.', $route_name)[$entity_type_id_index];
      }
    }
    return NULL;
  }

}
