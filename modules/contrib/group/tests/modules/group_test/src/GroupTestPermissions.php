<?php

namespace Drupal\group_test;

/**
 * Provides dynamic permissions for testing.
 */
class GroupTestPermissions {

  /**
   * Returns a list of group permissions.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  public function buildPermissions() {
    return [
      'test group' => [
        'title' => 'Test the group',
      ],
    ];
  }

}
