<?php

namespace Drupal\ai_provider_aws_bedrock;

use Drupal\ai\OperationType\Chat\StreamedChatMessage;
use Drupal\ai\OperationType\Chat\StreamedChatMessageIterator;

/**
 * AWS Bedrock Chat message iterator.
 */
class BedrockChatMessageIterator extends StreamedChatMessageIterator {

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \Generator {
    foreach ($this->iterator as $data) {
      yield new StreamedChatMessage(
        $data['messageStart']['role'] ?? '',
        $data['contentBlockDelta']['delta']['text'] ?? '',
        $data['usage'] ?? []
      );
    }
  }

}
