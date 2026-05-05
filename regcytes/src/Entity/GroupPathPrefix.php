<?php

declare(strict_types=1);

namespace Drupal\regcytes\Entity;

use Drupal\group\Entity\GroupInterface;

/**
 * Type-hinted getter for the group path prefix base field added by this module.
 */
final class GroupPathPrefix {

  /**
   * The machine name of the base field that stores a group's path prefix.
   */
  public const FIELD_NAME = 'url_alias';
  //public const FIELD_NAME = 'path';

  /**
   * Gets a group's path prefix, with type hints.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group with a path prefix.
   *
   * @return string
   *   The path prefix or NULL if one has not been defined for the group.
   */
  public static function get(GroupInterface $group): ?string {
    return $group->get(self::FIELD_NAME)->getString() ?: NULL;
  }

}
