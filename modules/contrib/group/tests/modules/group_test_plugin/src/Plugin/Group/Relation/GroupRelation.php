<?php

namespace Drupal\group_test_plugin\Plugin\Group\Relation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Plugin\Attribute\GroupRelationType;
use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a group relation type for groups.
 */
#[GroupRelationType(
  id: 'group_relation',
  entity_type_id: 'group',
  label: new TranslatableMarkup('Subgroup'),
  description: new TranslatableMarkup('Relates groups to groups as subgroups.'),
  reference_label: new TranslatableMarkup('Group name'),
  reference_description: new TranslatableMarkup('The name of the group you want to relate to the group'),
  entity_bundle: 'default',
  pretty_path_key: 'subgroup'
)]
class GroupRelation extends GroupRelationBase {
}
