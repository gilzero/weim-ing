<?php

namespace Drupal\group_test_plugin\Plugin\Group\Relation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Plugin\Attribute\GroupRelationType;
use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a group relation type for test entities.
 */
#[GroupRelationType(
  id: 'entity_test_relation',
  entity_type_id: 'entity_test_with_owner',
  label: new TranslatableMarkup('Group test entity'),
  description: new TranslatableMarkup('Relates test entities to groups.'),
  reference_label: new TranslatableMarkup('Test entity'),
  reference_description: new TranslatableMarkup('The name of the test entity you want to relate to the group'),
  entity_access: TRUE,
  admin_permission: 'administer entity_test_relation',
  pretty_path_key: 'entity_test_with_owner'
)]
class EntityTestRelation extends GroupRelationBase {
}
