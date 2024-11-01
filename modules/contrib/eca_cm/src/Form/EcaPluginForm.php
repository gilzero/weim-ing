<?php

namespace Drupal\eca_cm\Form;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Email;
use Drupal\Core\Render\Element\Number;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Plugin\Action\ActionInterface;
use Drupal\eca\PluginManager\Action;
use Drupal\eca\PluginManager\Condition;
use Drupal\eca\PluginManager\Event;
use Drupal\eca_cm\EcaCm;
use Drupal\eca_ui\Service\TokenBrowserService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form class for configuring an ECA plugin.
 */
abstract class EcaPluginForm implements FormInterface, ContainerInjectionInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait {
    __sleep as servicesSleep;
    __wakeup as servicesWakeup;
  }

  /**
   * List of plugins for which validation needs to be avoided.
   *
   * @var string[]
   *
   * @see https://www.drupal.org/project/eca/issues/3278080
   */
  protected static array $skipValidation = [];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The event plugin manager.
   *
   * @var \Drupal\eca\PluginManager\Event
   */
  protected Event $eventManager;

  /**
   * The condition manager.
   *
   * @var \Drupal\eca\PluginManager\Condition
   */
  protected Condition $conditionManager;

  /**
   * The action manager.
   *
   * @var \Drupal\eca\PluginManager\Action
   */
  protected Action $actionManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The ECA configuration.
   *
   * @var \Drupal\eca\Entity\Eca|null
   */
  protected ?Eca $eca = NULL;

  /**
   * The ECA plugin.
   *
   * @var \Drupal\Component\Plugin\PluginInspectionInterface|null
   */
  protected ?PluginInspectionInterface $plugin = NULL;

  /**
   * The Token browser service.
   *
   * @var \Drupal\eca_ui\Service\TokenBrowserService
   */
  protected TokenBrowserService $tokenBrowser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The config key that identifies the plugin in ECA's config array.
   *
   * @var string|null
   */
  protected ?string $configKey = NULL;

  /**
   * The previously used config key.
   *
   * @var string|null
   */
  protected ?string $configKeyOriginal = NULL;

  /**
   * The config array that is stored at the config key in ECA's config array.
   *
   * @var array|null
   */
  protected ?array $configArray = NULL;

  /**
   * This flag indicates whether a new plugin config has been saved.
   *
   * @var bool
   */
  protected bool $savedNew = FALSE;

  /**
   * The plugin type, either one of "event", "condition" or "action".
   *
   * @var string
   */
  protected string $type;

  /**
   * Internal values stored for de-serialization.
   *
   * @var array
   */
  protected array $serialized = [];

  /**
   * The EcaPluginForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\eca\Entity\Eca|null $eca
   *   The ECA config.
   * @param Drupal\Component\Plugin\PluginInspectionInterface|null $plugin
   *   The ECA plugin.
   * @param \Drupal\eca_ui\Service\TokenBrowserService $token_browser
   *   The Token browser service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param string|null $config_key
   *   The ECA config key of the plugin.
   * @param array|null $config_array
   *   The ECA config array that is being identified by the config key.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, RouteMatchInterface $route_match, TokenBrowserService $token_browser, ModuleHandlerInterface $module_handler, ?Eca $eca = NULL, ?PluginInspectionInterface $plugin = NULL, ?string $config_key = NULL, ?array $config_array = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->routeMatch = $route_match;
    $this->eca = $eca;
    $this->plugin = $plugin;
    $this->configKey = $config_key;
    $this->configArray = $config_array;
    $this->tokenBrowser = $token_browser;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, ?Eca $eca = NULL, ?PluginInspectionInterface $plugin = NULL, ?string $config_key = NULL, ?array $config_array = NULL) {
    $instance = new static($container->get('entity_type.manager'), $container->get('messenger'), $container->get('current_route_match'), $container->get('eca_ui.service.token_browser'), $container->get('module_handler'), $eca, $plugin, $config_key, $config_array);
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->eventManager = $container->get('plugin.manager.eca.event');
    $instance->conditionManager = $container->get('plugin.manager.eca.condition');
    $instance->actionManager = $container->get('plugin.manager.eca.action');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eca_cm_plugin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?Eca $eca = NULL, ?PluginInspectionInterface $plugin = NULL, ?string $config_key = NULL, ?array $config_array = NULL) {
    $form['#tree'] = TRUE;
    $form['#process'][] = '::processForm';
    $form['#after_build'][] = '::afterBuild';
    $this->initProperties($form, $form_state, $eca, $plugin, $config_key, $config_array);
    $plugin = $this->plugin;
    $definition = $plugin->getPluginDefinition();
    $config = $plugin instanceof ConfigurableInterface ? $plugin->getConfiguration() : [];
    if (isset($config_array, $config_array['configuration'])) {
      $config += $config_array['configuration'];
    }
    $select_widget = $this->moduleHandler->moduleExists('select2') ? 'select2' : 'select';

    $weight = 0;
    $plugins_array = $this->eca->get($this->type . 's');
    $plugin_is_new = !isset($plugins_array[$this->configKey]);
    $type_label = $this->getTypeLabel();
    $config_array = $this->configArray ?? [];
    $original_config_array = $plugins_array[$this->configKey] ?? [];

    $weight += 10;

    $form[$this->type] = [
      '#type' => 'container',
      '#weight' => $weight++,
      '#tree' => TRUE,
      '#parents' => [$this->type],
    ];

    $weight += 10;
    $form[$this->type]['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('The human-readable label of this @type.', [
        '@type' => $type_label,
      ]),
      '#required' => TRUE,
      '#weight' => $weight,
      '#default_value' => $config_array['label'] ?? '',
    ];
    if ($this->type === 'condition') {
      $form[$this->type]['label']['#default_value'] = $definition['label'];
      $form[$this->type]['label']['#disabled'] = TRUE;
      $form[$this->type]['label']['#description'] = $this->t('Conditions currently don\'t support setting a customized label.');
    }
    $is_used = EcaCm::configKeyIsUsed($this->eca, $this->configKey, $this->type);
    $form[$this->type]['config_key'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->configKey,
      '#maxlength' => 32,
      '#disabled' => $is_used,
      '#machine_name' => [
        'exists' => [$this, 'configKeyExists'],
        'source' => [$this->type, 'label'],
      ],
      '#required' => TRUE,
      '#weight' => ($weight += 10),
    ];
    if ($is_used) {
      $form[$this->type]['config_key']['#description'] = $this->t('This @type is being used by another component and therefore cannot be changed.', [
        '@type' => $this->getTypeLabel(),
      ]);
    }

    $weight += 100;
    $has_config = FALSE;

    $form[$this->type]['configuration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('@type: @name', [
        '@type' => $type_label,
        '@name' => $definition['label'],
      ]),
      '#parents' => [$this->type],
      '#weight' => ($weight += 10),
    ];
    if (!empty($definition['description'])) {
      $form[$this->type]['configuration']['plugin_description'] = [
        '#type' => 'markup',
        '#prefix' => '<p>',
        '#markup' => $definition['description'],
        '#suffix' => '</p>',
        '#weight' => -10000,
      ];
    }

    if ($plugin instanceof PluginFormInterface) {
      $has_config = TRUE;

      // @todo Remove this workaround using $holdovers once
      // \Drupal\eca\Plugin\ECA\PluginFormTrait::updateConfigurationForm got
      // fixed. It is not properly iterating through element children.
      $holdovers = $form[$this->type]['configuration'];
      $form[$this->type]['configuration'] = [];

      // This block is not part of the workaround.
      $plugin_form_state = SubformState::createForSubform($form[$this->type]['configuration'], $form, $form_state);
      $form[$this->type]['configuration'] = $plugin->buildConfigurationForm($form[$this->type]['configuration'], $plugin_form_state);

      // Final step of the workaround.
      $form[$this->type]['configuration'] = $holdovers + $form[$this->type]['configuration'];

      // Some native form field types include validation, that may conflict
      // with token syntax. Use the textfield type instead, although that
      // may skip some basic validation first. This will be taken care of
      // within ::validateForm.
      $use_textfields = NULL;
      $use_textfields = static function (array &$elements) use (&$use_textfields) {
        if (isset($elements['#type']) && in_array($elements['#type'], ['number', 'email'], TRUE)) {
          $elements['#original_form_type'] = $elements['#type'];
          $elements['#type'] = 'textfield';
        }
        foreach (Element::children($elements) as $ckey) {
          $use_textfields($elements[$ckey]);
        }
      };
      foreach (Element::children($form[$this->type]['configuration']) as $ckey) {
        $use_textfields($form[$this->type]['configuration'][$ckey]);
      }

      $form[$this->type]['configuration']['token_browser'] = $this->tokenBrowser->getTokenBrowserMarkup();
      $form[$this->type]['configuration']['token_browser']['#weight'] = -10000;
    }

    switch ($this->type) {

      case 'action':
        // @see \Drupal\eca\Service\Actions::getConfigurationForm()
        $actionType = $plugin->getPluginDefinition()['type'] ?? '';
        if ($actionType === 'entity' || $this->entityTypeManager->getDefinition($actionType, FALSE)) {
          $has_config = TRUE;
          $form[$this->type]['object'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Entity'),
            '#description' => $this->t('Provide the token name of the %type that this action should operate with.', [
              '%type' => $actionType,
            ]),
            '#default_value' => $config['object'] ?? '',
            '#weight' => ($weight += 10),
          ];
        }
        if (!($plugin instanceof ActionInterface) && ($plugin instanceof ConfigurableInterface)) {
          // @todo Consider a form validate and submit method for this service.
          $has_config = TRUE;
          $form[$this->type]['replace_tokens'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Replace tokens'),
            '#description' => $this->t('When enabled, tokens will be replaced <em>before</em> executing the action. <strong>Please note:</strong> Actions might already take care of replacing tokens on their own. Therefore, use this option only with care and when it makes sense.'),
            '#default_value' => $config['replace_tokens'] ?? FALSE,
            '#weight' => ($weight += 10),
          ];
        }
        break;

    }

    if (!$has_config) {
      $form[$this->type]['configuration']['no_settings'] = [
        '#type' => 'markup',
        '#markup' => $this->t('This @type does not provide any configuration.', [
          '@type' => $this->type,
        ]),
        '#weight' => $weight,
      ];
    }

    if ($this->type !== 'condition') {
      $form[$this->type]['successors'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Successors'),
        '#parents' => [$this->type, 'successors'],
        '#weight' => ($weight += 10),
      ];
      $action_successor_options = ['_none' => $this->t('- Select -')];
      foreach (($this->eca->get('actions') ?? []) as $action_config_key => $action_config_array) {
        $action_successor_options[$action_config_key] = ($action_config_array['label'] ?? $this->actionManager->getDefinition($action_config_array['plugin'])['label']) . ' (' . $action_config_key . ')';
      }
      $condition_successor_options = ['_none' => $this->t('- Select -')];
      foreach (($this->eca->get('conditions') ?? []) as $condition_config_key => $condition_config_array) {
        $condition_successor_options[$condition_config_key] = ($condition_config_array['label'] ?? $this->conditionManager->getDefinition($condition_config_array['plugin'])['label']) . ' (' . $condition_config_key . ')';
      }

      $successors = ($config_array['successors'] ?? []);
      $wrapper_id = Html::getUniqueId('successors-table-wrapper');
      $form[$this->type]['successors']['wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['id' => $wrapper_id],
      ];
      if ($successors && count($action_successor_options) > 1) {
        $form[$this->type]['successors']['wrapper']['table'] = [
          '#attributes' => ['id' => Html::getUniqueId('successors-table')],
          '#type' => 'table',
          '#header' => [
            $this->t('Condition'),
            $this->t('Action'),
            '',
          ],
          '#parents' => [$this->type, 'successors'],
          '#weight' => ($weight += 10),
        ];
      }
      else {
        $form[$this->type]['successors']['wrapper']['table'] = [
          '#attributes' => ['id' => Html::getUniqueId('successors-table')],
          '#type' => 'container',
        ];
        if ($successors && count($action_successor_options) === 1) {
          $form[$this->type]['successors']['wrapper']['table']['no_actions'] = [
            '#type' => 'markup',
            '#markup' => $this->t('No actions have been configured yet for this ECA configuration. You first need to add an action in order to add it as a successor here.'),
          ];
        }
      }
      foreach ($successors as $i => $successor) {
        $form[$this->type]['successors']['wrapper']['table'][$i]['condition'] = [
          '#type' => $select_widget,
          '#title' => $this->t('Condition'),
          '#title_display' => 'invisible',
          '#options' => $condition_successor_options,
          '#default_value' => $successor['condition'] ?? '_none',
          '#required' => FALSE,
          '#empty_value' => '_none',
          '#weight' => 10,
        ];
        $form[$this->type]['successors']['wrapper']['table'][$i]['id'] = [
          '#type' => $select_widget,
          '#title' => $this->t('Action'),
          '#title_display' => 'invisible',
          '#options' => $action_successor_options,
          '#default_value' => $successor['id'] ?? '_none',
          '#required' => TRUE,
          '#empty_value' => '_none',
          '#weight' => 20,
        ];

        if (isset($original_config_array['successors'][$i])) {
          $form[$this->type]['successors']['wrapper']['table'][$i]['remove'] = [
            '#type' => 'button',
            '#value' => $this->t('Remove'),
            '#button_type' => 'danger',
            '#weight' => 30,
            '#executes_submit_callback' => TRUE,
            '#limit_validation_errors' => [],
            '#submit' => [[static::class, 'removeSuccessorSubmit']],
            '#name' => 'remove_successor_' . $i,
            '#ajax' => [
              'callback' => [static::class, 'removeSuccessorAjax'],
              'method' => 'html',
              'wrapper' => $wrapper_id,
            ],
          ];
        }
        else {
          $form[$this->type]['successors']['wrapper']['table'][$i]['remove'] = [];
        }
      }

      if (!$successors) {
        $form[$this->type]['successors']['wrapper']['no_successors'] = [
          '#type' => 'markup',
          '#markup' => '<p>' . $this->t('This @type has no successors.' . '</p>', [
            '@type' => $this->getTypeLabel(),
          ]),
        ];
        if (count($action_successor_options) === 1) {
          $form[$this->type]['successors']['wrapper']['no_actions'] = [
            '#type' => 'markup',
            '#markup' => '<p>' . $this->t('No actions have been configured yet for this ECA configuration. You need to add an action first, in order to add it as a successor here.') . '</p>',
          ];
        }
      }

      if (count($action_successor_options) > 1) {
        $form[$this->type]['successors']['add_item'] = [
          '#type' => 'button',
          '#value' => $this->t('Add item'),
          '#weight' => count($successors) + 100,
          '#executes_submit_callback' => TRUE,
          '#limit_validation_errors' => [],
          '#submit' => [[static::class, 'addSuccessorSubmit']],
          '#ajax' => [
            'callback' => [static::class, 'addSuccessorAjax'],
            'method' => 'html',
            'wrapper' => $wrapper_id,
          ],
        ];
      }
    }

    $weight += 100;
    $form['actions']['#weight'] = $weight++;
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm', '::save', '::redirectAfterSave'],
      '#button_type' => 'primary',
      '#weight' => 10,
    ];
    if (!$plugin_is_new && !$is_used && empty($config_array['successors'])) {
      $form['actions']['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#access' => $this->eca->access('delete'),
        '#submit' => ['::delete'],
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
        '#button_type' => 'danger',
        '#weight' => 20,
      ];
    }
    $weight += 10;
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancel'],
      '#attributes' => [
        'class' => ['button'],
      ],
      '#weight' => $weight++,
    ];

    $form['config'] = ['#tree' => TRUE, '#weight' => $weight++];
    $form['config']['eca_id'] = [
      '#type' => 'hidden',
      '#value' => $this->eca->id(),
    ];
    $form['config']['type'] = [
      '#type' => 'hidden',
      '#value' => $this->type,
    ];
    if (isset($this->configKey)) {
      $form['config']['config_key'] = [
        '#type' => 'hidden',
        '#value' => $this->configKey,
      ];
    }
    $form['config']['plugin'] = [
      '#type' => 'hidden',
      '#value' => $this->plugin->getPluginId(),
    ];

    return $form;
  }

  /**
   * Process callback.
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    $this->eca = Eca::load($form_state->get('eca_id'));
    $this->configKey = $form_state->get('config_key');
    $this->configArray = $form_state->get('config_array');
    $plugins = $this->eca->getPluginCollections();
    if (isset($plugins[$this->type . 's.' . $this->configKey])) {
      foreach ($plugins[$this->type . 's.' . $this->configKey] as $plugin) {
        $this->plugin = $plugin;
      }
    }
    if (($this->plugin instanceof ConfigurableInterface) && !empty($this->configArray['configuration'])) {
      $this->plugin->setConfiguration($this->configArray['configuration']);
    }
    return $element;
  }

  /**
   * After build callback.
   */
  public function afterBuild(array $form, FormStateInterface $form_state) {
    $plugin = $this->plugin;

    // Prevent Inline Entity Form from saving nested data.
    // @todo Find a better way to prevent submit handlers from saving data.
    if ($triggering_element = &$form_state->getTriggeringElement()) {
      if (isset($triggering_element['#ief_submit_trigger']) && !empty($triggering_element['#submit']) && is_array($triggering_element['#submit'])) {
        foreach ($triggering_element['#submit'] as $i => $submit_handler) {
          if (is_array($submit_handler) && (reset($submit_handler) === 'Drupal\\inline_entity_form\\ElementSubmit') && end($submit_handler) === 'trigger') {
            unset($triggering_element['#submit'][$i]);
          }
        }
      }
    }

    if ($form_state->hasValue([$this->type, 'configuration']) && $plugin instanceof PluginFormInterface) {
      $values = $form_state->getValue([$this->type, 'configuration']);
      array_walk_recursive($values, function (&$value) {
        if ($value === '_none') {
          $value = NULL;
        }
      });
      $form_state->setValue([$this->type, 'configuration'], $values);
      $plugin_form_state = SubformState::createForSubform($form[$this->type]['configuration'], $form, $form_state);
      $plugin->submitConfigurationForm($form[$this->type]['configuration'], $plugin_form_state);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->eca->access('update')) {
      $form_state->setError($form, $this->t('You don\'t have permission to manage this configuration.'));
    }

    // A helper function to check whether a user input is a token.
    $is_token = static function ($input) {
      return (is_scalar($input) &&
        mb_substr(trim((string) $input), 0, 1) === '[') &&
        (mb_substr(trim((string) $input), -1, 1) === ']') &&
        (mb_strlen(trim((string) $input)) <= 255);
    };

    // Within ::buildForm some native form field types were replaced by
    // textfield type to support tokens. Here the validation on non-tokens
    // needs to be manually evaluated.
    $manual_validate = NULL;
    $manual_validate = static function (array &$elements) use (&$manual_validate, $form_state, &$form) {
      if (isset($elements['#original_form_type'])) {
        switch ($elements['#original_form_type']) {

          case 'number':
            Number::validateNumber($elements, $form_state, $form);
            break;

          case 'email':
            Email::validateEmail($elements, $form_state, $form);
            break;

        }
      }
      foreach (Element::children($elements) as $ckey) {
        $manual_validate($elements[$ckey]);
      }
    };
    if (isset($form[$this->type]['configuration'])) {
      foreach (Element::children($form[$this->type]['configuration']) as $ckey) {
        $manual_validate($form[$this->type]['configuration'][$ckey]);
      }
    }

    // Lookup generated errors of native form field types. If the validation
    // fails because it does not accept tokens, this part removes that case.
    $errors = $form_state->getErrors();
    $form_state->clearErrors();
    foreach ($errors as $name => $error) {
      $value = $form_state->getValue(explode('][', $name), '');
      if (!$is_token($value)) {
        $form_state->setErrorByName($name, $error);
      }
    }

    if ($triggering_element = &$form_state->getTriggeringElement()) {
      if (isset($triggering_element['#parents']) && reset($triggering_element['#parents']) !== 'actions') {
        return;
      }
    }

    $plugin = $this->plugin;
    if ($plugin instanceof PluginFormInterface && !in_array($plugin->getPluginId(), static::$skipValidation, TRUE)) {
      $plugin_form_state = SubformState::createForSubform($form[$this->type]['configuration'], $form, $form_state);
      $plugin->validateConfigurationForm($form[$this->type]['configuration'], $plugin_form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->eca->access('update')) {
      return;
    }
    if ($triggering_element = &$form_state->getTriggeringElement()) {
      if (isset($triggering_element['#parents']) && reset($triggering_element['#parents']) !== 'actions') {
        return;
      }
    }

    if (!isset($this->plugin)) {
      $this->initProperties($form, $form_state);
    }

    $plugin = $this->plugin;
    $config = $this->configArray ?? [];
    $config['plugin'] = $plugin->getPluginId();
    if (in_array($this->type, ['event', 'action'])) {
      $config['label'] = $form_state->getValue([$this->type, 'label'], $config['label'] ?? '');
    }
    if (isset($config['configuration']) && ($plugin instanceof ConfigurableInterface)) {
      $plugin->setConfiguration($config['configuration']);
    }
    if ($plugin instanceof PluginFormInterface) {
      if (isset($form[$this->type]['configuration']) && $plugin instanceof PluginFormInterface) {
        $plugin_form_state = SubformState::createForSubform($form[$this->type]['configuration'], $form, $form_state);
        $plugin->submitConfigurationForm($form[$this->type]['configuration'], $plugin_form_state);
      }
    }
    if ($plugin instanceof ConfigurableInterface) {
      $config['configuration'] = $plugin->getConfiguration();

      // Some plugins don't take care about converting boolean values.
      // This section takes care of that.
      $default_configuration = $plugin->defaultConfiguration();
      foreach ($default_configuration as $k => $v) {
        if (is_bool($v) && isset($config['configuration'][$k])) {
          $config['configuration'][$k] = (bool) $config['configuration'][$k];
        }
      }

    }
    if ($form_state->hasValue([$this->type, 'object'])) {
      $config['configuration']['object'] = $form_state->getValue([$this->type, 'object']);
    }
    if ($form_state->hasValue([$this->type, 'replace_tokens'])) {
      $config['configuration']['replace_tokens'] = (bool) $form_state->getValue([$this->type, 'replace_tokens']);
    }
    if (in_array($this->type, ['event', 'action', 'gateway'])) {
      foreach ($form_state->getValue([$this->type, 'successors'], []) as $i => $successor) {
        if (!is_array($successor)) {
          continue;
        }
        $config['successors'][$i]['id'] = ($successor['id'] ?? '_none');
        $config['successors'][$i]['condition'] = ($successor['condition'] ?? '_none');
        if ($config['successors'][$i]['id'] === '_none') {
          $config['successors'][$i]['id'] = NULL;
        }
        if ($config['successors'][$i]['condition'] === '_none') {
          $config['successors'][$i]['condition'] = NULL;
        }
      }
      if (!isset($config['successors'])) {
        $config['successors'] = [];
      }
      foreach ($config['successors'] as $i => $successor) {
        if (!isset($successor['id']) && !isset($successor['condition'])) {
          unset($config['successors'][$i]);
        }
        if (($successor['id'] === '_none') && ($successor['condition'] === '_none')) {
          unset($config['successors'][$i]);
        }
      }
      $config['successors'] = array_values($config['successors']);
    }
    $this->configKeyOriginal = $this->configKey;
    $this->configKey = $form_state->getValue([$this->type, 'config_key'], $form_state->getValue(['config', 'config_key'], $form_state->get('config_key')));

    $this->configArray = $config;
    $form_state->set('config_key', $this->configKey);
    $form_state->set('config_array', $this->configArray);
  }

  /**
   * Redirect after save submission callback.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function redirectAfterSave(array &$form, FormStateInterface $form_state) {
    if (!$this->eca->access('update')) {
      return;
    }

    $label = $this->configArray['label'] ?? $this->plugin->getPluginDefinition()['label'];

    if ($this->savedNew) {
      $message = $this->t('The new @type "%name" has been added.', [
        '@type' => $this->getTypeLabel(),
        '%name' => $label,
      ]);
    }
    else {
      $message = $this->t('The changes for @type "%name" have been saved.', [
        '@type' => $this->getTypeLabel(),
        '%name' => $label,
      ]);
    }

    $this->messenger->addStatus($message);

    $form_state->setRedirect("entity.eca.edit_form", [
      'eca' => $this->eca->id(),
    ]);
  }

  /**
   * Save submission callback.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function save(array &$form, FormStateInterface $form_state): void {
    if (!$this->eca->access('update')) {
      return;
    }
    $plugins_array = $this->eca->get($this->type . 's') ?? [];
    $i = 0;
    foreach ($plugins_array as &$plugin_array) {
      $plugin_array['weight'] = $i;
      $i++;
    }
    unset($plugin_array);

    $this->savedNew = !isset($plugins_array[$this->configKey]);
    $weight = count($plugins_array);
    if (isset($this->configKeyOriginal) && ($this->configKey !== $this->configKeyOriginal)) {
      $weight = $plugins_array[$this->configKeyOriginal]['weight'] ?? $weight;
      unset($plugins_array[$this->configKeyOriginal]);
    }
    $weight = $plugins_array[$this->configKey]['weight'] ?? $weight;
    $plugins_array[$this->configKey] = $this->configArray;
    if ($this->type === 'condition') {
      unset($plugins_array[$this->configKey]['label']);
    }
    $plugins_array[$this->configKey]['weight'] = $weight;
    uasort($plugins_array, function ($a, $b) {
      return $a['weight'] > $b['weight'] ? 1 : -1;
    });
    foreach ($plugins_array as &$plugin_array) {
      unset($plugin_array['weight']);
    }
    unset($plugin_array);
    if (!empty($plugin_array['successors'])) {
      $plugin_array['successors'] = array_values($plugin_array['successors']);
    }
    $this->eca->set($this->type . 's', $plugins_array);
    $this->eca->save();
  }

  /**
   * Delete submission callback that redirects to the plugin delete form.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function delete(array &$form, FormStateInterface $form_state): void {
    $this->eca = $this->eca;

    $form_state->setRedirect("eca_cm.{$this->type}.delete", [
      'eca' => $this->eca->id(),
      'eca_' . $this->type . '_id' => $this->configKey,
    ]);
  }

  /**
   * Initializes the form object properties.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\eca\Entity\Eca|null $eca
   *   The ECA config.
   * @param Drupal\Component\Plugin\PluginInspectionInterface|null $plugin
   *   The ECA plugin.
   * @param string|null $config_key
   *   The ECA config key of the plugin.
   * @param array|null $config_array
   *   The ECA config array that is being identified by the config key.
   */
  protected function initProperties(array &$form, FormStateInterface $form_state, ?Eca $eca = NULL, ?PluginInspectionInterface $plugin = NULL, ?string $config_key = NULL, ?array $config_array = NULL): void {
    if ($form_state->has('eca_id')) {
      $this->eca = Eca::load($form_state->get('eca_id'));
      $this->configKey = $form_state->get('config_key');
      $this->configArray = $form_state->get('config_array');
      $plugins = $this->eca->getPluginCollections();
      if (!isset($this->plugin) && $form_state->hasValue(['config', 'plugin'])) {
        if ($this->type === 'event') {
          $this->plugin = $this->eventManager->createInstance($form_state->getValue(['config', 'plugin']));
        }
        if ($this->type === 'condition') {
          $this->plugin = $this->conditionManager->createInstance($form_state->getValue(['config', 'plugin']));
        }
        if ($this->type === 'action') {
          $this->plugin = $this->actionManager->createInstance($form_state->getValue(['config', 'plugin']));
        }
      }
      if (isset($plugins[$this->type . 's.' . $this->configKey])) {
        foreach ($plugins[$this->type . 's.' . $this->configKey] as $plugin) {
          $this->plugin = $plugin;
        }
      }
      if (($this->plugin instanceof ConfigurableInterface) && !empty($this->configArray['configuration'])) {
        $this->plugin->setConfiguration($this->configArray['configuration']);
      }
    }
    elseif (isset($eca, $plugin)) {
      $this->eca = $eca;
      $this->plugin = $plugin;
      $this->configKey = $config_key;
      $this->configArray = $config_array;
    }
    elseif ($config_values = $form_state->getValue('config')) {
      $this->eca = Eca::load($config_values['eca_id']);
      $plugins_array = $this->eca->get($this->type . 's') ?? [];
      if (isset($config_values['config_key'])) {
        $this->configKey = $config_values['config_key'];
      }
      if (isset($this->configKey) && isset($plugins_array[$this->configKey])) {
        $plugins = $this->eca->getPluginCollections();
        foreach ($plugins[$this->type . 's.' . $this->configKey] as $plugin) {
          $this->plugin = $plugin;
        }
      }
    }
    else {
      throw new \InvalidArgumentException("Form build error: The plugin form cannot be built without any information about according configuration.");
    }
    $plugins_array = $this->eca->get($this->type . 's') ?? [];
    if (isset($plugins_array[$this->configKey]) && empty($this->configArray)) {
      $this->configArray = $plugins_array[$this->configKey];
    }
    $form_state->set('eca_id', $this->eca->id());
    $form_state->set('config_key', $this->configKey);
    $form_state->set('config_array', $this->configArray);
  }

  /**
   * Get the plugin type label.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The type label.
   */
  abstract protected function getTypeLabel(): MarkupInterface;

  /**
   * Looks up whether the given config key already exists.
   *
   * @param mixed $config_key
   *   The key to check for.
   *
   * @return bool
   *   Returns TRUE if exists, FALSE otherwise.
   */
  public function configKeyExists($config_key): bool {
    foreach (['events', 'conditions', 'actions', 'gateways'] as $type) {
      $plugins_array = $this->eca->get($type);
      if (isset($plugins_array[$config_key])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Cancel submission callback.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $eca = $this->eca;
    $form_state->setRedirect("entity.eca.edit_form", [
      'eca' => $eca->id(),
    ]);
  }

  /**
   * Remove successor ajax callback.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form content to refresh.
   */
  public static function removeSuccessorAjax(array &$form, FormStateInterface $form_state): array {
    $type = $form_state->getValue(['config', 'type']);
    return $form[$type]['successors']['wrapper']['table'];
  }

  /**
   * Remove successor submit callback.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function removeSuccessorSubmit(array &$form, FormStateInterface $form_state): void {
    $triggering_element = $form_state->getTriggeringElement();
    $keys = array_slice($triggering_element['#array_parents'], 0, -1);
    $index = end($keys);
    $config_array = $form_state->get('config_array');
    unset($config_array['successors'][$index]);
    $form_state->set('config_array', $config_array);
    $form_state->setRebuild();
    $build_info = $form_state->getBuildInfo();
    $build_info['args'] = [];
    $form_state->setBuildInfo($build_info);
  }

  /**
   * Add successor ajax callback.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form content to refresh.
   */
  public static function addSuccessorAjax(array &$form, FormStateInterface $form_state): array {
    $type = $form_state->getValue(['config', 'type']);
    return $form[$type]['successors']['wrapper']['table'];
  }

  /**
   * Add successor submit callback.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function addSuccessorSubmit(array &$form, FormStateInterface $form_state): void {
    $config_array = $form_state->get('config_array');
    $config_array['successors'][] = ['id' => NULL, 'condition' => NULL];
    $form_state->set('config_array', $config_array);
    $form_state->setRebuild();
    $build_info = $form_state->getBuildInfo();
    $build_info['args'] = [];
    $form_state->setBuildInfo($build_info);
  }

  public function __sleep() {
    $props = $this->servicesSleep();
    $this->serialized['eca'] = $this->eca ? $this->eca->id() : NULL;
    return array_filter($props, function ($v) {
      return !in_array($v, ['eca', 'plugin']);
    });
  }

  public function __wakeup() {
    $this->servicesWakeup();
    if (isset($this->serialized['eca'])) {
      $this->eca = Eca::load($this->serialized['eca']);
    }
    if (isset($this->configKey)) {
      $plugins = $this->eca->getPluginCollections();
      if (isset($plugins[$this->type . 's.' . $this->configKey])) {
        foreach ($plugins[$this->type . 's.' . $this->configKey] as $plugin) {
          $this->plugin = $plugin;
        }
      }
      if (($this->plugin instanceof ConfigurableInterface) && !empty($this->configArray['configuration'])) {
        $this->plugin->setConfiguration($this->configArray['configuration']);
      }
    }
  }

}
