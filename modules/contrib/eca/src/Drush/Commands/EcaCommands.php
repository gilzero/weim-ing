<?php

namespace Drupal\eca\Drush\Commands;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Service\ExportRecipe;
use Drupal\eca\Service\Modellers;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Drush command file.
 */
final class EcaCommands extends DrushCommands {

  /**
   * ECA config entity storage manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $configStorage;

  /**
   * Constructs an EcaCommands object.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    private readonly Modellers $ecaServiceModeller,
    private readonly ExportRecipe $exportRecipe,
  ) {
    parent::__construct();
    $this->configStorage = $entityTypeManager->getStorage('eca');
  }

  /**
   * Return an instance of these Drush commands.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @return \Drupal\eca\Drush\Commands\EcaCommands
   *   The instance of Drush commands.
   */
  public static function create(ContainerInterface $container): EcaCommands {
    return new EcaCommands(
      $container->get('entity_type.manager'),
      $container->get('eca.service.modeller'),
      $container->get('eca.export.recipe'),
    );
  }

  /**
   * Import a single ECA file.
   */
  #[CLI\Command(name: 'eca:import', aliases: [])]
  #[CLI\Argument(name: 'pluginId', description: 'The id of the modeller plugin.')]
  #[CLI\Argument(name: 'filename', description: 'The file name to import, relative to the Drupal root or absolute.')]
  #[CLI\Usage(name: 'eca:import camunda mymodel.xml', description: 'Import a single ECA file.')]
  public function import(string $pluginId, string $filename): void {
    $modeller = $this->ecaServiceModeller->getModeller($pluginId);
    if ($modeller === NULL) {
      $this->io()->error('This modeller plugin does not exist.');
      return;
    }
    if (!file_exists($filename)) {
      $this->io()->error('This file does not exist.');
      return;
    }
    try {
      $modeller->save(file_get_contents($filename), $filename);
    }
    catch (\LogicException | EntityStorageException $e) {
      $this->io()->error($e->getMessage());
    }
  }

  /**
   * Update all previously imported ECA files.
   */
  #[CLI\Command(name: 'eca:reimport', aliases: [])]
  #[CLI\Usage(name: 'eca:reimport', description: 'Update all previously imported ECA files.')]
  public function reimportAll(): void {
    /** @var \Drupal\eca\Entity\Eca $eca */
    foreach ($this->configStorage->loadMultiple() as $eca) {
      $modeller = $this->ecaServiceModeller->getModeller($eca->get('modeller'));
      if ($modeller === NULL) {
        $this->logger->error('This modeller plugin ' . $eca->get('modeller') . ' does not exist.');
        continue;
      }
      if ($modeller->isEditable()) {
        // Editable models have no external files.
        continue;
      }
      $model = $eca->getModel();
      $filename = $model->getFilename();
      if (!file_exists($filename)) {
        $this->logger->error('This file ' . $filename . ' does not exist.');
        continue;
      }
      try {
        $modeller->save(file_get_contents($filename), $filename);
      }
      catch (\LogicException | EntityStorageException $e) {
        $this->io()->error($e->getMessage());
      }
    }
  }

  /**
   * Export templates for all ECA modellers.
   */
  #[CLI\Command(name: 'eca:export:templates', aliases: [])]
  #[CLI\Usage(name: 'eca:export:templates', description: 'Export templates for all ECA modellers.')]
  public function exportTemplates(): void {
    foreach ($this->ecaServiceModeller->getModellerDefinitions() as $plugin_id => $definition) {
      $modeller = $this->ecaServiceModeller->getModeller($plugin_id);
      if ($modeller === NULL) {
        $this->io()->error('This modeller plugin does not exist.');
        continue;
      }
      $modeller->exportTemplates();
    }
  }

  /**
   * Updates all existing ECA entities calling ::updateModel in their modeller.
   *
   * It is the modeller's responsibility to load all existing plugins and find
   * out if the model data, which is proprietary to them, needs to be updated.
   */
  #[CLI\Command(name: 'eca:update', aliases: [])]
  #[CLI\Usage(name: 'eca:update', description: 'Update all models if plugins got changed.')]
  public function updateAllModels(): void {
    /** @var \Drupal\eca\Entity\Eca $eca */
    foreach ($this->configStorage->loadMultiple() as $eca) {
      $modeller = $this->ecaServiceModeller->getModeller($eca->get('modeller'));
      if ($modeller === NULL) {
        $this->logger->error('This modeller plugin ' . $eca->get('modeller') . ' does not exist.');
        continue;
      }
      $model = $eca->getModel();
      $modeller->setConfigEntity($eca);
      if ($modeller->updateModel($model)) {
        $filename = $model->getFilename();
        if ($filename && file_exists($filename)) {
          file_put_contents($filename, $model->getModeldata());
        }
        try {
          $modeller->save($model->getModeldata(), $filename);
        }
        catch (\LogicException | EntityStorageException $e) {
          $this->io()->error($e->getMessage());
        }
      }
    }
  }

  /**
   * Disable all existing ECA entities.
   */
  #[CLI\Command(name: 'eca:disable', aliases: [])]
  #[CLI\Usage(name: 'eca:disable', description: 'Disable all models.')]
  public function disableAllModels(): void {
    /** @var \Drupal\eca\Entity\Eca $eca */
    foreach ($this->configStorage->loadMultiple() as $eca) {
      $modeller = $this->ecaServiceModeller->getModeller($eca->get('modeller'));
      if ($modeller === NULL) {
        $this->logger->error('This modeller plugin ' . $eca->get('modeller') . ' does not exist.');
        continue;
      }
      $modeller
        ->setConfigEntity($eca)
        ->disable();
    }
  }

  /**
   * Enable all existing ECA entities.
   */
  #[CLI\Command(name: 'eca:enable', aliases: [])]
  #[CLI\Usage(name: 'eca:enable', description: 'Enable all models.')]
  public function enableAllModels(): void {
    /** @var \Drupal\eca\Entity\Eca $eca */
    foreach ($this->configStorage->loadMultiple() as $eca) {
      $modeller = $this->ecaServiceModeller->getModeller($eca->get('modeller'));
      if ($modeller === NULL) {
        $this->logger->error('This modeller plugin ' . $eca->get('modeller') . ' does not exist.');
        continue;
      }
      $modeller
        ->setConfigEntity($eca)
        ->enable();
    }
  }

  /**
   * Rebuild the state of subscribed events.
   */
  #[CLI\Command(name: 'eca:subscriber:rebuild', aliases: [])]
  #[CLI\Usage(name: 'eca:subscriber:rebuild', description: 'Rebuild the state of subscribed events.')]
  public function rebuildSubscribedEvents(): void {
    /** @var \Drupal\eca\Entity\EcaStorage $storage */
    $storage = $this->configStorage;
    $storage->rebuildSubscribedEvents();
  }

  /**
   * Export a model as a recipe.
   */
  #[CLI\Command(name: 'eca:model:export', aliases: [])]
  #[CLI\Argument(name: 'id', description: 'The ID of the model.')]
  #[CLI\Usage(name: 'eca:model:export MODELID', description: 'Export the model with the given ID as a recipe.')]
  public function exportModel(string $id): void {
    /** @var \Drupal\eca\Entity\Eca|null $eca */
    $eca = $this->configStorage->load($id);
    if ($eca === NULL) {
      $this->io()->error('The given ECA model does not exist!');
      return;
    }
    $this->exportRecipe->doExport($eca);
  }

}
