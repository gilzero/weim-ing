<?php

namespace Drupal\ultimenu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\ultimenu\UltimenuManagerInterface;
use Drupal\ultimenu\UltimenuSkinInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Ultimenu' block.
 *
 * @Block(
 *  id = "ultimenu_block",
 *  admin_label = @Translation("Ultimenu block"),
 *  category = @Translation("Ultimenu"),
 *  deriver = "Drupal\ultimenu\Plugin\Derivative\UltimenuBlock",
 * )
 */
class UltimenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Ultimenu manager service.
   *
   * @var \Drupal\ultimenu\UltimenuManagerInterface
   */
  protected $manager;

  /**
   * The Ultimenu skin service.
   *
   * @var \Drupal\ultimenu\UltimenuSkinInterface
   */
  protected $skin;

  /**
   * Constructs an UltimenuBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\ultimenu\UltimenuManagerInterface $manager
   *   The ultimenu manager.
   * @param \Drupal\ultimenu\UltimenuSkinInterface $skin
   *   The ultimenu skin service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $current_user,
    UltimenuManagerInterface $manager,
    UltimenuSkinInterface $skin,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->manager = $manager;
    $this->skin = $skin;
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
      $container->get('ultimenu.manager'),
      $container->get('ultimenu.skin')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function delta() {
    // Derivatives are prefixed with 'ultimenu-', e.g.: ultimenu-main.
    $id = $this->getDerivativeId();
    return substr($id, 9);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ajaxify' => FALSE,
      'regions' => [],
      'unlink' => '',
      'unlinks' => [],
      'skin' => 'module|ultimenu--dark',
      'orientation' => 'ultimenu--htb',
      'caret' => FALSE,
      'caret_skin' => 'arrow',
      'submenu' => FALSE,
      'submenu_position' => '',
      'submenu_collapsible' => FALSE,
      'offcanvas' => FALSE,
      'hamburger' => FALSE,
      'canvas_off' => '',
      'canvas_on' => '',
      'canvas_skin' => 'scalein',
      'sticky' => FALSE,
    ];
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {
    if ($this->currentUser->hasPermission('administer ultimenu')) {
      $ultimenu_admin = Url::fromRoute('ultimenu.settings')->toString();
      $form['ajaxify'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Ajaxify'),
        '#default_value' => $this->configuration['ajaxify'] ?? FALSE,
        '#description'   => $this->t('Check to load ultimenu region contents using AJAX. Only makes sense for massive contents.'),
      ];

      // @todo all: $regions = (array) $this->manager->getSetting('regions');
      $regions = $this->manager->getRegionsByMenu($this->delta());
      $states['visible'][':input[name="settings[ajaxify]"]'] = ['checked' => TRUE];
      $form['regions'] = [
        '#type'          => 'checkboxes',
        '#title'         => $this->t('Ajaxifed regions'),
        '#options'       => $regions,
        '#default_value' => isset($this->configuration['regions']) ? array_values((array) $this->configuration['regions']) : [],
        '#description'   => $this->t('Check which regions should be ajaxified, leaving those unchecked as non-ajaxed regions. Be sure to enable the regions at <a href=":url">Ultimenu admin</a>.', [':url' => $ultimenu_admin]),
        '#states'        => $states,
      ];

      $unlink_options = [
        'hashed'  => $this->t('Hashed (#)'),
        'through' => $this->t('Original link (A HREF)'),
        'nolink'  => $this->t('No link (SPAN)'),
      ];

      $form['unlink'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Non click-through markup'),
        '#default_value' => $this->configuration['unlink'],
        '#options'       => $unlink_options,
        '#empty_option'  => $this->t('- None -'),
        '#description'   => $this->t('Choose a markup replacement for non-click-through menu items. At any rate, all options will not direct visitors anywhere, clickable but not click through. You can also use menu administration to put <code>&lt;nolink&gt;</code> for more fine-grained controls, but will only have SPAN unlike these options. If provided, this option, except for <strong>Original link</strong>, will hijack <code>&lt;nolink&gt;</code>.'),
      ];

      $unlinks['visible']['select[name*="[unlink]"]'] = ['!value' => ''];
      $form['unlinks'] = [
        '#type'          => 'checkboxes',
        '#title'         => $this->t('Non click-through menu items'),
        '#options'       => $regions,
        '#default_value' => isset($this->configuration['unlinks']) ? array_values((array) $this->configuration['unlinks']) : [],
        '#description'   => $this->t('Check which menu items should be unlinked/ non-click-through. Useful if you have no ready, or well designed pages, and or the visitors should not access the pages directly. The menu item will be just a placeholder without click-through link. Be sure to enable the regions at <a href=":url">Ultimenu admin</a>.', [':url' => $ultimenu_admin]),
        '#states'        => $unlinks,
      ];

      $form['skin'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Ultimenu skin'),
        '#default_value' => $this->configuration['skin'],
        '#options'       => $this->skin->getSkins(),
        '#empty_option'  => $this->t('- None -'),
        '#description'   => $this->t('Choose the skin for this block. You can supply custom skins at <a href=":ultimenu_settings" target="_blank">Ultimenu settings</a>. The skin can be made specific to this block using the proper class by each menu name. Be sure to <a href=":clear" target="_blank">clear the cache</a> if trouble to see the new skin applied.', [
          ':ultimenu_settings' => $ultimenu_admin,
          ':clear' => Url::fromRoute('system.performance_settings')->toString(),
        ]),
      ];

      $form['orientation'] = [
        '#type'           => 'select',
        '#title'          => $this->t('Flyout orientation'),
        '#default_value'  => $this->configuration['orientation'],
        '#options'        => [
          'ultimenu--htb' => $this->t('Horizontal to bottom'),
          'ultimenu--htt' => $this->t('Horizontal to top'),
          'ultimenu--vtl' => $this->t('Vertical to left'),
          'ultimenu--vtr' => $this->t('Vertical to right'),
        ],
        '#description'   => $this->t('Choose the orientation of the flyout, depending on the placement. At sidebar left, <strong>Vertical to right</strong>. At header, <strong>Horizontal to bottom</strong>, normally used for off-canvas. At footer, <strong>Horizontal to top</strong>. <br><strong>Repeat!</strong> Only <strong>Horizontal to bottom</strong> makes sense for off-canvas menu in the header region, not sidebar, nor footer, due to layout requirements, see STYLING section at /admin/help/ultimenu#styling.'),
      ];

      $form['caret'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Use caret'),
        '#default_value' => $this->configuration['caret'],
        '#description'   => $this->t('If enabled, CSS :hover will be disabled so to use carets instead. Only useful and works if <strong>Always use hamburger</strong> option is disabled since hamburger will always have carets.'),
      ];

      $caret_skins = $this->skin->getCaretSkins();
      $form['caret_skin'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Caret skin'),
        '#options'       => array_combine($caret_skins, $caret_skins),
        '#required'      => TRUE,
        '#default_value' => $this->configuration['caret_skin'],
        '#description'   => $this->t('Choose the skin for the caret.'),
      ];

      $form['submenu'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Render submenu'),
        '#default_value' => $this->configuration['submenu'],
        '#description'   => $this->t('Render the relevant submenus inside the Ultimenu region without using Block admin, and independent from blocks. Alternatively use core Menu level option with regular menu block when core supports the "Fixed parent item", see <a href=":url" target="_blank">#2631468</a>. <br /><strong>Important!</strong> Be sure to check "<strong>Show as expanded</strong>" at the parent menu item edit page as needed, otherwise no submenus will be rendered.', [':url' => 'https://www.drupal.org/node/2631468']),
      ];

      $submenus['visible'][':input[name*="[submenu]"]'] = ['checked' => TRUE];
      $form['submenu_collapsible'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Collapsible submenu'),
        '#default_value' => $this->configuration['submenu_collapsible'],
        '#description'   => $this->t('Makes submenus collapsible. Otherwise they are stacking. Only for submenus managed by Ultimenu via <b>Render submenu</b> option above, not separate submenu blocks. <br /><strong>Important!</strong> Be sure to check "<strong>Show as expanded</strong>" at each parent submenu item edit page as needed, otherwise no submenus will be rendered. Behold! The menu item created using Views UI may refuse "<strong>Show as expanded</strong>" for some reason, use regular Menu administration instead.'),
        '#states'        => $submenus,
      ];

      $form['submenu_position'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Submenu position'),
        '#options'       => [
          'bottom' => $this->t('Bottom'),
          'top'    => $this->t('Top'),
        ],
        '#empty_option'  => $this->t('- None -'),
        '#default_value' => $this->configuration['submenu_position'],
        '#description'   => $this->t('Choose where to place the submenu, either before or after existing blocks. Default to Top.'),
        '#states'        => $submenus,
      ];

      $offcanvases['visible'][':input[name*="[offcanvas]"]'] = ['checked' => TRUE];
      $form['offcanvas'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Use as off-canvas'),
        '#default_value' => $this->configuration['offcanvas'],
        '#description'   => $this->t('If enabled, this menu will act as the off-canvas menu with hamburger. Previously only Main menu.'),
        '#prefix' => '<div class="messages messages--warning">' . $this->t('<b>Warning!</b> The old rule still applies, only one off-canvas can exist on a page. Use the correct <strong>Flyout orientation</strong> and the correct header region. Use block visibility rules to avoid multiple off-canvas menus on a page. Leave anything empty/ unchecked below to not make it as off-canvas such as regular sidebar or footer mega menus.') . '</div>',
      ];

      $form['hamburger'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Always use hamburger'),
        '#default_value' => $this->configuration['hamburger'],
        '#description'   => $this->t('Enable to have off-canvas for both mobile and desktop, identified by hamburger. Disable to have a regular hoverable mega menu for desktop, and hamburger for mobile.'),
        '#states'        => $offcanvases,
      ];

      $stickies['visible'][':input[name*="[hamburger]"]'] = ['checked' => FALSE];
      $form['sticky'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Sticky/ fixed header'),
        '#default_value' => $this->configuration['sticky'],
        '#description'   => $this->t('Only works with hoverable (Always use hamburger disabled) to make header fixed to the top of the page when being scrolled down. May not work with Olivero or other themes which already manage header fixed positioning. Try Bartik to have a better idea.'),
        '#states'        => $stickies,
      ];

      $form['canvas_off'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Off-canvas element'),
        '#default_value' => $this->configuration['canvas_off'],
        '#description'   => $this->t('Valid CSS selector for the off-canvas element. Only one can exist. <br>For Bartik, e.g.: <code>#header</code> or <code>.region-primary-menu</code> (not good, just works). But not both. <br>For Olivero, e.g.: <code>#header</code>. Best after branding.'),
        '#states'        => $offcanvases,
      ];

      $form['canvas_on'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('On-canvas element'),
        '#default_value' => $this->configuration['canvas_on'],
        '#description'   => $this->t('Valid CSS selector for the on-canvas element. Can be multiple. <br>For Bartik, e.g.: <code>#main-wrapper, .highlighted, .featured-top, .site-footer</code> <br>For Olivero, e.g.: <code>#main-wrapper, .site-footer</code> <br>Visit <b>/admin/help/ultimenu</b> under <b>STYLING</b> section for details.'),
        '#states'        => $offcanvases,
      ];

      $skins = $this->skin->getOffCanvasSkins();
      $form['canvas_skin'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Off-canvas skin'),
        '#options'       => array_combine($skins, $skins),
        '#default_value' => $this->configuration['canvas_skin'],
        '#description'   => $this->t('The off-canvas skin. Note the name oldies is meant for old browsers up, but not as smoother. Consider Modernizr.js to support old browsers with advanced transform effects. More custom works are required as usual.'),
        '#states'        => $offcanvases,
      ];
    }

    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // This form may be loaded as a subform Context, Layout Builder, etc.
    // More info: #2536646, #2798261, #2774077.
    $form_state2 = $form_state;
    if ($form_state instanceof SubformStateInterface) {
      $form_state2 = $form_state->getCompleteFormState();
    }

    // Weirdo, might be NULL randomly at Context UI, see #2897557.
    $object = isset($form_state2->getBuildInfo()['callback_object']) ? $form_state2->getFormObject() : NULL;
    $entity = NULL;
    // When Ultimenu is loaded by LayoutBuilder, the method is not available.
    // Fortunately we only care for the Ultimenu:main which is not so usable at
    // LayoutBuilder level anyway, not crucial.
    if ($object && method_exists($object, 'getEntity')) {
      $entity = $object->getEntity();
    }

    if ($entity) {
      $id = $entity->getPluginId();

      $config = $this->manager->configFactory()->getEditable('ultimenu.settings');

      // @todo not usable, yet, intended for future works with themes.
      if ($theme = $entity->getTheme()) {
        $config->set('offcanvases.' . $id . '.' . $theme, $entity->id());

        $config->save(TRUE);
      }
    }

    foreach (array_keys($this->defaultConfiguration()) as $key) {
      $value = $form_state->getValue($key) ?: $form_state2->getValue($key);
      $this->configuration[$key] = $key == 'regions' && is_array($value)
        ? array_filter($value)
        : $value;
    }

    // Invalidate the library discovery cache to update the new skin discovery.
    $this->skin->clearCachedDefinitions();
    // @todo update for D12 $this->manager->libraries()->discovery()->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $goodies       = $this->manager->getSetting('goodies');
    $menu_name     = $this->delta();
    $skin          = $this->configuration['skin'];
    $provider      = '';
    $skin_name     = '';
    $skin_basename = '';

    // Load the specified block skin.
    if (!empty($skin)) {
      $skin_css_path = $this->skin->getSkinPath($skin);
      $skin_basename = $this->skin->getName($skin_css_path);

      // Fetch the skin file name from the setting.
      [$provider, $skin_name] = array_pad(array_map('trim', explode("|", $skin, 2)), 2, NULL);
    }

    // @todo remove BC.
    $hamburger = $this->configuration['hamburger'] ?? FALSE;
    $main = $menu_name == 'main' && empty($goodies['decouple-main-menu']);
    if ($main && !empty($goodies['off-canvas-all'])) {
      $hamburger = TRUE;
    }

    // Provide the settings for further process.
    $regions = array_filter($this->configuration['regions'] ?? []);
    $unlinks = array_filter($this->configuration['unlinks'] ?? []);

    $build['config'] = [
      'bid'           => $this->getDerivativeId(),
      'menu_name'     => $menu_name,
      'regions'       => $regions,
      'unlinks'       => $unlinks,
      'skin_name'     => $skin_name,
      'skin_provider' => $provider,
      'skin_basename' => $skin_basename,
      'hamburger'     => $hamburger,
    ] + $this->configuration;

    return $this->manager->build($build);
  }

}
