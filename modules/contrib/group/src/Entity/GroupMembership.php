<?php

namespace Drupal\group\Entity;

/**
 * Shared bundle class for a GroupRelationship entity representing a membership.
 */
class GroupMembership extends SharedBundleClassBase implements GroupMembershipInterface {

  use GroupMembershipTrait;

}
