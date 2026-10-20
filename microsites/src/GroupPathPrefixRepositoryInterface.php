<?php

declare(strict_types=1);

namespace Drupal\microsites;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\GroupInterface;

/**
 * Loads a group by various path-based strategies.
 */
interface GroupPathPrefixRepositoryInterface {

  /**
   * Gets the group whose homepage node's alias is a prefix of the request path.
   *
   * @param string $request_path
   *   The incoming request path (e.g. /my-site/about).
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The best-matching group, or NULL if none found.
   */
  public function getGroupByRequestPathPrefix(string $request_path): ?GroupInterface;

  /**
   * Gets the group that owns an internal Drupal path via entity relationships.
   *
   * @param string $path
   *   An internal Drupal path (e.g. /node/42).
   * @param \Drupal\Core\Render\BubbleableMetadata|null $bubbleable_metadata
   *   Optional cacheability metadata collector.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The owning group, or NULL.
   */
  public function getGroupByInternalPath(string $path, ?BubbleableMetadata $bubbleable_metadata = NULL): ?GroupInterface;

  /**
   * Returns all groups that have at least one node with a path alias.
   *
   * @return \Drupal\group\Entity\GroupInterface[]
   *   Keyed by group ID.
   */
  public function getGroupPrefixSites(): array;

}
