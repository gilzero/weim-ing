<?php

namespace Drupal\eca_cm\Form;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\eca\PluginManager\Action;
use Drupal\eca\PluginManager\Condition;
use Drupal\eca\PluginManager\Event;
use Drupal\eca_cm\EcaCm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for an ECA configuration entity.
 */
class EcaForm extends EntityForm {

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
   * {@inheritdoc}
   */
  protected $operation = 'default';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setEntityTypeManager($container->get('entity_type.manager'));
    $instance->setModuleHandler($container->get('module_handler'));
    $instance->setRedirectDestination($container->get('redirect.destination'));
    $instance->eventManager = $container->get('plugin.manager.eca.event');
    $instance->conditionManager = $container->get('plugin.manager.eca.condition');
    $instance->actionManager = $container->get('plugin.manager.eca.action');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eca_cm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $this->checkPossibleProblems();

    /** @var \Drupal\eca\Entity\Eca $eca */
    $eca = $this->entity;

    $weight = 10;

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $eca->label(),
      '#description' => $eca->isNew() ? $this->t('The human-readable name of the new ECA configuration.') : $this->t('The human-readable name of this ECA configuration.'),
      '#required' => TRUE,
      '#size' => 30,
      '#weight' => ($weight += 10),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $eca->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\eca\Entity\Eca', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this ECA configuration. It must only contain lowercase letters, numbers, and underscores.'),
      '#weight' => ($weight += 10),
    ];

    if ($eca->isNew()) {
      $form['new_info'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['eca-is-new']],
        '#weight' => ($weight += 10),
      ];
      $form['new_info']['text'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Once the new ECA configuration is saved, you can add events, conditions and actions to it.'),
      ];

      $form['actions']['#weight'] = ($weight += 10);

      return $form;
    }

    $plugins = $eca->getPluginCollections();
    $event_plugins = array_filter($plugins, function ($key) {
      return mb_substr($key, 0, 7) === 'events.';
    }, ARRAY_FILTER_USE_KEY);
    $condition_plugins = array_filter($plugins, function ($key) {
      return mb_substr($key, 0, 11) === 'conditions.';
    }, ARRAY_FILTER_USE_KEY);
    $action_plugins = array_filter($plugins, function ($key) {
      return mb_substr($key, 0, 8) === 'actions.';
    }, ARRAY_FILTER_USE_KEY);

    $select_widget = $this->moduleHandler->moduleExists('select2') ? 'select2' : 'select';

    $num_events = count($event_plugins);
    $num_conditions = count($condition_plugins);
    $num_actions = count($action_plugins);
    $form['events'] = [
      '#type' => 'details',
      '#open' => !$num_events,
      '#title' => $this->t('Events (@count)', [
        '@count' => $num_events,
      ]),
      '#weight' => ($weight += 10),
    ];
    if ($num_events) {
      $form['events']['table'] = [
        '#attributes' => ['id' => Html::getUniqueId('events-table')],
        '#type' => 'table',
        '#parents' => ['events'],
        '#header' => [
          $this->t('Event'),
          $this->t('Configuration'),
          $this->t('Successors'),
          $this->t('Weight'),
          $this->t('Operations'),
        ],
        '#weight' => 10,
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'eca-event-weight',
          ],
        ],
      ];
      $order_weight = 0;
      /** @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection $collection */
      foreach ($event_plugins as $k => $collection) {
        [, $id] = explode('.', $k, 2);
        $config_array = ($eca->get('events') ?? [])[$id] ?? [];
        $order_weight += 1;
        /** @var \Drupal\eca\Plugin\ECA\Event\EventInterface $event_plugin */
        foreach ($collection as $event_plugin) {
          $definition = $event_plugin->getPluginDefinition();
          $config = $event_plugin->getConfiguration();
          $form['events']['table'][$id] = [
            '#attributes' => ['class' => ['draggable']],
            '#weight' => $order_weight,
          ];
          $event_label = (string) ($config_array['label'] ?? $definition['label']);
          if (((string) $definition['label']) != $event_label) {
            $event_label .= '<br/><em>' . $definition['label'] . '</em>';
          }
          $event_label .= '<br/>' . $this->t('Machine name:') . ' <em>' . $id . '</em>';
          $form['events']['table'][$id]['event'] = [
            '#type' => 'markup',
            '#markup' => '<div>' . $event_label . '</div>',
            '#weight' => 10,
          ];
          $config_string = $config ? $this->getConfigString($config) : $this->t('nothing');
          $form['events']['table'][$id]['configuration'] = [
            '#type' => 'markup',
            '#markup' => '<div>' . $config_string . '</div>',
            '#weight' => 20,
          ];
          $successors = $config_array['successors'] ?? [];
          $successors = $successors ? $this->getConfigString($successors) : $this->t('No successor');
          $form['events']['table'][$id]['successors'] = [
            '#type' => 'markup',
            '#markup' => '<div>' . $successors . '</div>',
            '#weight' => 30,
          ];
          $form['events']['table'][$id]['weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight'),
            '#title_display' => 'invisible',
            '#default_value' => $order_weight,
            '#attributes' => ['class' => ['eca-event-weight', 'weight']],
            '#delta' => $num_events + 10,
            '#weight' => 40,
          ];
          $operations = [];
          if ($eca->access('update')) {
            $operations['edit'] = [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute("eca_cm.event.edit", [
                'eca' => $eca->id(),
                'eca_event_id' => $id,
              ]),
              'weight' => 10,
            ];
          }
          if ($eca->access('delete') && empty($config_array['successors'])) {
            $operations['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute("eca_cm.event.delete", [
                'eca' => $eca->id(),
                'eca_event_id' => $id,
              ]),
              'weight' => 20,
            ];
          }
          $form['events']['table'][$id]['operations'] = [
            '#type' => 'operations',
            '#links' => $operations,
            '#weight' => 50,
          ];
        }
      }
    }
    else {
      $form['events']['empty'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No events have been added yet. At first, you need to add an event, in order to specify what to react upon. Once you have added at least one event, you can then add conditions and actions as successors. To add an event, choose one in the select dropdown below, and then click on the button <em>Add event</em>.'),
        '#weight' => 10,
      ];
    }

    if ($num_events || $num_conditions || $num_actions) {
      $form['conditions'] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $this->t('Conditions (@count)', [
          '@count' => $num_conditions,
        ]),
        '#weight' => ($weight += 10),
      ];

      if ($num_conditions) {
        $form['conditions']['table'] = [
          '#attributes' => ['id' => Html::getUniqueId('conditions-table')],
          '#type' => 'table',
          '#parents' => ['conditions'],
          '#header' => [
            $this->t('Condition'),
            $this->t('Configuration'),
            $this->t('Weight'),
            $this->t('Operations'),
          ],
          '#weight' => 10,
          '#tabledrag' => [
            [
              'action' => 'order',
              'relationship' => 'sibling',
              'group' => 'eca-condition-weight',
            ],
          ],
        ];
        $order_weight = 0;
        /** @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection $collection */
        foreach ($condition_plugins as $k => $collection) {
          [, $id] = explode('.', $k, 2);
          $config_array = ($eca->get('conditions') ?? [])[$id] ?? [];
          $order_weight += 1;
          /** @var \Drupal\eca\Plugin\ECA\Condition\ConditionInterface $condition_plugin */
          foreach ($collection as $condition_plugin) {
            $definition = $condition_plugin->getPluginDefinition();
            $config = $condition_plugin->getConfiguration();
            $form['conditions']['table'][$id] = [
              '#attributes' => ['class' => ['draggable']],
              '#weight' => $order_weight,
            ];
            $condition_label = (string) ($config_array['label'] ?? $definition['label']);
            if (((string) $definition['label']) != $condition_label) {
              $condition_label .= '<br/><em>' . $definition['label'] . '</em>';
            }
            $condition_label .= '<br/>' . $this->t('Machine name:') . ' <em>' . $id . '</em>';
            $form['conditions']['table'][$id]['event'] = [
              '#type' => 'markup',
              '#markup' => '<div>' . $condition_label . '</div>',
              '#weight' => 10,
            ];
            $config_string = $config ? $this->getConfigString($config) : $this->t('nothing');
            $form['conditions']['table'][$id]['configuration'] = [
              '#type' => 'markup',
              '#markup' => '<div>' . $config_string . '</div>',
              '#weight' => 20,
            ];
            $form['conditions']['table'][$id]['weight'] = [
              '#type' => 'weight',
              '#title' => $this->t('Weight'),
              '#title_display' => 'invisible',
              '#default_value' => $order_weight,
              '#attributes' => ['class' => ['eca-condition-weight', 'weight']],
              '#delta' => $num_conditions + 10,
              '#weight' => 40,
            ];
            $operations = [];
            if ($eca->access('update')) {
              $operations['edit'] = [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute("eca_cm.condition.edit", [
                  'eca' => $eca->id(),
                  'eca_condition_id' => $id,
                ]),
                'weight' => 30,
              ];
            }
            if ($eca->access('delete') && !EcaCm::configKeyIsUsed($eca, $id, 'condition') && empty($config_array['successors'])) {
              $operations['delete'] = [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute("eca_cm.condition.delete", [
                  'eca' => $eca->id(),
                  'eca_condition_id' => $id,
                ]),
                'weight' => 20,
              ];
            }
            $form['conditions']['table'][$id]['operations'] = [
              '#type' => 'operations',
              '#links' => $operations,
              '#weight' => 50,
            ];
          }
        }
      }
      else {
        $form['conditions']['empty'] = [
          '#type' => 'markup',
          '#markup' => $this->t('No conditions have been added yet. You can add one below in section <em>Add condition</em>.'),
          '#weight' => 10,
        ];
      }
    }

    if ($num_events || $num_conditions || $num_actions) {
      $form['action_plugins'] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $this->t('Actions (@count)', [
          '@count' => $num_actions,
        ]),
        '#weight' => ($weight += 10),
      ];

      if ($num_actions) {
        $form['action_plugins']['table'] = [
          '#attributes' => ['id' => Html::getUniqueId('actions-table')],
          '#type' => 'table',
          '#parents' => ['action_plugins'],
          '#header' => [
            $this->t('Action'),
            $this->t('Configuration'),
            $this->t('Successors'),
            $this->t('Weight'),
            $this->t('Operations'),
          ],
          '#weight' => 10,
          '#tabledrag' => [
            [
              'action' => 'order',
              'relationship' => 'sibling',
              'group' => 'eca-action-weight',
            ],
          ],
        ];
        $order_weight = 0;
        /** @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection $collection */
        foreach ($action_plugins as $k => $collection) {
          [, $id] = explode('.', $k, 2);
          $config_array = ($eca->get('actions') ?? [])[$id] ?? [];
          $order_weight += 1;
          /** @var \Drupal\Core\Action\ActionInterface $action_plugin */
          foreach ($collection as $action_plugin) {
            $definition = $action_plugin->getPluginDefinition();
            $config = $action_plugin instanceof ConfigurableInterface ? $action_plugin->getConfiguration() : [];
            if (isset($config_array['configuration'])) {
              $config += $config_array['configuration'];
            }
            $form['action_plugins']['table'][$id] = [
              '#attributes' => ['class' => ['draggable']],
              '#weight' => $order_weight,
            ];
            $action_label = (string) ($config_array['label'] ?? $definition['label']);
            if (((string) $definition['label']) != $action_label) {
              $action_label .= '<br/><em>' . $definition['label'] . '</em>';
            }
            $action_label .= '<br/>' . $this->t('Machine name:') . ' <em>' . $id . '</em>';
            $form['action_plugins']['table'][$id]['action'] = [
              '#type' => 'markup',
              '#markup' => '<div>' . $action_label . '</div>',
              '#weight' => 10,
            ];
            $config_string = $config ? $this->getConfigString($config) : $this->t('nothing');
            $form['action_plugins']['table'][$id]['configuration'] = [
              '#type' => 'markup',
              '#markup' => '<div>' . $config_string . '</div>',
              '#weight' => 20,
            ];
            $successors = $config_array['successors'] ?? [];
            $successors = $successors ? $this->getConfigString($successors) : $this->t('No successor');
            $form['action_plugins']['table'][$id]['successors'] = [
              '#type' => 'markup',
              '#markup' => '<div>' . $successors . '</div>',
              '#weight' => 30,
            ];
            $form['action_plugins']['table'][$id]['weight'] = [
              '#type' => 'weight',
              '#title' => $this->t('Weight'),
              '#title_display' => 'invisible',
              '#default_value' => $order_weight,
              '#attributes' => ['class' => ['eca-action-weight', 'weight']],
              '#delta' => $num_actions + 10,
              '#weight' => 40,
            ];
            $operations = [];
            if ($eca->access('update')) {
              $operations['edit'] = [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute("eca_cm.action.edit", [
                  'eca' => $eca->id(),
                  'eca_action_id' => $id,
                ]),
                'weight' => 10,
              ];
            }
            if ($eca->access('delete') && !EcaCm::configKeyIsUsed($eca, $id, 'action') && empty($config_array['successors'])) {
              $operations['delete'] = [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute("eca_cm.action.delete", [
                  'eca' => $eca->id(),
                  'eca_action_id' => $id,
                ]),
                'weight' => 20,
              ];
            }
            $form['action_plugins']['table'][$id]['operations'] = [
              '#type' => 'operations',
              '#links' => $operations,
              '#weight' => 50,
            ];
          }
        }
      }
      else {
        $form['action_plugins']['empty'] = [
          '#type' => 'markup',
          '#markup' => $this->t('No actions have been added yet. You can add one below in section <em>Add action</em>.'),
          '#weight' => 10,
        ];
      }
    }

    $weight += 100;
    $wrapper_id = Html::getUniqueId('add-event');
    $form['add_event'] = [
      '#type' => 'details',
      '#open' => !$num_events || ($form_state->getValue(['add_event', 'plugin'], '_none') !== '_none'),
      '#title' => $this->t('Add event'),
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#weight' => $weight++,
      '#group' => 'events',
    ];
    $form['add_event']['table'] = [
      '#type' => 'table',
      '#weight' => 10,
      '#attributes' => [
        'class' => ['eca-form-add-event'],
      ],
    ];

    $form['add_event']['table'][0] = [
      '#parents' => ['add_event'],
    ];
    $event_options_label = [];
    $event_options_provider = [];
    foreach ($this->eventManager->getDefinitions() as $id => $definition) {
      $module_name = $definition['provider'] === 'core' ? 'Drupal core' : (string) $this->moduleHandler->getName((string) $definition['provider']);
      $event_options_label[$id] = (string) $definition['label'] . ' (' . $module_name . ')';
      $event_options_provider[$id] = $module_name . ' ' . (string) $definition['label'];
    }
    asort($event_options_provider, SORT_NATURAL);
    $event_options = ['_none' => $this->t('- Select -')];
    foreach (array_keys($event_options_provider) as $id) {
      $event_options[$id] = $event_options_label[$id];
    }
    if (count($event_options) === 1) {
      $this->messenger()->addWarning($this->t("There are no events available to add. Install at least one module that provides an event, for example <em>ECA Base (eca_base)</em>."));
    }
    $form['add_event']['table'][0]['plugin'] = [
      '#type' => $select_widget,
      '#options' => $event_options,
      '#default_value' => '_none',
      '#empty_value' => '_none',
      '#weight' => 20,
      '#ajax' => [
        'callback' => [static::class, 'addEventAjax'],
        'wrapper' => $wrapper_id,
      ],
      '#executes_submit_callback' => TRUE,
      '#submit' => [[static::class, 'submitEventAjax']],
      '#required' => FALSE,
    ];
    if ($form_state->getValue(['add_event', 'plugin'], '_none') !== '_none') {
      $requested_plugin = trim($form_state->getValue(['add_event', 'plugin'], ''));
      $form['add_event']['table'][0]['link'] = [
        '#type' => 'link',
        '#attributes' => [
          'class' => ['button', 'button-action', 'button--primary'],
        ],
        '#title' => $this->t('Add event'),
        '#url' => Url::fromRoute("eca_cm.event.add", [
          'eca' => $eca->id(),
          'eca_event_plugin' => $requested_plugin,
        ]),
      ];
    }
    else {
      $form['add_event']['table'][0]['link'] = [
        '#type' => 'button',
        '#disabled' => TRUE,
        '#button_type' => 'primary',
        '#value' => $this->t('Add event'),
      ];
    }

    if ($num_events || $num_conditions || $num_actions) {
      $weight += 100;
      $wrapper_id = Html::getUniqueId('add-condition');
      $form['add_condition'] = [
        '#type' => 'details',
        '#open' => ($form_state->getValue(['add_condition', 'plugin'], '_none') !== '_none'),
        '#title' => $this->t('Add condition'),
        '#prefix' => '<div id="' . $wrapper_id . '">',
        '#suffix' => '</div>',
        '#weight' => $weight++,
        '#group' => 'conditions',
      ];
      $form['add_condition']['table'] = [
        '#type' => 'table',
        '#weight' => 10,
        '#attributes' => [
          'class' => ['eca-form-add-condition'],
        ],
      ];

      $form['add_condition']['table'][0] = [
        '#parents' => ['add_condition'],
      ];
      $condition_options_label = [];
      $condition_options_provider = [];
      foreach ($this->conditionManager->getDefinitions() as $id => $definition) {
        $module_name = $definition['provider'] === 'core' ? 'Drupal core' : (string) $this->moduleHandler->getName((string) $definition['provider']);
        $condition_options_label[$id] = (string) $definition['label'] . ' (' . $module_name . ')';
        $condition_options_provider[$id] = $module_name . ' ' . (string) $definition['label'];
      }
      asort($condition_options_provider, SORT_NATURAL);
      $condition_options = ['_none' => $this->t('- Select -')];
      foreach (array_keys($condition_options_provider) as $id) {
        $condition_options[$id] = $condition_options_label[$id];
      }
      if (count($condition_options) === 1) {
        $this->messenger()->addWarning($this->t("There are no conditions available to add. Install at least one module that provides a condition, for example <em>ECA Base (eca_base)</em>."));
      }
      $form['add_condition']['table'][0]['plugin'] = [
        '#type' => $select_widget,
        '#options' => $condition_options,
        '#default_value' => '_none',
        '#empty_value' => '_none',
        '#weight' => 20,
        '#ajax' => [
          'callback' => [static::class, 'addConditionAjax'],
          'wrapper' => $wrapper_id,
        ],
        '#executes_submit_callback' => TRUE,
        '#submit' => [[static::class, 'submitConditionAjax']],
        '#required' => FALSE,
      ];
      if ($form_state->getValue(['add_condition', 'plugin'], '_none') !== '_none') {
        $requested_plugin = trim($form_state->getValue(['add_condition', 'plugin'], ''));
        $form['add_condition']['table'][0]['link'] = [
          '#type' => 'link',
          '#attributes' => [
            'class' => ['button', 'button-action', 'button--primary'],
          ],
          '#title' => $this->t('Add condition'),
          '#url' => Url::fromRoute("eca_cm.condition.add", [
            'eca' => $eca->id(),
            'eca_condition_plugin' => $requested_plugin,
          ]),
        ];
      }
      else {
        $form['add_condition']['table'][0]['link'] = [
          '#type' => 'button',
          '#disabled' => TRUE,
          '#button_type' => 'primary',
          '#value' => $this->t('Add condition'),
        ];
      }

      $weight += 100;
      $wrapper_id = Html::getUniqueId('add-action');
      $form['add_action'] = [
        '#type' => 'details',
        '#open' => ($form_state->getValue(['add_action', 'plugin'], '_none') !== '_none'),
        '#title' => $this->t('Add action'),
        '#prefix' => '<div id="' . $wrapper_id . '">',
        '#suffix' => '</div>',
        '#weight' => $weight++,
        '#group' => 'action_plugins',
      ];
      $form['add_action']['table'] = [
        '#type' => 'table',
        '#weight' => 10,
        '#attributes' => [
          'class' => ['eca-form-add-action'],
        ],
      ];

      $form['add_action']['table'][0] = [
        '#parents' => ['add_action'],
      ];
      $action_options_label = [];
      $action_options_provider = [];
      foreach ($this->actionManager->getDecoratedActionManager()->getDefinitions() as $id => $definition) {
        // @see \Drupal\eca\Service\Actions::actions()
        if (!empty($definition['confirm_form_route_name'])) {
          // We cannot support actions that redirect to a confirmation form.
          // @see https://www.drupal.org/project/eca/issues/3279483
          continue;
        }
        if (($definition['id'] ?? NULL) === 'entity:save_action') {
          // We replace all save actions by one generic "Entity: save" action.
          continue;
        }
        if (($definition['provider'] ?? NULL) === 'core') {
          $module_name = 'Drupal core';
        }
        elseif (empty($definition['provider']) || !$this->moduleHandler->moduleExists($definition['provider'])) {
          $module_name = (string) $this->t('Unknown provider');
        }
        else {
          $module_name = (string) $this->moduleHandler->getName((string) $definition['provider']);
        }
        $action_options_label[$id] = (string) $definition['label'] . ' (' . $module_name . ')';
        $action_options_provider[$id] = $module_name . ' ' . (string) $definition['label'];
      }
      asort($action_options_provider, SORT_NATURAL);
      $action_options = ['_none' => $this->t('- Select -')];
      foreach (array_keys($action_options_provider) as $id) {
        $action_options[$id] = $action_options_label[$id];
      }
      if (count($action_options) === 1) {
        $this->messenger()->addWarning($this->t("There are no actions available to add. Install at least one module that provides an action, for example <em>ECA Base (eca_base)</em>."));
      }
      $form['add_action']['table'][0]['plugin'] = [
        '#type' => $select_widget,
        '#options' => $action_options,
        '#default_value' => '_none',
        '#empty_value' => '_none',
        '#weight' => 20,
        '#ajax' => [
          'callback' => [static::class, 'addActionAjax'],
          'wrapper' => $wrapper_id,
        ],
        '#executes_submit_callback' => TRUE,
        '#submit' => [[static::class, 'submitActionAjax']],
        '#required' => FALSE,
      ];
      if ($form_state->getValue(['add_action', 'plugin'], '_none') !== '_none') {
        $requested_plugin = trim($form_state->getValue(['add_action', 'plugin'], ''));
        $form['add_action']['table'][0]['link'] = [
          '#type' => 'link',
          '#attributes' => [
            'class' => ['button', 'button-action', 'button--primary'],
          ],
          '#title' => $this->t('Add action'),
          '#url' => Url::fromRoute("eca_cm.action.add", [
            'eca' => $eca->id(),
            'eca_action_plugin' => $requested_plugin,
          ]),
        ];
      }
      else {
        $form['add_action']['table'][0]['link'] = [
          '#type' => 'button',
          '#disabled' => TRUE,
          '#button_type' => 'primary',
          '#value' => $this->t('Add action'),
        ];
      }
    }

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
      '#weight' => ($weight += 10),
    ];

    $form['workflow'] = [
      '#type' => 'details',
      '#title' => $this->t('Workflow options'),
      '#group' => 'additional_settings',
      '#weight' => ($weight += 10),
    ];

    $form['workflow']['options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Default options'),
      '#title_display' => 'invisible',
      '#default_value' => $this->getWorkflowOptions(),
      '#options' => [
        'status' => $this->t('Enabled'),
      ],
    ];

    $form['workflow']['options']['status']['#description'] = $this->t('When checked, this ECA configuration will be immediately executed on the configured events.');

    $form['workflow']['version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Version'),
      '#description' => $this->t('When needed, you may specify a version of this model here, using any pattern you like. Examples: <em>Draft</em>, <em>1.0</em>'),
      '#default_value' => $eca->get('version'),
      '#required' => FALSE,
    ];

    $form['actions']['submit']['#value'] = $this->t('Save');
    $form['actions']['submit']['#submit'] = ['::submitForm', '::save'];
    $form['actions']['#weight'] = ($weight += 10);

    return $form;
  }

  /**
   * Prepares workflow options to be used in the 'checkboxes' form element.
   *
   * @return array
   *   Array of options ready to be used in #options.
   */
  protected function getWorkflowOptions(): array {
    /** @var \Drupal\eca\Entity\Eca $eca */
    $eca = $this->entity;
    $workflow_options = [
      'status' => $eca->status(),
    ];
    // Prepare workflow options to be used for 'checkboxes' form element.
    $keys = array_keys(array_filter($workflow_options));
    return array_combine($keys, $keys);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\eca\Entity\Eca $eca */
    $eca = $this->entity;

    $eca->set('id', trim($eca->id()));
    $eca->set('label', trim($eca->label()));
    $eca->set('status', (bool) $form_state->getValue(['options', 'status']));
    $eca->set('modeller', 'core');
    $status = $eca->save();

    $t_args = ['%name' => $eca->label()];
    if ($status == SAVED_UPDATED) {
      $message = $this->t('The ECA configuration %name has been updated.', $t_args);
    }
    elseif ($status == SAVED_NEW) {
      $message = $this->t('The ECA configuration %name has been added.', $t_args);
    }
    $this->messenger()->addStatus($message);

    if ($status == SAVED_UPDATED) {
      $form_state->setRedirectUrl($eca->toUrl('collection'));
    }
    elseif ($status == SAVED_NEW) {
      $form_state->setRedirectUrl($eca->toUrl('edit-form'));
    }
  }

  /**
   * Ajax callback for adding a new event.
   *
   * @param array $form
   *   The current form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The according form state.
   *
   * @return array
   *   The part of the form that got refreshed via Ajax.
   */
  public static function addEventAjax(array $form, FormStateInterface $form_state): array {
    return $form['add_event'];
  }

  /**
   * Submit ajax callback for adding a new event.
   *
   * @param array $form
   *   The current form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The according form state.
   */
  public static function submitEventAjax(array $form, FormStateInterface $form_state): void {
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for adding a new condition.
   *
   * @param array $form
   *   The current form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The according form state.
   *
   * @return array
   *   The part of the form that got refreshed via Ajax.
   */
  public static function addConditionAjax(array $form, FormStateInterface $form_state): array {
    return $form['add_condition'];
  }

  /**
   * Submit ajax callback for adding a new condition.
   *
   * @param array $form
   *   The current form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The according form state.
   */
  public static function submitConditionAjax(array $form, FormStateInterface $form_state): void {
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for adding a new action.
   *
   * @param array $form
   *   The current form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The according form state.
   *
   * @return array
   *   The part of the form that got refreshed via Ajax.
   */
  public static function addActionAjax(array $form, FormStateInterface $form_state): array {
    return $form['add_action'];
  }

  /**
   * Submit ajax callback for adding a new action.
   *
   * @param array $form
   *   The current form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The according form state.
   */
  public static function submitActionAjax(array $form, FormStateInterface $form_state): void {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\eca\Entity\Eca $eca */
    $eca = $this->entity;
    foreach (['events', 'conditions', 'actions'] as $type) {
      $config_arrays = $eca->get($type) ?? [];
      $i = 0;
      $form_type_key = $type === 'actions' ? 'action_plugins' : $type;
      foreach ($form_state->getValue([$form_type_key], []) as $config_key => &$config_array) {
        $i++;
        if (isset($config_arrays[$config_key])) {
          $config_arrays[$config_key]['weight'] = $config_array['weight'] ?? $i;
        }
      }
      unset($config_array);
      uasort($config_arrays, function ($a, $b) {
        return $a['weight'] > $b['weight'] ? 1 : -1;
      });
      foreach ($config_arrays as &$config_array) {
        unset($config_array['weight']);
      }
      $eca->set($type, $config_arrays);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity($entity, $form, $form_state) {
    /** @var \Drupal\eca\Entity\Eca $eca */
    $eca = $entity;
    $eca->set('label', $form_state->getValue('label', $eca->label()));
    $eca->set('id', $form_state->getValue('id', $eca->id()));
    $eca->set('status', (bool) $form_state->getValue(['options', 'status'], $eca->status()));
    $eca->set('version', $form_state->getValue(['version']));
  }

  /**
   * Converts the given config array into a readable string representation.
   *
   * @param array $config
   *   The config array.
   *
   * @return string
   *   The string representation.
   */
  protected function getConfigString(array $config): string {
    $config = array_filter($config, function ($value) {
      return $value !== '' && $value !== NULL;
    });
    foreach ($config as $key => $val) {
      if (is_array($val)) {
        $config[$key] = array_filter($config[$key], function ($value) {
          return $value !== '' && $value !== NULL;
        });
      }
    }
    array_walk_recursive($config, function (&$v) {
      if (is_string($v) && mb_strlen($v) > 25) {
        $v = substr($v, 0, 25) . '...';
      }
    });
    return nl2br(Html::escape(Yaml::encode($config)));
  }

  /**
   * Checks for possibly missing settings and prints according warnings.
   */
  protected function checkPossibleProblems(): void {
    /** @var \Drupal\eca\Entity\Eca $eca */
    $eca = $this->entity;
    if (!$eca->get('actions')) {
      // No actions there yet, does not make sense to print a warning already.
      return;
    }
    if ($this->getRequest()->getMethod() !== 'GET') {
      // Only show the warnings on the form page.
      return;
    }
    $warning_printed = FALSE;
    $eca_events = $eca->get('events') ?? [];
    foreach ($eca_events as $eca_event_id => $config_array) {
      if (empty($config_array['successors'])) {
        $event_label = (string) ($config_array['label'] ?? ($this->eventManager->getDefinition($config_array['plugin'])['label']) ?? $eca_event_id);
        $this->messenger()->addWarning($this->t("The event <em>@label</em> has no successor yet. <a href=\":url\">Add a successor</a>", [
          '@label' => $event_label,
          ':url' => Url::fromRoute("eca_cm.event.edit", [
              'eca' => $eca->id(),
              'eca_event_id' => $eca_event_id,
            ])->setAbsolute(FALSE)->toString(),
          ]));
          $warning_printed = TRUE;
      }
    }
    if ($warning_printed) {
      $this->messenger()->addWarning($this->t("The execution chain of an ECA process is defined with <em>successors</em>. Every event and action must define, which action should be executed next. The execution chain stops at the component that has no successor."));
    }
    elseif ($eca_events && !$eca->status()) {
      $this->messenger()->addWarning($this->t("This configuration is not enabled and thus will not be executed. You can enable it at the workflow options below."));
    }
  }

}
