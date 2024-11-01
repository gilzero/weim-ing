<?php

namespace Drupal\auto_translation;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ai_translate\Controller\AiTranslateController;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Utility class for auto_translation module functions.
 *
 * @package Drupal\auto_translation
 */
class Utility {

  use StringTranslationTrait;
  use LoggerChannelTrait;

  /**
   * The config object.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The http client object.
   *
   * @var GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The message interface object.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The language interface object.
   *
   * @var Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module handler object.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new Utility object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, MessengerInterface $messenger, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->messenger = $messenger;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Dependency injection via create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('messenger'),
      $container->get('language_manager'),
      $container->get('module_handler')
    );
  }

  /**
   * Translates the given text from the source language to the target language.
   *
   * @param string $text
   *   The text to be translated.
   * @param string $s_lang
   *   The source language code.
   * @param string $t_lang
   *   The target language code.
   *
   * @return string|null
   *   The translated text or NULL if translation fails.
   */
  public function translate($text, $s_lang, $t_lang) {
    $config = $this->configFactory->get('auto_translation.settings');
    $provider = $config->get('auto_translation_provider') ?? 'google';
    $api_enabled = $config->get('auto_translation_api_enabled') ?? NULL;
    $translation = NULL;

    switch ($provider) {
      case 'google':
        $translation = $api_enabled ? $this->translateApiServerCall($text, $s_lang, $t_lang) : $this->translateApiBrowserCall($text, $s_lang, $t_lang);
        break;

      case 'libretranslate':
        $translation = $this->libreTranslateApiCall($text, $s_lang, $t_lang);
        break;

      case 'drupal_ai':
        if ($this->moduleHandler->moduleExists('ai')) {
          $translation = $this->drupalAiTranslateApiCall($text, $s_lang, $t_lang);
        }
        else {
          $this->messenger->addError($this->t('AI translation module is not installed.'));
        }
        break;
    }

    return $translation;
  }

  /**
   * Translates the given text using the API libre translate server.
   *
   * @param string $text
   *   The text to be translated.
   * @param string $s_lang
   *   The source language of the text.
   * @param string $t_lang
   *   The target language for the translation.
   *
   * @return string
   *   The translated text.
   */
  public function libreTranslateApiCall($text, $s_lang, $t_lang) {
    $config = $this->configFactory->get('auto_translation.settings');
    $translation = NULL;
    $endpoint = 'https://libretranslate.com/translate';

    $options = [
      'headers' => ['Content-Type' => 'application/json'],
      'json' => [
        'q' => $text,
        'source' => $s_lang,
        'target' => $t_lang,
        'format' => 'text',
        'api_key' => $config->get('auto_translation_api_key'),
      ],
      'verify' => FALSE,
    ];

    try {
      $response = $this->httpClient->post($endpoint, $options);
      $result = Json::decode($response->getBody()->getContents());
      $translation = $result['translatedText'] ?? NULL;
    }
    catch (RequestException $e) {
      $this->getLogger('auto_translation')->error('Translation API error: @error', ['@error' => $e->getMessage()]);
    }

    return $translation;
  }

