<?php

namespace Drupal\tavily\Plugin\AiAutomatorType;

use Drupal\ai_automator\PluginBaseClasses\ExternalBase;
use Drupal\ai_automator\PluginInterfaces\AiAutomatorTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tavily\TavilyApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base for context.
 */
class TavilySearchWordToContextBase extends ExternalBase implements AiAutomatorTypeInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public $title = 'Tavily: Search Word to Summary';

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
  public function extraAdvancedFormFields(ContentEntityInterface $entity, FieldDefinitionInterface $fieldDefinition, FormStateInterface $formState, array $defaultValues = []) {
    $form['automator_tavily_concatenate'] = [
      '#type' => 'checkbox',
      '#title' => 'Concatenate',
      '#description' => $this->t('If checked, the results will be concatenated into one field.'),
      '#default_value' => $defaultValues['automator_tavily_concatenate'] ?? FALSE,
      '#weight' => 24,
    ];

    return $form;
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
        if ($automatorConfig['tavily_concatenate']) {
          if (!isset($values[0])) {
            $values[0] = $result['title'] . $this->rowBreaker() . $result['content'];
          }
          else {
            $values[0] .= $this->rowBreaker() . $this->rowBreaker() . $result['title'] . $this->rowBreaker() . $result['content'];
          }
        }
        else {
          $values[] = $result['title'] . $this->rowBreaker() . $result['content'];
        }
      }
    }
    return $values;
  }

  /**
   * Breaker.
   *
   * @return string
   *   The row breaker.
   */
  public function rowBreaker() {
    return "\n";
  }

}
