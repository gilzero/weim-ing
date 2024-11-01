<?php

namespace Drupal\eca_cm\Controller;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\eca\Entity\Eca;
use Drupal\eca\PluginManager\Action;
use Drupal\eca\PluginManager\Condition;
use Drupal\eca\PluginManager\Event;
use Drupal\eca\Service\Modellers;
use Drupal\eca_cm\Form\EcaActionDeleteForm;
use Drupal\eca_cm\Form\EcaActionForm;
use Drupal\eca_cm\Form\EcaConditionDeleteForm;
use Drupal\eca_cm\Form\EcaConditionForm;
use Drupal\eca_cm\Form\EcaEventDeleteForm;
use Drupal\eca_cm\Form\EcaEventForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller class for Classic modeller integration into ECA.
 */
class CoreModeller extends ControllerBase {

  /**
   * ECA modeller service.
   *
   * @var \Drupal\eca\Service\Modellers
   */
  protected Modellers $modellerServices;

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
   * Constructs a new CoreModeller object.
   *
   * @param \Drupal\eca\Service\Modellers $modeller_services
   *   The ECA modeller service.
   * @param \Drupal\eca\PluginManager\Event $event_manager
   *   The event plugin manager.
   * @param \Drupal\eca\PluginManager\Condition $condition manager
   *   The condition manager.
   * @param \Drupal\eca\PluginManager\Action $action_manager
   *   The action manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(Modellers $modeller_services, Event $event_manager, Condition $condition_manager, Action $action_manager, FormBuilderInterface $form_builder) {
    $this->modellerServices = $modeller_services;
    $this->eventManager = $event_manager;
    $this->conditionManager = $condition_manager;
    $this->actionManager = $action_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eca.service.modeller'),
      $container->get('plugin.manager.eca.event'),
      $container->get('plugin.manager.eca.condition'),
      $container->get('plugin.manager.eca.action'),
      $container->get('form_builder')
    );
  }

  /**
   * Callback to add a new Core model.
   *
   * @return array
   *   The render array for editing the new model.
   */
  public function add(): array {
    /** @var \Drupal\eca_cm\Plugin\ECA\Modeller\Core $modeller */
    $modeller = $this->modellerServices->getModeller('core');
    $modeller->setConfigEntity(Eca::create([
      'status' => FALSE,
      'modeller' => 'core',
    ]));
    $modeller->createNewModel('', '');
    return $modeller->edit();
  }

  public function addEvent(Eca $eca, string $eca_event_plugin): array {
    if ('core' !== $eca->get('modeller')) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\eca\Plugin\ECA\Event\EventInterface $plugin */
    $plugin = $this->eventManager->createInstance($eca_event_plugin);

    $config_key = str_replace(':', '_', $plugin->getPluginId());
    $config_key = substr($config_key, 0, 32);
    $plugins_array = $eca->get('events') ?? [];
    if (isset($plugins_array[$config_key])) {
      $i = 1;
      while (isset($plugins_array[$config_key . '_' . $i])) {
        $i++;
      }
      if (strlen($config_key) + strlen('_' . $i) > 32) {
        $config_key = substr($config_key, 0, - strlen('_' . $i));
      }
      $config_key .= '_' . $i;
    }

    $config_array = [
      'label' => $plugin->getPluginDefinition()['label'],
      'configuration' => $plugin instanceof ConfigurableInterface ? $plugin->getConfiguration() : [],
    ];
    return $this->formBuilder->getForm(EcaEventForm::class, $eca, $plugin, $config_key, $config_array);
  }

  public function editEvent(Eca $eca, string $eca_event_id): array {
    if ('core' !== $eca->get('modeller')) {
      throw new NotFoundHttpException();
    }

    $config_key = $eca_event_id;

    $plugins_array = $eca->get('events') ?? [];
    if (!isset($plugins_array[$config_key])) {
      throw new NotFoundHttpException();
    }

    $config_array = $plugins_array[$config_key];
    $plugins = $eca->getPluginCollections();
    foreach ($plugins['events.' . $config_key] as $plugin) {}

    return $this->formBuilder->getForm(EcaEventForm::class, $eca, $plugin, $config_key, $config_array);
  }

  public function deleteEvent(Eca $eca, string $eca_event_id): array {
    if ('core' !== $eca->get('modeller')) {
      throw new NotFoundHttpException();
    }

    $config_key = $eca_event_id;

    $plugins_array = $eca->get('events') ?? [];
    if (!isset($plugins_array[$config_key])) {
      throw new NotFoundHttpException();
    }

    $config_array = $plugins_array[$config_key];
    $plugins = $eca->getPluginCollections();
    foreach ($plugins['events.' . $config_key] as $plugin) {}

    return $this->formBuilder->getForm(EcaEventDeleteForm::class, $eca, $plugin, $config_key, $config_array);
  }

  public function addCondition(Eca $eca, string $eca_condition_plugin): array {
    if ('core' !== $eca->get('modeller')) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\eca\Plugin\ECA\Condition\ConditionInterface $plugin */
    $plugin = $this->conditionManager->createInstance($eca_condition_plugin);

    $config_key = str_replace(':', '_', $plugin->getPluginId());
    $config_key = substr($config_key, 0, 32);
    $plugins_array = $eca->get('conditions') ?? [];
    if (isset($plugins_array[$config_key])) {
      $i = 1;
      while (isset($plugins_array[$config_key . '_' . $i])) {
        $i++;
      }
      if (strlen($config_key) + strlen('_' . $i) > 32) {
        $config_key = substr($config_key, 0, - strlen('_' . $i));
      }
      $config_key .= '_' . $i;
    }

    $config_array = [
      'label' => $plugin->getPluginDefinition()['label'],
      'configuration' => $plugin instanceof ConfigurableInterface ? $plugin->getConfiguration() : [],
    ];
    return $this->formBuilder->getForm(EcaConditionForm::class, $eca, $plugin, $config_key, $config_array);
  }

  public function editCondition(Eca $eca, string $eca_condition_id): array {
    if ('core' !== $eca->get('modeller')) {
      throw new NotFoundHttpException();
    }

    $config_key = $eca_condition_id;

    $plugins_array = $eca->get('conditions') ?? [];
    if (!isset($plugins_array[$config_key])) {
      throw new NotFoundHttpException();
    }

    $config_array = $plugins_array[$config_key];
    $plugins = $eca->getPluginCollections();
    foreach ($plugins['conditions.' . $config_key] as $plugin) {}

    return $this->formBuilder->getForm(EcaConditionForm::class, $eca, $plugin, $config_key, $config_array);
  }

  public function deleteCondition(Eca $eca, string $eca_condition_id): array {
    if ('core' !== $eca->get('modeller')) {
      throw new NotFoundHttpException();
    }

    $config_key = $eca_condition_id;

    $plugins_array = $eca->get('conditions') ?? [];
    if (!isset($plugins_array[$config_key])) {
      throw new NotFoundHttpException();
    }

    $config_array = $plugins_array[$config_key];
    $plugins = $eca->getPluginCollections();
    foreach ($plugins['conditions.' . $config_key] as $plugin) {}

    return $this->formBuilder->getForm(EcaConditionDeleteForm::class, $eca, $plugin, $config_key, $config_array);
  }

  public function addAction(Eca $eca, string $eca_action_plugin): array {
    if ('core' !== $eca->get('modeller')) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\Core\Action\ActionInterface $plugin */
    $plugin = $this->actionManager->createInstance($eca_action_plugin);

    $config_key = str_replace(':', '_', $plugin->getPluginId());
    $config_key = substr($config_key, 0, 32);
    $plugins_array = $eca->get('actions') ?? [];
    if (isset($plugins_array[$config_key])) {
      $i = 1;
      while (isset($plugins_array[$config_key . '_' . $i])) {
        $i++;
      }
      if (strlen($config_key) + strlen('_' . $i) > 32) {
        $config_key = substr($config_key, 0, - strlen('_' . $i));
      }
      $config_key .= '_' . $i;
    }

    $config_array = [
      'label' => $plugin->getPluginDefinition()['label'],
      'configuration' => $plugin instanceof ConfigurableInterface ? $plugin->getConfiguration() : [],
    ];
    return $this->formBuilder->getForm(EcaActionForm::class, $eca, $plugin, $config_key, $config_array);
  }

  public function editAction(Eca $eca, string $eca_action_id): array {
    if ('core' !== $eca->get('modeller')) {
      throw new NotFoundHttpException();
    }

    $config_key = $eca_action_id;

    $plugins_array = $eca->get('actions') ?? [];
    if (!isset($plugins_array[$config_key])) {
      throw new NotFoundHttpException();
    }

    $config_array = $plugins_array[$config_key];
    $plugins = $eca->getPluginCollections();
    foreach ($plugins['actions.' . $config_key] as $plugin) {}

    return $this->formBuilder->getForm(EcaActionForm::class, $eca, $plugin, $config_key, $config_array);
  }

  public function deleteAction(Eca $eca, string $eca_action_id): array {
    if ('core' !== $eca->get('modeller')) {
      throw new NotFoundHttpException();
    }

    $config_key = $eca_action_id;

    $plugins_array = $eca->get('actions') ?? [];
    if (!isset($plugins_array[$config_key])) {
      throw new NotFoundHttpException();
    }

    $config_array = $plugins_array[$config_key];
    $plugins = $eca->getPluginCollections();
    foreach ($plugins['actions.' . $config_key] as $plugin) {}

    return $this->formBuilder->getForm(EcaActionDeleteForm::class, $eca, $plugin, $config_key, $config_array);
  }

}
