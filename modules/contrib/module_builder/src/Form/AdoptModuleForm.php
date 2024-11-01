<?php

namespace Drupal\module_builder\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\module_builder\DrupalCodeBuilder;
use Drupal\module_builder\ExceptionHandler;
use DrupalCodeBuilder\Exception\SanityException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adopting an existing module as a new module entity.
 */
class AdoptModuleForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The installed profile.
   *
   * @var string
   */
  protected $profile;

  /**
   * The drupal code builder devel service.
   *
   * @var \Drupal\module_builder\DrupalCodeBuilder
   */
  protected $drupalCodeBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('extension.list.module'),
      $container->getParameter('install_profile'),
      $container->get('module_builder.drupal_code_builder'),
    );
  }

  /**
   * Creates a AdoptModuleForm instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list service.
   * @param string $profile
   *   The installed profile extension .
   * @param \Drupal\module_builder\DrupalCodeBuilder $drupal_code_builder
   *   The drupal code builder service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ModuleExtensionList $module_extension_list,
    string $profile,
    DrupalCodeBuilder $drupal_code_builder
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleExtensionList = $module_extension_list;
    $this->profile = $profile;
    $this->drupalCodeBuilder = $drupal_code_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'module_builder_adopt_module_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Try to get the DCB task handler we need on submit, so we can complain
    // about sanity now.
    try {
      $this->drupalCodeBuilder->getTask('AnalyseExtension');
    }
    catch (SanityException $e) {
      ExceptionHandler::handleSanityException($e);

      return $form;
    }

    $modules = $this->moduleExtensionList->getList();
    $module_options = [];
    foreach ($modules as $module_name => $module) {
      $module_options[$module_name] = $module->info['name'] . ' (' . $module_name . ')';
    }
    // WTF: The installed profile is in the list of modules. Remove it!
    unset($module_options[$this->profile]);

    // No idea what the sort order is, not documented.
    natcasesort($module_options);

    $form['module'] = [
      '#type' => 'radios',
      '#title' => $this->t("Module"),
      '#description' => $this->t("The existing module to adopt as a module entity."),
      '#required' => TRUE,
      '#options' => $module_options,
    ];

    $form['actions']['adopt'] = [
      '#type' => 'submit',
      '#value' => $this->t('Adopt module'),
      '#name' => 'adopt',
    ];
    $form['actions']['adopt_components'] = [
      '#type' => 'submit',
      '#value' => $this->t('Adopt module and adopt components'),
      '#name' => 'adopt_components',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Need to collect our own list of error like chumps because of this
    // core bug: https://www.drupal.org/project/drupal/issues/549020.
    $errors = [];
    $replacements = [];

    if ($module = $this->entityTypeManager->getStorage('module_builder_module')->load($form_state->getValue('module'))) {
      $errors[] = "The <a href=\":url\">'@module' module entity</a> already exists.";
      $replacements += [
        '@module' => $form_state->getValue('module'),
        ':url' => $module->toUrl()->toString(),
      ];
    }

    $module = $this->moduleExtensionList->get($form_state->getValue('module'));

    $analyse_extension_task = $this->drupalCodeBuilder->getTask('AnalyseExtension');
    $existing_extension = $analyse_extension_task->createExtensionFromCoreExtension($module);

    // Get the data item from the adopt task.
    $adopt_task = $this->drupalCodeBuilder->getTask('Adopt');
    $module_data = $adopt_task->adoptExtension($existing_extension);
    foreach ($module_data->validate() as $address => $violation_messages) {
      foreach ($violation_messages as $violation) {
        $errors[] = $violation;
      }
    }

    if ($errors) {
      $form_state->setError($form['module'], $this->t(implode('; ', $errors), $replacements));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $module = $this->moduleExtensionList->get($form_state->getValue('module'));

    $analyse_extension_task = $this->drupalCodeBuilder->getTask('AnalyseExtension');
    $existing_extension = $analyse_extension_task->createExtensionFromCoreExtension($module);

    // Get the data item from the adopt task.
    $adopt_task = $this->drupalCodeBuilder->getTask('Adopt');
    $module_data = $adopt_task->adoptExtension($existing_extension);

    // Create a new module entity.
    $module_entity = $this->entityTypeManager->getStorage('module_builder_module')->create([
      'id' => $module_data->root_name->value,
      'name' => $module_data->readable_name->value,
    ]);

    // Store the data in the new entity.
    $module_entity->set('data', $module_data->export());
    $module_entity->save();

    $this->messenger()->addMessage($this->t("Adopted the '@module' module entity</a>.", [
      '@module' => $form_state->getValue('module'),
    ]));

    $redirect_url = match ($form_state->getTriggeringElement()['#name']) {
      'adopt' => $module_entity->toUrl('edit-form'),
      'adopt_components' => $module_entity->toUrl('adopt-form'),
    };

    $form_state->setRedirectUrl($redirect_url);
  }

}
