<?php

namespace Drupal\push_framework\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\push_framework\ChannelPluginManager;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send a notification.
 *
 * @Action(
 *   id = "push_framework_notify",
 *   label = @Translation("Push a notification to a channel."),
 *   type = "user"
 * )
 */
class Notify extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The channel plugin manager.
   *
   * @var \Drupal\push_framework\ChannelPluginManager
   */
  protected ChannelPluginManager $channelPluginManager;

  /**
   * The entity type and bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * Constructs a new instance of the class.
   *
   * @param array $configuration
   *   An array of configuration values for the plugin.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\push_framework\ChannelPluginManager $channelPluginManager
   *   The channel plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type and bundle info service.
   */
  final public function __construct(array $configuration, $plugin_id, $plugin_definition, ChannelPluginManager $channelPluginManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->channelPluginManager = $channelPluginManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * Creates a new instance of the Notify class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container interface.
   * @param array $configuration
   *   An array of configuration values for the plugin.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return static
   *   The new instance of the Notify class.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('push_framework.channel.plugin.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIf($object instanceof UserInterface);
    try {
      /** @var \Drupal\push_framework\ChannelPluginInterface|null $channelPlugin */
      $channelPlugin = $this->channelPluginManager->createInstance($this->configuration['channel']);
      if ($channelPlugin === NULL) {
        $result = AccessResult::forbidden('The given channel does not exist.');
      }
      else {
        $bundles = $this->entityTypeBundleInfo->getBundleInfo('node');
        if (!isset($bundles[$this->configuration['node_type']])) {
          $result = AccessResult::forbidden('The given node type does not exist.');
        }
      }
    }
    catch (\Exception $e) {
      $result = AccessResult::forbidden($e->getMessage());
    }
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(mixed $object = NULL): void {
    /** @var \Drupal\push_framework\ChannelPluginInterface $channelPlugin */
    $channelPlugin = $this->channelPluginManager->createInstance($this->configuration['channel']);
    /** @var \Drupal\user\UserInterface $user */
    $user = $object;
    /** @var \Drupal\node\Entity\Node $node */
    $node = Node::create([
      'type' => $this->configuration['node_type'],
      'title' => $this->configuration['subject'],
      $this->configuration['body_field'] => $this->configuration['body'],
    ]);
    $node->in_preview = TRUE;
    $content = $channelPlugin->prepareContent($user, $node);
    $channelPlugin->send($user, $node, $content, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'channel' => '',
      'node_type' => 'page',
      'body_field' => 'body',
      'subject' => '',
      'body' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $channels = [];
    foreach ($this->channelPluginManager->getDefinitions() as $id => $definition) {
      $channels[$id] = $definition['label'];
    }
    if (empty($channels)) {
      return $form;
    }
    $bundles = [];
    foreach ($this->entityTypeBundleInfo->getBundleInfo('node') as $bundle => $bundleDef) {
      $bundles[$bundle] = $bundleDef['label'];
    }
    if (empty($bundles)) {
      return $form;
    }
    $form['channel'] = [
      '#type' => 'select',
      '#title' => $this->t('Channel'),
      '#options' => $channels,
      '#default_value' => $this->configuration['channel'],
      '#required' => TRUE,
    ];
    $form['node_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type'),
      '#options' => $bundles,
      '#default_value' => $this->configuration['node_type'],
      '#required' => TRUE,
    ];
    $form['body_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field name for content'),
      '#default_value' => $this->configuration['body_field'],
    ];
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject of notification'),
      '#default_value' => $this->configuration['subject'],
    ];
    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Content of notification'),
      '#default_value' => $this->configuration['body'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['channel'] = $form_state->getValue('channel');
    $this->configuration['node_type'] = $form_state->getValue('node_type');
    $this->configuration['body_field'] = $form_state->getValue('body_field');
    $this->configuration['subject'] = $form_state->getValue('subject');
    $this->configuration['body'] = $form_state->getValue('body');
  }

}
