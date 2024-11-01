<?php

namespace Drupal\group\Plugin\Group\Relation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Plugin\Attribute\GroupRelationType;

/**
 * Provides a group relation for users as members.
 */
#[GroupRelationType(
  id: 'group_membership',
  entity_type_id: 'user',
  label: new TranslatableMarkup('Group membership'),
  description: new TranslatableMarkup('Adds users to groups as members.'),
  reference_label: new TranslatableMarkup('User'),
  reference_description: new TranslatableMarkup('The user you want to make a member'),
  shared_bundle_class: 'Drupal\group\Entity\GroupMembership',
  admin_permission: 'administer members',
  pretty_path_key: 'member',
  enforced: TRUE
)]
class GroupMembership extends GroupRelationBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other group relations.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    return $form;
  }

}
