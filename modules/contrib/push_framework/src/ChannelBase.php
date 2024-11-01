<?php

namespace Drupal\push_framework;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\Token;
use Drupal\push_framework\Event\ChannelPostRender;
use Drupal\push_framework\Event\ChannelPreBuild;
use Drupal\push_framework\Event\ChannelPrepareTemplates;
use Drupal\push_framework\Event\ChannelPreRender;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Base class for push_framework plugins.
 */
abstract class ChannelBase extends PluginBase implements ChannelPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Indicator if this channel is active.
   *
   * @var bool
   */
  protected bool $active;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The render service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * The Push Framework configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $defaultConfig;

  /**
   * The channel plugin configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $pluginConfig;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  final public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger, RendererInterface $renderer, Token $token, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->renderer = $renderer;
    $this->token = $token;
    $this->eventDispatcher = $event_dispatcher;
    $this->defaultConfig = $config_factory->get('push_framework.settings');
    $this->pluginConfig = $config_factory->get($this->getConfigName());
    $this->active = (bool) $this->pluginConfig->get('active');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.push_framework'),
      $container->get('renderer'),
      $container->get('token'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  final public function isActive(): bool {
    return $this->active;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * Get the configuration value for a given key.
   *
   * @param string $key
   *   The key of the configuration.
   * @param mixed $default
   *   The default value.
   *
   * @return array|mixed|null
   *   The configuration value from either the plugin, or from the default
   *   config. If that doesn't exist either, it returns the given default value.
   */
  private function getConfigValue(string $key, mixed $default): mixed {
    $value = $this->pluginConfig->get($key);
    if (empty($value) || $this->pluginConfig->get('use_default_settings')) {
      $value = $this->defaultConfig->get($key);
      if (empty($value)) {
        $value = $default;
      }
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  final public function prepareContent(UserInterface $user, ContentEntityInterface $entity, SourcePluginInterface $plugin = NULL, string $oid = NULL): array {
    $mode = $this->getConfigValue('display_modes.' . $entity->getEntityTypeId(), 'teaser');
    $subjectPattern = $this->getConfigValue('pattern.subject', '[push-object:label]');
    $bodyPattern = $this->getConfigValue('pattern.body.value', '[push-object:content]');
    $bodyFormat = $this->getConfigValue('pattern.body.format', 'plain_text');
    $isHtml = $bodyFormat !== 'plain_text';

    // Allow other modules to alter the templates.
    $this->eventDispatcher->dispatch(new ChannelPrepareTemplates($this, $user, $entity, $mode, $subjectPattern, $bodyPattern, $bodyFormat, $isHtml), ChannelEvents::PREPARE_TEMPLATES);

    $viewBuilder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $pushObject = ['label' => $entity->label()];
    $content = [];
    foreach ($entity->getTranslationLanguages() as $key => $language) {

      try {
        // Allow other modules to alter data prior building.
        $this->eventDispatcher->dispatch(new ChannelPreBuild($this, $user, $entity, $mode, $key), ChannelEvents::PRE_BUILD);
        $elements = $viewBuilder->view($entity, $mode, $key);

        // Allow other modules to alter data prior rendering.
        $this->eventDispatcher->dispatch(new ChannelPreRender($this, $user, $entity, $mode, $key, $elements), ChannelEvents::PRE_RENDER);
        // @phpstan-ignore-next-line
        $output = $this->renderer->renderPlain($elements);

        // Allow other modules to alter data post rendering.
        $this->eventDispatcher->dispatch(new ChannelPostRender($this, $user, $entity, $mode, $key, $output), ChannelEvents::POST_RENDER);
      }
      catch (\Throwable $e) {
        $output = Markup::create('Entity can not be rendered: ' . $e->getMessage());
      }

      if (!$isHtml) {
        $output = trim(PlainTextOutput::renderFromHtml($output));
      }
      $pushObject['content'] = $output;
      $tokenData = [
        'user' => $user,
        'push-object' => $pushObject,
        'push_framework_source_plugin' => $plugin,
        'push_framework_source_id' => $oid,
      ];
      $content[$key] = [
        'subject' => $this->token->replace($subjectPattern, $tokenData, ['clear' => TRUE]),
        'body' => $this->token->replace($bodyPattern, $tokenData, ['clear' => TRUE]),
        'is html' => $isHtml,
      ];
      if ($isHtml) {
        $content[$key]['body'] = Markup::create($content[$key]['body']);
      }
    }
    return $content;
  }

}
