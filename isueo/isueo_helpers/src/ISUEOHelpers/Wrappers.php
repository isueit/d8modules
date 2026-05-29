<?php

namespace Drupal\isueo_helpers\ISUEOHelpers;

use Drupal;

class Wrappers
{
  // Add/Remove Permissions to a Role
  public static function changeRolePermissions(string $role_name, array $permissions, bool $addPermissions)
  {
    $role = Drupal\user\Entity\Role::load($role_name);

    if ($role) {
      foreach ($permissions as $permission) {
        if ($addPermissions) {
          // Grant permission to role if it's not already there
          if (!$role->hasPermission($permission)) {
            $role->grantPermission($permission);
          }
        } else {
          // Revoke permission from role if it's currently there
          if ($role->hasPermission($permission)) {
            $role->revokePermission($permission);
          }
        }
      }
      $role->save();
    }
  }
}
