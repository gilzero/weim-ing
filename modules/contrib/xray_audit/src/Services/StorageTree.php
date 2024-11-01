<?php

namespace Drupal\xray_audit\Services;

/**
 * First level of the tree.
 */
class StorageTree {

  /**
   * Root the tree structure.
   *
   * @var \Drupal\xray_audit\Services\StorageNode|null
   */
  protected $root;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->root = NULL;
  }

  /**
   * Get Root.
   *
   * @return \Drupal\xray_audit\Services\StorageNode|null
   *   Storage Node.
   */
  public function getRoot() {
    return $this->root;
  }

  /**
   * Set Root.
   *
   * @param \Drupal\xray_audit\Services\StorageNode $root
   *   Root.
   *
   * @return \Drupal\xray_audit\Services\StorageNode
   *   StorageNode.
   */
  public function setRoot($root) {
    return $this->root = $root;
  }

  /**
   * A group the data by bundle.
   *
   * @return array
   *   Data.
   */
  public function summaryDataByParagraph(): array {
    $data = [];
    $root = $this->root;
    if (!$root instanceof StorageNode) {
      return $data;
    }
    $first_level = $root->getChildren();
    foreach ($first_level as $node) {
      $this->transverseAllNodes($node, $data);
    }
    return $data;
  }

  /**
   * Transverse all nodes.
   *
   * @param \Drupal\xray_audit\Services\StorageNode $node
   *   Node.
   * @param array $data
   *   Data.
   */
  protected function transverseAllNodes($node, &$data) {
    $key = $node->entity . '_' . $node->bundle;
    if (!isset($data[$key])) {
      $data[$key] = [
        'entity' => $node->entity,
        'bundle' => $node->bundle,
        'label' => $node->label,
        'count' => 0,
      ];
    }
    $data[$key]['count'] += $node->count;

    $children = $node->getChildren();
    foreach ($children as $key => $value) {
      $this->transverseAllNodes($value, $data);
    }
  }

}
