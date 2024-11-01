<?php

namespace Drupal\danse_content\Topic;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The Delete topic class.
 *
 * @package Drupal\danse_content\Topic
 */
class Delete extends TopicBase {

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return self::DELETE;
  }

  /**
   * {@inheritdoc}
   */
  public function actionForLabel(): string {
    return (string) $this->t('deleted');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormLabel(): TranslatableMarkup {
    return $this->t('Delete');
  }

}
