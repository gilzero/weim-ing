<?php

namespace Drupal\xray_audit\Services;

/**
 * Storage node class.
 */
class StorageNode {

  /**
   * Level of the node.
   *
   * @var int
   */
  protected $level;

  /**
   * Entity.
   *
   * @var string
   */
  public $entity;

  /**
   * Bundle.
   *
   * @var string
   */
  public $bundle;

  /**
   * Label.
   *
   * @var string
   */
  public $label;

  /**
   * Count.
   *
   * @var int
   */
  public $count;

  /**
   * Children.
   *
   * @var mixed[]
   */
  protected $children;

  /**
   * Constructor.
   *
   * @param string $entity
   *   Entity.
   * @param string $bundle
   *   Bundle.
   */
  public function __construct(string $entity, string $bundle) {
    $this->level = 0;
    $this->entity = $entity;
    $this->bundle = $bundle;
    $this->children = [];
    $this->count = 0;
  }

  /**
   * Add children.
   *
   * @param StorageNode $node
   *   Add children.
   */
  public function addChildren(StorageNode $node) {
    $node->setLevel($this->getLevel() + 1);
    $this->children[] = $node;
  }

  /**
   * Get children.
   *
   * @return array
   *   Children.
   */
  public function getChildren(): array {
    return $this->children;
  }

  /**
   * Set level.
   *
   * @param int $int
   *   Level.
   */
  public function setLevel(int $int) {
    $this->level = $int;
  }

  /**
   * Get level.
   *
   * @return int
   *   Level.
   */
  public function getLevel(): int {
    return !empty($this->level) ? $this->level : 0;
  }

  /**
   * Sum.
   *
   * @param int $int
   *   Sum.
   */
  public function sum($int) {
    $this->count += $int;
  }

  /**
   * Label.
   *
   * @param string $label
   *   Set labels.
   */
  public function setLabel(string $label) {
    $this->label = $label;
  }

  /**
   * Get label.
   *
   * @return string
   *   Label.
   */
  public function getLabel(): string {
    return $this->label;
  }

}
