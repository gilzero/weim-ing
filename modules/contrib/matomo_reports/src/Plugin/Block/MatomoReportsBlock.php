<?php

namespace Drupal\matomo_reports\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\matomo_reports\MatomoData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'MatomoReportsBlock' block.
 *
 * @Block(
 *  id = "matomo_page_report",
 *  admin_label = @Translation("Matomo page statistics"),
 * )
 */
class MatomoReportsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Account Proxy Interface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The Entity Manager Interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructs a new MatomoReportsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the block.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $currentUser, EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory, ModuleHandlerInterface $moduleHandler, RendererInterface $renderer, RequestStack $request_stack, RequestContext $request_context) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->renderer = $renderer;
    $this->requestStack = $request_stack;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('request_stack'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access matomo reports');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $renderer = $this->renderer;
    $current_user = $this->currentUser;
    $build = [];

    if (!$this->moduleHandler->moduleExists('matomo')) {
      $build['#markup'] = $this->t('To use this block, you need to install the <a href=":url">Matomo</a> module', [':url' => 'https://www.drupal.org/project/matomo']);
      return $build;
    }

    // Build the data URL with all params.
    $token_auth = MatomoData::getToken();
    $matomo_url = MatomoData::getUrl();
    // Message when no token?
    if (empty($matomo_url)) {
      $build['#markup'] = $this->t('Please configure the <a href=":url">Matomo settings</a> to use this block.', [':url' => '/admin/config/system/matomo']);
      return $build;
    }
    $request = $this->requestStack->getCurrentRequest();
    $this->requestContext->fromRequest($request);
    $requestUri = $this->requestContext->getPathInfo();
    $decodedUri = urldecode($requestUri);

    $data_params = [];
    $data_params['idSite'] = $this->configFactory->get('matomo.settings')->get('site_id');
    $data_params['date'] = 'today';
    $data_params['period'] = 'year';
    $data_params['module'] = 'API';
    $data_params['method'] = 'Actions.getPageUrl';
    $data_params['pageUrl'] = $decodedUri;
    $data_params['format'] = 'JSON';
    if (!empty($token_auth)) {
      $data_params['token_auth'] = $token_auth;
    }
    $query_string = http_build_query($data_params);

    $build['#markup'] = '<div id="matomopageviews"></div>';
    $build['#attached']['library'][] = 'matomo_reports/matomoreports';
    $build['#attached']['drupalSettings']['matomo_reports']['matomoJS']['url'] = $matomo_url;
    $build['#attached']['drupalSettings']['matomo_reports']['matomoJS']['query_string'] = $query_string;
    $build['#cache']['contexts'] = [
      'user',
      'url',
    ];
    $renderer->addCacheableDependency($build, $this->entityTypeManager->getStorage('user')->load($current_user->id()));

    return $build;
  }

}
