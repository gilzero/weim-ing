<?php

declare(strict_types=1);

namespace Drupal\azure_ai_faq_bot\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block for Azure AI FAQ Bot.
 */
#[Block(
  id: 'azure_ai_faq_bot_block',
  admin_label: new TranslatableMarkup('Azure AI FAQ Bot'),
  category: new TranslatableMarkup('Custom'),
)]
final class WebChatBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    $build = [
      '#markup' => Markup::create('<div id="azure-ai-faq-bot-webchat" role="main"></div>'),
      '#attached' => [
        'library' => [
          'azure_ai_faq_bot/azure_ai_faq_bot',
          'azure_ai_faq_bot/botframework.webchat',
        ],
      ],
    ];

    return $build;
  }

}
