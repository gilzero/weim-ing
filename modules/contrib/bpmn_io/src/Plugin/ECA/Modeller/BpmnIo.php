<?php

namespace Drupal\bpmn_io\Plugin\ECA\Modeller;

use Drupal\bpmn_io\Form\Modeller;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Url;
use Drupal\eca_modeller_bpmn\ModellerBpmnBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the ECA Modeller.
 *
 * @EcaModeller(
 *   id = "bpmn_io",
 *   label = "BPMN.iO",
 *   description = "BPMN modeller with a feature-rich UI."
 * )
 */
class BpmnIo extends ModellerBpmnBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected FormBuilder $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->formBuilder = $container->get('form_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function xmlNsPrefix(): string {
    return 'bpmn2:';
  }

  /**
   * {@inheritdoc}
   */
  public function isEditable(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function edit(): array {
    $form = $this->formBuilder->getForm(Modeller::class);
    if (isset($form['gin_sidebar'])) {
      $form['gin_sidebar']['property_panel'] = ['#markup' => '<div class="property-panel"></div>'];
      $form['gin_sidebar']['token_browser'] = $this->tokenBrowserService->getTokenBrowserMarkup();
      $extras = '';
    }
    else {
      $extras = '<div class="property-panel in-canvas"></div>';
      $form['actions']['tb'] = $this->tokenBrowserService->getTokenBrowserMarkup();
    }
    return [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'bpmn-io',
      ],
      'canvas' => [
        '#prefix' => '<div class="canvas"></div>' . $extras,
      ],
      'form' => $form,
      '#attached' => [
        'library' => [
          'bpmn_io/ui',
        ],
        'drupalSettings' => [
          'bpmn_io' => [
            'id' => $this->eca->id(),
            'isnew' => $this->eca->isNew(),
            'modeller' => 'bpmn_io',
            'bpmn' => $this->eca->getModel()->getModeldata(),
            'templates' => $this->getTemplates(),
            'save_url' => Url::fromRoute('eca.save', ['modeller_id' => 'bpmn_io'])->toString(),
            'collection_url' => Url::fromRoute('entity.eca.collection')->toString(),
          ],
        ],
      ],
    ];
  }

}
