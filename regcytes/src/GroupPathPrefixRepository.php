<?php

declare(strict_types=1);

namespace Drupal\regcytes;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\regcytes\Entity\GroupPathPrefix;
use Drupal\regcytes\Trait\PathPrefixMatcherTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Loads a group by various strategies.
 */
final class GroupPathPrefixRepository implements GroupPathPrefixRepositoryInterface {

  use PathPrefixMatcherTrait;

  /**
   * An array or recognized route name patterns.
   *
   * Patterns contain a single wildcard ("*") that should be an entity type
   * machine name.
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
   */
  public function getGroupByRequestPathPrefix(string $request_path): ?GroupInterface {
    // Normalize trailing slash vs. no slash.
    $request_path = \rtrim($request_path, '/');
    // Build and execute a database query to find a group that matches the
    // request path.
    $results = $this->buildPathQuery($request_path)->execute();
    if (!\is_null($results)) {
      $matches = $results->fetchAllKeyed();
      // Of all the prefixes which appear at the start of the request path, find
      // the best match.
      $group_id = self::matchPathPrefix($request_path, $matches);
      if ($group_id === FALSE) {
        return NULL;
      }
      return Group::load($group_id);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupByInternalPath(string $path, ?BubbleableMetadata $bubbleable_metadata = NULL): ?GroupInterface {
    if (\is_null($bubbleable_metadata)) {
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
    // Is this an entity route? E.g., "entity.node.canonical". If the route is
    // an entity route, its path can have a prefix if the primary entity for
    // that route belongs to a group or is itself a group. This somewhat
    // optimistically falls back to 'group' route parameter so Views arguments
    // or any other routes that use a "group" parameter are supported.
    $entity_type_id = self::getRecognizedRouteNameEntityTypeId($url->getRouteName()) ?? 'group';
    $entity_id = $url->getRouteParameters()[$entity_type_id] ?? NULL;
    $bubbleable_metadata->addCacheContexts(['route']);
    if (empty($entity_id)) {
      return NULL;
    }
    // @todo should this use getActive() instead of getCanonical()?
    $entity = $this->entityRepository->getCanonical($entity_type_id, $entity_id);
    if (!$entity instanceof EntityInterface) {
      return NULL;
    }
    $bubbleable_metadata->addCacheableDependency($entity);
    if ($entity instanceof GroupInterface) {
      return $entity;
    }
    $relationships = GroupRelationship::loadByEntity($entity);
    // Filter relationships by group in the URL.
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

    $group_relationship = \array_shift($relationships);
    if (!$group_relationship instanceof GroupRelationshipInterface) {
      return NULL;
    }
    return $group_relationship->getGroup();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPrefixSites(): array {
    $results = $this->buildGroupQuery()->execute();
    if (!\is_null($results)) {
      return $this->entityTypeManager->getStorage('group')
        ->loadMultiple(\array_keys($results->fetchAllKeyed()));
    }
    return [];
  }

  /**
   * Gets an entity type ID if the route name is recognized.
   *
   * @param string $route_name
   *   The route name.
   *
   * @return string|null
   *   An entity type ID or NULL if the route name is not in the set of
   *   recognized route names.
   */
  private static function getRecognizedRouteNameEntityTypeId(string $route_name): ?string {
    $is_pattern_match = static function (string $route_name, string $pattern): bool {
      $route_parts = \explode('.', $route_name);
      $pattern_parts = \explode('.', $pattern);
      if (\count($route_parts) !== \count($pattern_parts)) {
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
      $entity_type_id_index = \array_search('*', \explode('.', $pattern), TRUE);
      \assert(\is_int($entity_type_id_index));
      if ($is_pattern_match($route_name, $pattern)) {
        return \explode('.', $route_name)[$entity_type_id_index];
      }
    }
    return NULL;
  }

  /**
   * Builds a database query to find group IDs with a matching path prefix.
   *
   * @param string $request_path
   *   The incoming request path.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A query returning the group ID and prefix for all group prefixes whose
   *   string value is at the beginning of the request path string.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function buildPathQuery(string $request_path): SelectInterface {
    $query = $this->queryBase();
    // Only return rows where the group's path prefix is at the beginning of
    // the request path, i.e., where the index of the path prefix string is at
    // position 1 in the request path. Note, this is not sufficient to find a
    // match on its own. For the reason described by matchPathPrefix().
    /* @link \Drupal\regcytes\Trait\PathPrefixMatcherTrait::matchPathPrefix() */
    /* @see https://dev.mysql.com/doc/refman/8.0/en/string-functions.html#function_instr. */
    $path_prefix_column = $this->getPathPrefixColumn();
    $query_field = "gfd.{$path_prefix_column}";
    $query->where("INSTR(:request_path, {$query_field}) = 1", [
      ':request_path' => $request_path,
    ]);
    return $query;
  }

  /**
   * Builds a database query to find group IDs with a path prefix.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A query returning the group ID and prefix for all groups with prefixes.
   */
  private function buildGroupQuery(): SelectInterface {
    $query = $this->queryBase();
    // Only return rows where the group's path prefix is at the beginning of
    // the request path, i.e., where the index of the path prefix string is at
    // position 1 in the request path. Note, this is not sufficient to find a
    // match on its own. For the reason described by matchPathPrefix().
    /* @link \Drupal\regcytes\Trait\PathPrefixMatcherTrait::matchPathPrefix() */
    /* @see https://dev.mysql.com/doc/refman/8.0/en/string-functions.html#function_instr. */
    $path_prefix_column = $this->getPathPrefixColumn();
    $query_field = "gfd.{$path_prefix_column}";
    return $query->isNotNull($query_field);
  }

  /**
   * Builds a base database query to find group IDs.
   *
   * Does not use an entity query because this query executes in the critical
   * path, which would require loading all the selected group IDs in order to
   * get the path field value.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A query returning the group ID and prefix for all group prefixes
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function queryBase(): SelectInterface {
    $entity_type = $this->entityTypeManager->getDefinition('group');
    \assert($entity_type instanceof EntityTypeInterface);
    // Which table and column has the group path prefix in it?
    $field_data_table = $this->getPathPrefixTable();
    $path_prefix_column = $this->getPathPrefixColumn();
    // Which column stores the group ID?
    $id = $entity_type->getKey('id');
    // Build the base query for group IDs and path prefixes.
    return $this->database
      ->select($field_data_table, 'gfd')
      ->fields('gfd', [$id, $path_prefix_column]);
  }

  /**
   * Get the path prefix database table name.
   */
  private function getPathPrefixTable(): string {
    $tableAndColumn = $this->getPathPrefixTableAndColumn();
    return \reset($tableAndColumn);
  }

  /**
   * Get the path prefix database column name.
   */
  private function getPathPrefixColumn(): string {
    $tableAndColumn = $this->getPathPrefixTableAndColumn();
    return \array_pop($tableAndColumn);
  }

  /**
   * Determines the names of the group field data table and path prefix column.
   *
   * @return array
   *   A keyed array whose "entity_data_table" key's value is the group field
   *   data table name and whose "value_column_name" is the column name for the
   *   group's path prefix field value property.
   */
  private function getPathPrefixTableAndColumn(): array {
    $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions('group');
    $path_prefix_field_definition = $base_field_definitions[GroupPathPrefix::FIELD_NAME];
    \assert($path_prefix_field_definition instanceof BaseFieldDefinition);
    $storage_handler = $this->entityTypeManager->getStorage('group');
    \assert($storage_handler instanceof SqlEntityStorageInterface);
    $table_mapping = $storage_handler->getTableMapping();
    \assert($table_mapping instanceof DefaultTableMapping);
    return [
      'entity_data_table' => $table_mapping->getDataTable(),
      //'value_column_name' => $table_mapping->getFieldColumnName($path_prefix_field_definition, 'value'),
// BDW - Not sure if this is the right thing to do, but it works
      'value_column_name' => 'label',
    ];
  }

}
