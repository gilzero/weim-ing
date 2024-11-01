<?php

namespace Drupal\danse_content\Topic;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The Unpublish topic class.
 *
 * @package Drupal\danse_content\Topic
 */
class Unpublish extends TopicBase {

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return self::UNPUBLISH;
  }

  /**
   * {@inheritdoc}
   */
  public function actionForLabel(): string {
    return (string) $this->t('unpublished');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormLabel(): TranslatableMarkup {
    return $this->t('Unpublish');
  }

  /**
   * {@inheritdoc}
   */
  public function accessToDefaultSettings(): bool {
    return FALSE;
  }

}
