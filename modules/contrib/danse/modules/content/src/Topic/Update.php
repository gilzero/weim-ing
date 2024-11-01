<?php

namespace Drupal\danse_content\Topic;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The Update topic class.
 *
 * @package Drupal\danse_content\Topic
 */
class Update extends TopicBase {

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return self::UPDATE;
  }

  /**
   * {@inheritdoc}
   */
  public function actionForLabel(): string {
    return (string) $this->t('updated');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormLabel(): TranslatableMarkup {
    return $this->t('Update');
  }

}
