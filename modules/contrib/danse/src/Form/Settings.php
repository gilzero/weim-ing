<?php

namespace Drupal\danse\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\danse\RecipientSelectionManager;
use Drupal\danse\Service;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Subscription Audit Notification Event settings for this site.
 */
final class Settings extends ConfigFormBase {

  /**
   * The DANSE service.
   *
   * @var \Drupal\danse\Service
   */
  protected Service $danse;

  /**
   * The recipient selection plugin manager.
   *
   * @var \Drupal\danse\RecipientSelectionManager
   */
  protected RecipientSelectionManager $recipientSelectionManager;

  /**
   * DANSE settings form constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\danse\Service $danse
   *   The DANSE services.
   * @param \Drupal\danse\RecipientSelectionManager $recipient_selection_manager
   *   The recipient selection plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, Service $danse, RecipientSelectionManager $recipient_selection_manager) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->danse = $danse;
    $this->recipientSelectionManager = $recipient_selection_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): Settings {
    return new Settings(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('danse.service'),
      $container->get('plugin.manager.danse.recipient.selection')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'danse_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['danse.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('danse.settings');

    $form['subscriptions_as_tab_events'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Event subscription settings as tab in user profile'),
      '#default_value' => $config->get('subscriptions_as_tab.events'),
      '#description' => $this->t('By default, subscription settings will be available as a tab in the user profile. Turn this off to include them in the user profile edit form.'),
    ];
    $form['subscriptions_as_tab_view'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active subscriptions as tab in user profile'),
      '#default_value' => $config->get('subscriptions_as_tab.view'),
      '#description' => $this->t('By default, active subscriptions will be available as a tab in the user profile. Turn this off to include them in the user profile edit form.'),
    ];
    $form['subscriptions_as_tab_expand'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expand subscriptions list in user profile'),
      '#default_value' => $config->get('subscriptions_as_tab.expand'),
      '#description' => $this->t('By default, the subscriptions list will be collapsed. Turn this on to expand it.'),
    ];
    $form['recipient_selection_plugin'] = [
      '#type' => 'details',
      '#title' => $this->t('Recipient selection plugins'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => $this->t('To determine the recipients for each type of event below, please select a plugin from each available list. These plugins will determine recipients who will receive pushed notifications regardless of whether the user has subscribed or not. No user will be notified more than once per event, regardless how many plugins you select. If you prefer subscriptions to automatic push notifications, user subscription settings are found on the edit page of each entity type and no selections of plugins need to be made from the options below.'),
    ];
    foreach ($this->danse->getPluginInstances() as $id => $plugin) {
      $default = $config->get('recipient_selection_plugin.' . $id) ?? '';
      $form['recipient_selection_plugin'][$id] = [
        '#type' => 'select',
        '#title' => $this->t('@label events', ['@label' => $plugin->label()]),
        '#options' => $this->recipientSelectionManager->pluginListForSelect(),
        '#default_value' => explode(',', $default),
        '#required' => FALSE,
        '#multiple' => TRUE,
      ];
    }
    $form['prune'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings to prune database records'),
      '#open' => FALSE,
      '#tree' => TRUE,
      '#description' => $this->t('Select, how many or for how long the records of each event type should be kept in the database.'),
    ];
    foreach ($this->danse->getPluginInstances() as $id => $plugin) {
      $form['prune'][$id] = [
        '#type' => 'fieldset',
        '#title' => $plugin->label(),
        '#attributes' => [
          'class' => [
            'container-inline',
            'fieldgroup',
            'form-composite',
          ],
        ],
      ];
      $form['prune'][$id]['value'] = [
        '#type' => 'number',
        '#min' => 0,
        '#size' => 5,
        '#default_value' => $config->get('prune.' . $id . '.value'),
        '#required' => FALSE,
        '#states' => [
          'invisible' => [
            'select[name="prune[' . $id . '][type]"]' => ['value' => 'all'],
          ],
        ],
      ];
      $form['prune'][$id]['type'] = [
        '#type' => 'select',
        '#options' => [
          'all' => $this->t('All / forever'),
          'records' => $this->t('Database records'),
          'days' => $this->t('Days'),
          'weeks' => $this->t('Weeks'),
          'months' => $this->t('Months'),
          'years' => $this->t('Years'),
        ],
        '#default_value' => $config->get('prune.' . $id . '.type'),
        '#required' => FALSE,
      ];

    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('danse.settings');
    $config->set('subscriptions_as_tab.events',
      $form_state->getValue('subscriptions_as_tab_events'));
    $config->set('subscriptions_as_tab.view',
      $form_state->getValue('subscriptions_as_tab_view'));
    $config->set('subscriptions_as_tab.expand',
      $form_state->getValue('subscriptions_as_tab_expand'));
    foreach ($this->danse->getPluginInstances() as $id => $plugin) {
      $config->set('recipient_selection_plugin.' . $id,
        implode(',', $form_state->getValue(['recipient_selection_plugin', $id])));
      $config->set('prune.' . $id . '.value',
        $form_state->getValue(['prune', $id, 'value']));
      $config->set('prune.' . $id . '.type',
        $form_state->getValue(['prune', $id, 'type']));
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
