<?php

namespace Drupal\eca_config\Plugin\ECA\Event;

use Drupal\Core\Config\ConfigCollectionEvents;
use Drupal\Core\Config\ConfigCollectionInfo;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\Importer\MissingContentEvent;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Attributes\Token;
use Drupal\eca\Event\Tag;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Plugin\ECA\Event\EventBase;

/**
 * Plugin implementation of the ECA Events for config.
 *
 * @EcaEvent(
 *   id = "config",
 *   deriver = "Drupal\eca_config\Plugin\ECA\Event\ConfigEventDeriver",
 *   eca_version_introduced = "1.0.0"
 * )
 */
class ConfigEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    return [
      'delete' => [
        'label' => 'Delete config',
        'event_name' => ConfigEvents::DELETE,
        'event_class' => ConfigCrudEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'collection_info' => [
        'label' => 'Collect information on all config collections',
        'event_name' => ConfigCollectionEvents::COLLECTION_INFO,
        'event_class' => ConfigCollectionInfo::class,
        'tags' => Tag::READ | Tag::PERSISTENT | Tag::AFTER,
      ],
      'import' => [
        'label' => 'Import config',
        'event_name' => ConfigEvents::IMPORT,
        'event_class' => ConfigImporterEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'import_missing_content' => [
        'label' => 'Import config but content missing',
        'event_name' => ConfigEvents::IMPORT_MISSING_CONTENT,
        'event_class' => MissingContentEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'import_validate' => [
        'label' => 'Import config validation',
        'event_name' => ConfigEvents::IMPORT_VALIDATE,
        'subscriber_priority' => 1024,
        'event_class' => ConfigImporterEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'rename' => [
        'label' => 'Rename config',
        'event_name' => ConfigEvents::RENAME,
        'event_class' => ConfigRenameEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'save' => [
        'label' => 'Save config',
        'event_name' => ConfigEvents::SAVE,
        'event_class' => ConfigCrudEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    if ($this->eventClass() === ConfigCrudEvent::class) {
      $form['help'] = [
        '#type' => 'markup',
        '#markup' => $this->t('This event provides three tokens:<ul><li><em>"[config:*]"</em> to access properties of a configuration</li><li><em>"[config_original:*]"</em> to access the original unchanged values</li><li>and <em>"[config_name]"</em> to get the machine name of a configuration (e.g. "system.site")</li></ul>'),
        '#weight' => 10,
        '#description' => $this->t('This event provides three tokens:<ul><li><em>"[config:*]"</em> to access properties of a configuration</li><li><em>"[config_original:*]"</em> to access the original unchanged values</li><li>and <em>"[config_name]"</em> to get the machine name of a configuration (e.g. "system.site")</li></ul>'),
      ];
    }
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  #[Token(
    name: 'config',
    description: 'The configuration with all current values.',
  )]
  #[Token(
    name: 'config_original',
    description: 'The configuration with all original values plus overrides.',
  )]
  #[Token(
    name: 'config_name',
    description: 'The name of the configuration.',
  )]
  public function getData(string $key): mixed {
    $event = $this->event;
    if ($event instanceof ConfigCrudEvent) {
      $config = $event->getConfig();
      switch ($key) {
        case 'config':
          return DataTransferObject::create($config->get());

        case 'config_original':
          return DataTransferObject::create($config->getOriginal());

        case 'config_name':
          return $config->getName();
      }
    }
    return parent::getData($key);
  }

}
