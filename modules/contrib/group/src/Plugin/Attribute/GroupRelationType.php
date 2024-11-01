<?php

namespace Drupal\group\Plugin\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Plugin\Group\Relation\GroupRelationType as GroupRelationTypeDefinition;

/**
 * Defines a group relation type for plugin discovery.
 *
 * Group relation type plugins use an object-based annotation method, rather
 * than an array-type (as commonly used on other plugin types).
 *
 * The attribute properties of group relation types are found on
 * \Drupal\group\Plugin\Group\Relation\GroupRelationType and are accessed
 * using get/set methods defined in
 * \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class GroupRelationType extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly string $entity_type_id,
    public readonly TranslatableMarkup $label,
    public readonly TranslatableMarkup $description,
    public readonly ?TranslatableMarkup $reference_label = NULL,
    public readonly ?TranslatableMarkup $reference_description = NULL,
    public readonly string|false $entity_bundle = FALSE,
    public readonly string|false $shared_bundle_class = FALSE,
    public readonly bool $entity_access = FALSE,
    public readonly string|false $admin_permission = FALSE,
    public readonly string $pretty_path_key = 'content',
    public readonly bool $enforced = FALSE,
    public readonly bool $code_only = FALSE,
    public readonly ?string $deriver = NULL,
    public readonly array $additional = [],
  ) {}

  /**
   * {@inheritdoc}
   */
  public function get(): array|object {
    $to_filter = get_object_vars($this) + [
      'class' => $this->getClass(),
      'provider' => $this->getProvider(),
    ];

    $values = array_filter($to_filter, function ($value, $key) {
      return !($value === NULL && ($key === 'deriver' || $key === 'provider'));
    }, ARRAY_FILTER_USE_BOTH);

    return new GroupRelationTypeDefinition($values);
  }

}
