<?php

namespace Drupal\tavily\Plugin\AiAutomatorType;

use Drupal\ai_automator\Attribute\AiAutomatorType;
use Drupal\ai_automator\PluginBaseClasses\ExternalBase;
use Drupal\ai_automator\PluginInterfaces\AiAutomatorTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\tavily\TavilyApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The rules for a link field.
 */
#[AiAutomatorType(
  id: 'tavily_search_word_to_link',
  label: new TranslatableMarkup('Tavily Search Word to Link'),
  field_rule: 'link',
  target: '',
)]
class TavilySearchWordToLink extends ExternalBase implements AiAutomatorTypeInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public $title = 'Tavily Search Word to Link';

  /**
   * The Tavily API.
   */
  public TavilyApi $tavilyApi;

  /**
   * The constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TavilyApi $tavilyApi) {
    $this->tavilyApi = $tavilyApi;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tavily.api')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function placeholderText() {
    return "{{ context }}";
  }

  /**
   * {@inheritDoc}
   */
  public function generate(ContentEntityInterface $entity, FieldDefinitionInterface $fieldDefinition, array $automatorConfig) {
    $values = [];
    $prompts = parent::generate($entity, $fieldDefinition, $automatorConfig);
    foreach ($prompts as $prompt) {
      $results = $this->tavilyApi->search($prompt);
      foreach ($results['results'] as $result) {
        $values[] = [
          'uri' => $result['url'],
        ];
      }
    }
    return $values;
  }

  /**
   * {@inheritDoc}
   */
  public function verifyValue(ContentEntityInterface $entity, $value, FieldDefinitionInterface $fieldDefinition, array $automatorConfig) {
    // Check so the value is a valid url.
    if (isset($value['uri']) && filter_var($value['uri'], FILTER_VALIDATE_URL)) {
      return TRUE;
    }
    return FALSE;
  }

}
