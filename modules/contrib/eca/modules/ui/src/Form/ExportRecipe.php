<?php

namespace Drupal\eca_ui\Form;

use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Service\ExportRecipe as ExportRecipeService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Export a model as a recipe.
 */
class ExportRecipe extends FormBase {

  /**
   * The export recipe service.
   *
   * @var \Drupal\eca\Service\ExportRecipe
   */
  protected ExportRecipeService $exportRecipe;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected FileSystem $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $form = parent::create($container);
    $form->exportRecipe = $container->get('eca.export.recipe');
    $form->fileSystem = $container->get('file_system');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'eca_export_recipe';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?Eca $eca = NULL): array {
    if ($eca === NULL) {
      return $form;
    }
    $form['eca'] = ['#type' => 'hidden', '#value' => $eca->id()];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $this->exportRecipe->defaultName($eca),
      '#required' => TRUE,
    ];
    $form['namespace'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Namespace'),
      '#default_value' => ExportRecipeService::DEFAULT_NAMESPACE,
      '#required' => TRUE,
    ];
    $form['destination'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination'),
      '#default_value' => ExportRecipeService::DEFAULT_DESTINATION,
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $destination = $form_state->getValue('destination');
    $configDestination = $destination . '/config';
    if (!file_exists($configDestination)) {
      if (!$this->fileSystem->mkdir($configDestination, FileSystem::CHMOD_DIRECTORY, TRUE)) {
        $form_state->setErrorByName('destination', $this->t('The destination does not exist or is not writable.'));
      }
    }
    elseif (!is_writable($configDestination)) {
      $form_state->setErrorByName('destination', $this->t('The destination is not writable.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\eca\Entity\Eca $eca */
    $eca = Eca::load($form_state->getValue('eca'));
    $this->exportRecipe->doExport($eca, $form_state->getValue('name'), $form_state->getValue('namespace'), $form_state->getValue('destination'));
    $form_state->setRedirect('entity.eca.collection');
  }

}
