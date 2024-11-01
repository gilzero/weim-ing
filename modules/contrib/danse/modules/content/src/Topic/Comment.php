<?php

namespace Drupal\danse_content\Topic;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The Comment topic class.
 *
 * @package Drupal\danse_content\Topic
 */
class Comment extends TopicBase {

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return self::COMMENT;
  }

  /**
   * {@inheritdoc}
   */
  public function actionForLabel(): string {
    return (string) $this->t('commented');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormLabel(): TranslatableMarkup {
    return $this->t('Comment');
  }

  /**
   * {@inheritdoc}
   */
  public function accessToDefaultSettings(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function excludedOnEntityTypes(): array {
    return ['comment'];
  }

  /**
   * {@inheritdoc}
   */
  public function dependencies(): array {
    return ['comment'];
  }

}
