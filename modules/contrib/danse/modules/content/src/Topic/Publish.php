<?php

namespace Drupal\danse_content\Topic;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The Publish topic class.
 *
 * @package Drupal\danse_content\Topic
 */
class Publish extends TopicBase {

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return self::PUBLISH;
  }

  /**
   * {@inheritdoc}
   */
  public function actionForLabel(): string {
    return (string) $this->t('published');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormLabel(): TranslatableMarkup {
    return $this->t('Publish');
  }

  /**
   * {@inheritdoc}
   */
  public function accessToDefaultSettings(): bool {
    return FALSE;
  }

}
