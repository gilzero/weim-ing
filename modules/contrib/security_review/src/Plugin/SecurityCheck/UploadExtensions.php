<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Checks for unsafe extensions in the allowed extensions settings of fields.
 *
 * @SecurityCheck(
 *   id = "upload_extensions",
 *   title = @Translation("Allowed upload extensions"),
 *   description = @Translation("Checks for unsafe extensions in the allowed extensions settings of fields."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("Only safe extensions are allowed for uploaded files and images."),
 *   failure_message = @Translation("Unsafe file extensions are allowed in uploads."),
 *   info_message = @Translation("Module field is not enabled."),
 *   help = {
 *     @Translation("File and image fields allow for uploaded files. Some extensions are considered dangerous because the files can be evaluated and then executed in the browser. A malicious user could use this opening to gain control of your site. Review <a href=""/admin/reports/fields"">all fields on your site</a>."),
 *   }
 * )
 */
class UploadExtensions extends SecurityCheckBase {

  use MessengerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected ExtensionPathResolver $extensionPathResolver;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SecurityCheckBase|ContainerFactoryPluginInterface|static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->extensionPathResolver = $container->get('extension.path.resolver');
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function run(bool $cli = FALSE, &$sandbox = []): float {
    if (!$this->moduleHandler->moduleExists('field')) {
      $this->createResult(CheckResult::INFO);
      return 1;
    }

    $config = $this->securityReview->getCheckSettings($this->pluginId);
    $hush_upload_extensions = $config['hush_upload_extensions'] ?? [];

    if (!isset($sandbox['fids'])) {
      try {
        $field_ids = $this->entityTypeManager->getStorage('field_config')
          ->getQuery()
          ->accessCheck()
          ->execute();
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException) {
        $this->messenger()->addError('Error running name_passwords check.');

        return 1;
      }
      $sandbox = [];
      $sandbox['fids'] = $field_ids;
      $sandbox['progress'] = 0;
      $sandbox['max'] = count($field_ids);
      $sandbox['findings'] = [];
      $sandbox['hushed_findings'] = [];
    }

    $findings = [];
    $security_data = $this->securitySettings;
    // 100 at a time.
    $ids = array_slice($sandbox['fids'], $sandbox['progress'], 100);

    // Check field configuration entities.
    foreach (FieldConfig::loadMultiple($ids) as $field_config) {
      $extensions = $field_config->getSetting('file_extensions');
      if ($extensions != NULL) {
        $extensions = explode(' ', $extensions);
        $intersect = array_intersect($extensions, $security_data->unsafeExtensions());
        // $intersect holds the unsafe extensions this entity allows.
        foreach ($intersect as $unsafe_extension) {
          if (array_key_exists($unsafe_extension, $hush_upload_extensions)) {
            $hushed_findings[$field_config->id()][] = $unsafe_extension;
            $hushed_findings[$field_config->id()]['reason'] = $hush_upload_extensions[$unsafe_extension];
            continue;
          }
          $findings[$field_config->id()][] = $unsafe_extension;
        }
      }

      // Update our progress information.
      $sandbox['progress']++;
    }

    if (!empty($findings)) {
      $sandbox['findings'] = array_merge($sandbox['findings'], $findings);
    }

    if (!empty($hushed_findings)) {
      $sandbox['hushed_findings'] = array_merge($sandbox['hushed_findings'], $hushed_findings);
    }

    // Have we finished?
    if ($sandbox['progress'] == $sandbox['max']) {
      $result = CheckResult::SUCCESS;
      if (!empty($sandbox['findings'])) {
        $result = CheckResult::FAIL;
      }
      $this->createResult($result, $sandbox['findings'], NULL, $sandbox['hushed_findings']);

      return 1;
    }

    // Report we are not finished, and provide an estimation of the
    // completion level we reached.
    return $sandbox['progress'] / $sandbox['max'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $config = $this->securityReview->getCheckSettings($this->pluginId);
    $known_risky_upload_extensions = $config['hush_upload_extensions'] ?? [];
    $form = [];
    $form['hush_upload_extensions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hush known risky extensions'),
      '#description' => $this->t('File upload extensions to be skipped in future runs. Enter one value per line, in the format extension|reason.'),
      '#default_value' => implode("\n", $known_risky_upload_extensions),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $values): void {
    $hushed['hush_upload_extensions'] = [];
    if (!empty($values['hush_upload_extensions'])) {
      $hushed['hush_upload_extensions'] = preg_split("/\r\n|\n|\r/", $values['hush_upload_extensions']);
    }
    $this->securityReview->setCheckSettings($this->pluginId, $hushed);
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(array $findings, array $hushed = [], bool $returnString = FALSE): array|string {
    if (empty($findings) && empty($hushed)) {
      return [];
    }

    $output = $returnString ? '' : [];
    $paragraphs = [];

    $items = [];
    foreach ($findings as $entity_id => $unsafe_extensions) {
      $entity = FieldConfig::load($entity_id);
      foreach ($unsafe_extensions as $extension) {
        $item = $this->t(
          'Review extension <strong>@extension</strong> in <strong>@field</strong> field on bundle <strong>@bundle</strong> of type <strong>@type</strong>',
          [
            '@extension' => $extension,
            '@field' => $entity->label(),
            '@bundle' => $entity->getTargetBundle(),
            '@type' => $entity->getTargetEntityTypeId(),
          ]
        );

        // Try to get an edit url.
        try {
          $url_params = ['field_config' => $entity->id()];
          if (in_array($entity->getTargetEntityTypeId(), ['node', 'media'])) {
            $url_params[$entity->getTargetEntityTypeId() . '_type'] = $entity->getTargetBundle();
          }
          $items[] = Link::createFromRoute(
            $item,
            sprintf('entity.field_config.%s_field_edit_form', $entity->getTargetEntityTypeId()),
            $url_params
          );
        }
        catch (RouteNotFoundException) {
          $items[] = $item;
        }
      }
    }

    $hushed_items = [];
    foreach ($hushed as $entity_id => $hushed_item) {
      $entity = FieldConfig::load($entity_id);
      $item = $this->t(
        'Extension <strong>@extension</strong> in <strong>@field</strong> field on bundle <strong>@bundle</strong> of type <strong>@type</strong>. Reason: @reason',
        [
          '@extension' => $hushed_item[0],
          '@field' => $entity->label(),
          '@bundle' => $entity->getTargetBundle(),
          '@type' => $entity->getTargetEntityTypeId(),
          '@reason' => $hushed_item['reason'],
        ]
      );

      // Try to get an edit url.
      try {
        $url_params = ['field_config' => $entity->id()];
        if (in_array($entity->getTargetEntityTypeId(), ['node', 'media'])) {
          $url_params[$entity->getTargetEntityTypeId() . '_type'] = $entity->getTargetBundle();
        }
        $hushed_items[] = Link::createFromRoute(
          $item,
          sprintf('entity.field_config.%s_field_edit_form', $entity->getTargetEntityTypeId()),
          $url_params
        );
      }
      catch (RouteNotFoundException) {
        $hushed_items[] = $item;
      }
    }

    if ($returnString) {
      $output .= implode("", $paragraphs) . implode("", $items);
    }
    else {
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $items,
        '#hushed_items' => $hushed_items,
      ];
    }

    return $output;
  }

  /**
   * Generates an array of hushed extensions.
   *
   * @param array $values
   *   Array of values from config where key is numerical. Turn this into
   *   something more useable.
   *
   * @return array
   *   A key|value array of the hushed values.
   */
  protected function getHushedExtensions(array $values): array {
    $lines = [];
    foreach ($values as $value) {
      $parts = explode('|', $value);
      $lines[$parts[0]] = $parts[1];
    }
    return $lines;
  }

}
