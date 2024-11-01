<?php

declare(strict_types=1);

namespace Drupal\monolog\Logger\Handler;

use Drupal\Core\Mail\MailManagerInterface;
use Monolog\Handler\MailHandler;
use Monolog\Level;

/**
 * DrupalMailHandler uses the Drupal's core mail manager to send Log emails.
 */
class DrupalMailHandler extends MailHandler {

  /**
   * The mail address to send the log emails to.
   *
   * @var string
   */
  private string $to;

  /**
   * DrupalMailHandler constructor.
   *
   * @param string $to
   *   The mail address to send the log emails to.
   * @param int|string|\Monolog\Level $level
   *   The minimum logging level at which this handler will be triggered.
   * @param bool $bubble
   *   The bubbling behavior.
   */
  public function __construct(
    string $to,
    int|string|Level $level = Level::Debug,
    bool $bubble = TRUE,
  ) {
    parent::__construct($level, $bubble);

    $this->to = $to;
  }

  /**
   * {@inheritdoc}
   */
  protected function send(string $content, array $records): void {
    // @phpstan-ignore-next-line
    $mail = \Drupal::service('plugin.manager.mail');
    \assert($mail instanceof MailManagerInterface);

    // @phpstan-ignore-next-line
    $default_language = \Drupal::languageManager()->getDefaultLanguage();

    $params = [
      'content' => $content,
      'records' => $records,
    ];
    $mail->mail('monolog', 'default', $this->to, $default_language->getName(),
      $params);
  }

}
