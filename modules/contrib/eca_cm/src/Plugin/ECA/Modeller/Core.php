<?php

namespace Drupal\eca_cm\Plugin\ECA\Modeller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Entity\Model;
use Drupal\eca\Plugin\ECA\Modeller\ModellerBase;
use Drupal\eca\Plugin\ECA\Modeller\ModellerInterface;
use Drupal\eca_cm\Form\EcaForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Classic modeller plugin.
 *
 * @EcaModeller(
 *   id = "core",
 *   label = "ECA Classic Modeller",
 *   description = "Simple modeller using the Drupal form API."
 * )
 */
class Core extends ModellerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    /** @var \Drupal\eca_cm\Plugin\ECA\Modeller\Core $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->formBuilder = $container->get('form_builder');
    $instance->container = $container;
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function generateId(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function createNewModel(string $id, string $model_data, string $filename = NULL, bool $save = FALSE): Eca {
    return $this->eca;
  }

  /**
   * {@inheritdoc}
   */
  public function save(string $data, string $filename = NULL, bool $status = NULL): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function updateModel(Model $model): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function enable(): ModellerInterface {
    $this->eca
      ->setStatus(TRUE)
      ->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disable(): ModellerInterface {
    $this->eca
      ->setStatus(FALSE)
      ->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clone(): ?Eca {
    $type = $this->eca->getEntityType();
    $clone = Eca::create([
      $type->getKey('id') => $this->eca->id() . '_copy',
      $type->getKey('uuid') => $this->uuid->generate(),
      $type->getKey('label') => $this->eca->label() . ' (copy)',
      'modeller' => 'core',
      'status' => FALSE,
    ] + $this->eca->toArray());
    $clone->save();
    return $clone;
  }

  /**
   * {@inheritdoc}
   */
  public function export(): ?Response {
    if (!method_exists($this, 'isExportable')) {
      // Since ECA v1.2 all modellers are exportable.
      return parent::export();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilename(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setModeldata(string $data): ModellerInterface {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getModeldata(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTags(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDocumentation(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function readComponents(Eca $eca): ModellerInterface {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function exportTemplates(): ModellerInterface {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function edit(): array {
    $form_object = EcaForm::create($this->container);
    $form_object->setEntity($this->eca);
    return $this->formBuilder->getForm($form_object);
  }

  /**
   * {@inheritdoc}
   */
  public function isEditable(): bool {
    return TRUE;
  }

}