  /**
   * Translates the given text using the API Drupal AI translate server.
   *
   * @param string $text
   *   The text to be translated.
   * @param string $s_lang
   *   The source language of the text.
   * @param string $t_lang
   *   The target language for the translation.
   *
   * @return string
   *   The translated text.
   */
  public function drupalAiTranslateApiCall($text, $s_lang, $t_lang) {
    $logger = $this->getLogger('auto_translation');
    $translation = NULL;
    if (!$this->moduleHandler->moduleExists('ai')) {
      $logger->error('Auto translation error: AI Module not installed please install Drupal AI module');
      return [
        '#type' => 'markup',
        '#markup' => $this->t('AI Module not installed please install Drupal AI and Drupal AI Translate modules'),
      ];
    }
    if (!\Drupal::service('ai.provider')->hasProvidersForOperationType('chat', TRUE)) {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('Sorry, no provider exists for Chat, install one first'),
      ];
    }
    $container = $this->getContainer();
    $languageManager = $container->get('language_manager');
    $langFrom = $languageManager->getLanguage($s_lang);
    $langTo = $languageManager->getLanguage($t_lang);
    $aiTranslateController = \Drupal::classResolver(AiTranslateController::class);
    $aiTranslateController = $aiTranslateController::create($container);
    try {
      $translatedText = $aiTranslateController->translateContent($text, $langFrom, $langTo);

      return $translatedText;
    }
    catch (RequestException $exception) {
      $logger->error('Auto translation error: @error', ['@error' => json_encode($exception->getMessage())]);
      $this->getMessages($exception->getMessage());
      return $exception;
    }
    return $translation;
  }

  /**
   * Translates the given text using the API server.
   *
   * @param string $text
   *   The text to be translated.
   * @param string $s_lang
   *   The source language of the text.
   * @param string $t_lang
   *   The target language for the translation.
   *
   * @return string
   *   The translated text.
   */

  /**
   * Calls the Google API to translate text using server-side key.
   */
  public function translateApiServerCall($text, $s_lang, $t_lang) {
    $config = $this->configFactory->get('auto_translation.settings');
    $client = new TranslateClient(['key' => $config->get('auto_translation_api_key')]);
    $translation = NULL;

    try {
      $result = $client->translate($text, ['source' => $s_lang, 'target' => $t_lang]);
      $translation = htmlspecialchars_decode($result['text']);
    }
    catch (RequestException $e) {
      $this->getLogger('auto_translation')->error('Auto translation error: @error', ['@error' => $e->getMessage()]);
    }

    return $translation;
  }

  /**
   * Translates the given text using the API browser call.
   *
   * @param string $text
   *   The text to be translated.
   * @param string $s_lang
   *   The source language of the text.
   * @param string $t_lang
   *   The target language for the translation.
   *
   * @return string
   *   The translated text.
   */
  public function translateApiBrowserCall($text, $s_lang, $t_lang) {
    $translation = NULL;
    $endpoint = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=' . $s_lang . '&tl=' . $t_lang . '&dt=t&q=' . rawurlencode($text);
    $options = ['verify' => FALSE];

    try {
      $response = $this->httpClient->get($endpoint, $options);
      $data = Json::decode($response->getBody()->getContents());

      $translation = '';
      foreach ($data[0] as $segment) {
        $translation .= $segment[0];
      }
    }
    catch (RequestException $e) {
      $this->getLogger('auto_translation')->error('Translation API error: @error', ['@error' => $e->getMessage()]);
    }

    return $translation;
  }

  /**
   * Custom function to return saved resources.
   */
  public function getEnabledContentTypes() {
    $config = $this->config();
    $enabledContentTypes = $config->get('auto_translation_content_types') ? $config->get('auto_translation_content_types') : NULL;
    return $enabledContentTypes;
  }

  /**
   * Retrieves the excluded fields.
   *
   * @return array
   *   The excluded fields.
   */
  public function getExcludedFields() {
    $config = $this->config();
    $excludedFields = [
      'uuid',
      'und',
      'published',
      'unpublished',
      '0',
      '1',
      'behavior_settings',
      'draft',
      'ready for review',
      'language',
      'parent_type',
      'parent_field_name',
      'boolean',
      'created',
      'changed',
      'datetime',
      'path',
      'code',
      NULL,
    ];
    $excludedFieldsSettings = $config->get('auto_translation_excluded_fields') ? $config->get('auto_translation_excluded_fields') : NULL;
    if ($excludedFieldsSettings) {
      $excludedFieldsSettings = explode(",", $excludedFieldsSettings);
      $excludedFields = array_merge($excludedFields, $excludedFieldsSettings);
    }
    return $excludedFields;
  }

  /**
   * Implements auto translation form function.
   */
  public function formTranslate($form, $form_state) {
    $current_path = \Drupal::service('path.current')->getPath();
    $enabledContentTypes = $this->getEnabledContentTypes();
    if ($form_state->getFormObject() instanceof EntityForm) {
      $entity = $form_state->getFormObject()->getEntity();
    }
    else {
      $entity = $form_state->getFormObject()->entity;
    }
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $arrCheck = $this->getExcludedFields();
    $container = $this->getContainer();
    $languageManager = $container->get('language_manager');
    if ($enabledContentTypes && strpos($current_path, 'translations/add') !== FALSE && in_array($bundle, $enabledContentTypes)) {
      $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
      $t_lang = $entity->langcode->value;
      $d_lang = $languageManager->getDefaultLanguage()->getId();

      foreach ($fields as $field) {
        $field_name = $field->getName();
        $field_type = $field->getType();
        if ((strpos($field_type, 'entity_reference') !== FALSE ||
        strpos($field_type, 'entity_reference_revision') !== FALSE) &&
        !$field->isTranslatable()) {

          // Check if the field is a reference to paragraphs.
          if ($field->getSetting('target_type') == 'paragraph') {
            $paragraphs = $entity->get($field_name);
            foreach ($paragraphs as $paragraph) {
              $paragraphEntity = $paragraph->entity;

              if ($paragraphEntity instanceof \Drupal\paragraphs\ParagraphInterface) {
                // Check if the paragraph already has the translation.
                if ($paragraphEntity->hasTranslation($t_lang)) {
                  $translated_paragraph = $paragraphEntity->getTranslation($t_lang);
                }
                else {
                  $translated_paragraph = $paragraphEntity->addTranslation($t_lang);
                }

                // Get all fields from the paragraph entity.
                $paragraph_fields = $paragraphEntity->getFields();
                $translated = [];

                foreach ($paragraph_fields as $paragraph_field_name => $paragraph_field) {
                  // Check the field type and retrieve the value accordingly.
                  // If it's a text field, retrieve the value.
                  $text_value = $paragraphEntity->get($paragraph_field_name)->value;
                  if (
                          is_string($text_value) &&
                          !in_array(strtolower($paragraph_field_name), $arrCheck) &&
                          !in_array(strtolower($text_value), $arrCheck) &&
                          $paragraph_field_name != "langcode" &&
                          !is_numeric($text_value) &&
                          !in_array($field_type, $arrCheck) &&
                          strip_tags($text_value) !== '' && !empty(strip_tags($text_value)
                          )
                        ) {
                    // Translate the text value.
                    $translationResponse = $this->translate($text_value, $d_lang, $t_lang);

                    array_push($translated, $translationResponse);
                    // Set the translated value for the field.
                    $translated_paragraph->set($paragraph_field_name, $translationResponse);
                  }
                }
                if (!empty($translated)) {
                  // Save the translated entity to persist the changes.
                  $translated_paragraph->save();
                  // $entity->save();
                }
              }
            }
          }
        }

        // Translatable field support.
        if ($field->isTranslatable()) {
          // Translate field.
          if (
            is_string($entity->get($field_name)->value)
            && !in_array(strtolower($entity->get($field_name)->value), $arrCheck)
            && $field_name != "langcode"
            && !is_numeric($entity->get($field_name)->value)
            && !in_array($field_type, $arrCheck)
            && isset($form[$field_name]['widget'][0]['value']['#default_value'])
          ) {
            $string = $entity->get($field_name)->value ? (string) $entity->get($field_name)->value : '';
            if ($string && !empty($string) && $string !== '' && strip_tags($string) !== '' && !empty(strip_tags($string))) {
              $translationResponse = $this->translate($string, $d_lang, $t_lang);
              if ($translationResponse) {
                $form[$field_name]['widget'][0]['value']['#default_value'] = $translationResponse;
              }
            }
          }
          // Paragraphs reference field support.
          if ($field->getSetting('target_type') == 'paragraph') {
            // Translate field.
            // var_dump($entity->get($field_name)->getValue());die;
            if (!in_array($field_type, $arrCheck)) {
              $paragraphs = $entity->get($field_name)->getValue();
              foreach ($paragraphs as $paragraph) {
                foreach ($paragraph as $key => $value) {
                  if (!$value || empty($value)) {
                    $value = $form[$field_name]['widget'][0][$key]['#default_value'];
                  }
                  if (
                    is_string($value) && !in_array(strtolower($value), $arrCheck) && $key != "langcode" && !is_numeric($value) && !in_array($field_type, $arrCheck)
                    && strip_tags($value) !== '' && !empty(strip_tags($value))
                  ) {
                    $translationResponse = $this->translate($value, $d_lang, $t_lang);
                    if ($translationResponse) {
                      $form[$field_name]['widget'][0][$key]['#default_value'] = $translationResponse;
                    }
                  }
                }
              }
            }
          }
          if (isset($form[$field_name]['widget'][0]['summary']["#default_value"]) && is_string($form[$field_name]['widget'][0]['summary']["#default_value"]) && !in_array('summary', $arrCheck)) {
            $string = $form[$field_name]['widget'][0]['summary']["#default_value"] ? $form[$field_name]['widget'][0]['summary']["#default_value"] : '';
            if ($string && !empty($string) && $string !== '') {
              $translationResponse = $this->translate($string, $d_lang, $t_lang);
              if ($translationResponse) {
                $form[$field_name]['widget'][0]['summary']["#default_value"] = $translationResponse;
              }
            }
          }
          if (isset($form[$field_name]['widget'][0]["#default_value"]) && is_string($form[$field_name]['widget'][0]["#default_value"]) && !in_array($field_name, $arrCheck)) {
            $string = $form[$field_name]['widget'][0]["#default_value"] ? $form[$field_name]['widget'][0]["#default_value"] : '';
            if ($string && !empty($string) && $string !== '' && strip_tags($string) !== '' && !empty(strip_tags($string))) {
              $translationResponse = $this->translate($string, $d_lang, $t_lang);
              if ($translationResponse) {
                $form[$field_name]['widget'][0]["#default_value"] = $translationResponse;
              }
            }
          }
        }
      }
    }
    return $form;
  }

  /**
   * Custom get string between function.
   */
  public function getStringBetween($string, $start, $end) {
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) {
      return '';
    }
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
  }

  /**
   * Retrieves the container.
   *
   * @return mixed
   *   The container.
   */
  public static function getContainer() {
    return \Drupal::getContainer();
  }

  /**
   * Retrieves the configuration settings.
   *
   * @return object
   *   The configuration settings.
   */
  public static function config() {
    return static::getContainer()
      ->get('config.factory')
      ->get('auto_translation.settings');
  }

  /**
   * Retrieves the specified form.
   *
   * @param object $messages
   *   The json of the message to retrieve.
   *
   * @return mixed
   *   The service object if found, null otherwise.
   */
  public static function getMessages($messages) {
    return static::getContainer()
      ->get('messenger')->addMessage(t('Auto translation error: @error', [
        '@error' => Markup::create(htmlentities(json_encode($messages))),
      ]), MessengerInterface::TYPE_ERROR);
    ;
  }

  /**
   * Returns the path of the module.
   *
   * @return string
   *   The path of the module.
   */
  public static function getModulePath() {
    return static::getContainer()
      ->get('extension.list.module')
      ->getPath('auto_translation');
  }

  /**
   * Retrieves the specified module by name.
   *
   * @param string $module_name
   *   The name of the module to retrieve.
   *
   * @return mixed|null
   *   The module object if found, null otherwise.
   */
  public static function getModule($module_name) {
    return static::getContainer()
      ->get('extension.list.module')
      ->get($module_name);
  }

}
