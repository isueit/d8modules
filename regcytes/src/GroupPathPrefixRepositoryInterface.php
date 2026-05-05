<?php

declare(strict_types=1);

namespace Drupal\regcytes;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\GroupInterface;

/**
 * Loads a group by various strategies.
 */
interface GroupPathPrefixRepositoryInterface {

  /**
   * Gets the group identified by a request path prefix, if one exists.
   *
   * @param string $request_path
   *   The request path.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The group identified from the request path prefix, or NULL if one could
   *   not be found.
   */
  public function getGroupByRequestPathPrefix(string $request_path): ?GroupInterface;

  /**
   * Gets a group path prefix if the path identifies an entity in a group.
   *
   * @param string $path
   *   The path to check.
   * @param \Drupal\Core\Render\BubbleableMetadata|null $bubbleable_metadata
   *   (optional) A BubbleableMetadata object.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The path prefix or NULL if the path does not have one.
   */
  public function getGroupByInternalPath(string $path, ?BubbleableMetadata $bubbleable_metadata = NULL): ?GroupInterface;

  /**
   * Gets all group sites that have prefixes.
   *
   * @return \Drupal\group\Entity\GroupInterface[]
   *   A list of groups.
   */
  public function getGroupPrefixSites(): array;

}
