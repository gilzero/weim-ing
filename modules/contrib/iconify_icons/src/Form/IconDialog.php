<?php

namespace Drupal\iconify_icons\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\iconify_icons\IconifyServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates an icon dialog form for use in CKEditor.
 *
 * @package Drupal\iconify_icons\Form
 */
class IconDialog extends FormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal Iconify service.
   *
   * @var \Drupal\iconify_icons\IconifyServiceInterface
   */
  protected $iconify;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('iconify_icons.iconify_service')
    );
  }

  /**
   * IconDialog constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\iconify_icons\IconifyServiceInterface $iconify
   *   The iconify service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, IconifyServiceInterface $iconify) {
    $this->configFactory = $configFactory;
    $this->iconify = $iconify;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'iconify_icons_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get selected collections from settings.
    $user_input = $form_state->getUserInput();
    $collections = explode(',', $user_input['dialogOptions']['collections'] ?? '');

    $field_wrapper_id = 'iconify-icons-widget-modal';

    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';

    $form['notice'] = [
      '#markup' => $this->t('Select the icon and parameters to be displayed.'),
    ];

    $form['icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon Name'),
      '#default_value' => '',
      '#required' => TRUE,
      '#description' => $this->t('Name of the Icon. See @iconsLink for valid icon names, or begin typing for an autocomplete list.', [
        '@iconsLink' => Link::fromTextAndUrl($this->t('the Iconify icon list'), Url::fromUri('https://icon-sets.iconify.design/', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]))->toString(),
      ]),
      '#prefix' => "<div id=\"{$field_wrapper_id}\">",
      '#suffix' => '</div>',
      '#attributes' => [
        'class' => [
          'iconify-icons',
        ],
      ],
      '#autocomplete_route_name' => 'iconify_icons.autocomplete',
      '#autocomplete_route_parameters' => [
        'collection' => implode(',', $collections),
      ],
      '#attached' => [
        'library' => [
          'iconify_icons/default',
        ],
      ],
    ];

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
    ];

    $form['settings']['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#description' => $this->t('Icon dimensions in pixels. If only one dimension is specified, such as height, other dimension will be automatically set to match it.'),
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#field_suffix' => t('pixels'),
      '#default_value' => 24,
    ];

    $form['settings']['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#description' => $this->t('Icon dimensions in pixels. If only one dimension is specified, such as height, other dimension will be automatically set to match it.'),
      '#size' => 8,
      '#maxlength' => 8,
      '#min' => 1,
      '#max' => 99999,
      '#field_suffix' => t('pixels'),
      '#default_value' => 24,
    ];

    $form['settings']['color'] = [
      '#type' => 'color',
      '#title' => $this->t('Color'),
      '#description' => $this->t('Icon color. Sets color for monotone icons.'),
      '#default_value' => '#000000',
    ];

    $form['settings']['flip'] = [
      '#type' => 'select',
      '#title' => $this->t('Flip'),
      '#empty_option' => $this->t('- None -'),
      '#options' => [
        'vertical' => $this->t('Vertical'),
        'horizontal' => $this->t('Horizontal'),
      ],
      '#description' => $this->t('Flip icon.'),
      '#default_value' => '',
    ];

    $form['settings']['rotate'] = [
      '#type' => 'select',
      '#title' => $this->t('Rotate'),
      '#empty_option' => $this->t('- None -'),
      '#options' => [
        '90' => $this->t('90°'),
        '180' => $this->t('180°'),
        '270' => $this->t('270°'),
      ],
      '#description' => $this->t('Rotate icon by 90, 180 or 270 degrees.'),
      '#default_value' => '',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Insert Icon'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $values = [];

    $parameters = [
      'width' => $form_state->getValue('width'),
      'height' => $form_state->getValue('height'),
      'color' => $form_state->getValue('color'),
      'flip' => $form_state->getValue('flip'),
      'rotate' => $form_state->getValue('rotate'),
    ];

    // Take 'Icon name (collection name)', match the collection name from
    // inside the parentheses.
    // @see \Drupal\Core\Entity\Element\EntityAutocomplete::extractEntityIdFromAutocompleteInput
    if (preg_match('/(.+\\s)\\(([^\\)]+)\\)/', $form_state->getValue('icon'), $matches)) {
      $icon_name = trim($matches[1]);
      $icon_collection = trim($matches[2]);
      $icon_src = $this->iconify->getIconSource($icon_collection, $icon_name, $parameters);

      $values = [
        'settings' => [
          'icon_src' => $icon_src,
          'icon_alt' => $form_state->getValue('icon'),
        ],
      ];
    }

    $response->addCommand(new EditorDialogSave($values));
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

}
