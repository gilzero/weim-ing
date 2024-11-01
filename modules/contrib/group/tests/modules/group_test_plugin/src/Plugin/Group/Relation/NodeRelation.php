<?php

namespace Drupal\group_test_plugin\Plugin\Group\Relation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Plugin\Attribute\GroupRelationType;
use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a group relation type for nodes.
 */
#[GroupRelationType(
  id: 'node_relation',
  entity_type_id: 'node',
  label: new TranslatableMarkup('Node relation (generic)'),
  description: new TranslatableMarkup('Adds nodes to groups.'),
  entity_access: TRUE,
  deriver: 'Drupal\group_test_plugin\Plugin\Group\Relation\NodeRelationDeriver'
)]
class NodeRelation extends GroupRelationBase {
}
