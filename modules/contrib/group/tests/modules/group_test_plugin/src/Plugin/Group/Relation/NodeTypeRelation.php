<?php

namespace Drupal\group_test_plugin\Plugin\Group\Relation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Plugin\Attribute\GroupRelationType;
use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a group relation type for node types.
 */
#[GroupRelationType(
  id: 'node_type_relation',
  entity_type_id: 'node_type',
  label: new TranslatableMarkup('Node type relation (generic)'),
  description: new TranslatableMarkup('Relates node types to groups.'),
  entity_access: TRUE,
  admin_permission: 'administer node_type_relation'
)]
class NodeTypeRelation extends GroupRelationBase {
}
