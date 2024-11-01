<?php

namespace Drupal\danse_content\Topic;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The Create topic class.
 *
 * @package Drupal\danse_content\Topic
 */
class Create extends TopicBase {

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return self::CREATE;
  }

  /**
   * {@inheritdoc}
   */
  public function actionForLabel(): string {
    return (string) $this->t('created');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormLabel(): TranslatableMarkup {
    return $this->t('Create');
  }

}
