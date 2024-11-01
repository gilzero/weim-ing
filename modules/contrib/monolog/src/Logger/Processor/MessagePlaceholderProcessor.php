<?php

declare(strict_types=1);

namespace Drupal\monolog\Logger\Processor;

use Drupal\Core\Logger\LogMessageParserInterface;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Parse and replace message placeholders.
 */
class MessagePlaceholderProcessor implements ProcessorInterface {

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected LogMessageParserInterface $parser;

  /**
   * Construct default object.
   *
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(LogMessageParserInterface $parser) {
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function __invoke(LogRecord $record): LogRecord {
    // Populate the message placeholders and then replace them in the message.
    $message_placeholders = $this
      ->parser
      ->parseMessagePlaceholders(
        $record['message'],
        $record['context'],
      );

    $message = \count($message_placeholders) == 0
      ? $record->message
      : \strtr($record->message, $message_placeholders);

    // Remove the replaced placeholders from the context to prevent logging the
    // same information twice.
    $context = $record->context;
    foreach ($message_placeholders as $placeholder => $value) {
      unset($context[$placeholder]);
    }

    return $record->with(message: $message, context: $context);
  }

}
