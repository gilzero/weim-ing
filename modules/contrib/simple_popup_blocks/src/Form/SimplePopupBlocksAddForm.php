<?php

namespace Drupal\simple_popup_blocks\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to add a popup entry.
 */
class SimplePopupBlocksAddForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * Object of EntityTypeManagerInterface.
   *
   * @var EntityTypeManagerInterface
   */
  protected $typeManager;

  /**
   * Messanger object.
   *
   * @var Messenger
   */
  protected $messenger;

  /**
   * Database object.
   *
   * @var Connection
   */
  protected $database;

  /**
   * SimplePopupBlocksAddForm constructor.
   *
   * @param EntityTypeManagerInterface $typeManager
   * @param Messenger $messenger
   * @param Connection $database
   */
  public function __construct(EntityTypeManagerInterface $typeManager, Messenger $messenger, Connection $database ) {
    $this->typeManager = $typeManager;
    $this->messenger = $messenger;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_popup_blocks';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simple_popup_blocks.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $visit_counts = [];
    $block_ids = $this->typeManager->getStorage('block')->getQuery()->accessCheck()->execute();

    $form = [];
    for ($i = 0; $i < 101; $i++) {
      $visit_counts[] = $i;
    }

    $form['uid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unique identifier'),
      '#required' => TRUE,
    ];

    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this
        ->t('Choose the identifier'),
      '#default_value' => 0,
      '#options' => [
        0 => $this
          ->t('Drupal blocks'),
        1 => $this
          ->t('Custom css id or class'),
      ],
    ];
    $form['block_list'] = [
      '#type' => 'select',
      '#title' => t("Blocks list"),
      '#options' => $block_ids,
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 0],
        ],
      ],
    ];
    $form['custom_css'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Selectors without # or .'),
      '#default_value' => t("custom-css-id"),
      '#description' => $this->t("Ex: my-profile, custom_div_cls, someclass, mypopup-class."),
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 1],
        ],
      ],
    ];
    $form['css_selector'] = [
      '#type' => 'radios',
      '#title' => $this
        ->t('Css selector'),
      '#default_value' => 1,
      '#options' => [
        0 => $this
          ->t('Css class (.)'),
        1 => $this
          ->t('Css id (#)'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 1],
        ],
      ],
    ];
    $form['layout'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose layout'),
      '#default_value' => 0,
      '#options' => [
        0 => $this
          ->t('Top left'),
        1 => $this
          ->t('Top Right'),
        2 => $this
          ->t('Bottom left'),
        3 => $this
          ->t('Bottom Right'),
        4 => $this
          ->t('Center'),
        5 => $this
          ->t('Top center'),
        6 => $this
          ->t('Top bar'),
        7 => $this
          ->t('Bottom bar'),
        8 => $this
          ->t('Left bar'),
        9 => $this
          ->t('Right bar'),
      ],
    ];
    $form['popup_frequency'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Popup Frequency'),
    ];

    $form['popup_frequency']['use_time_frequency'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use time frequency instead of visit count'),
      '#default_value' => 0,
    ];

    $form['popup_frequency']['visit_counts'] = [
      '#title' => $this->t('Visit counts'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#size' => 8,
      '#options' => $visit_counts,
      '#default_value' => 0,
      '#description' => $this->t("Examples:<br>
        0 = Show popup on users each visit<br>
        1,2 = Show popup on users first and second visit<br>
        1,4,7 = Show popup on users first, forth and seventh visit"),
      '#states' => [
        'invisible' => [
          ':input[name="use_time_frequency"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['popup_frequency']['time_frequency'] = [
      '#title' => $this->t('Time frequency'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [3600 => 'Hourly', 86400 => 'Daily', 604800 => 'Weekly'],
      '#default_value' => 3600,
      '#description' => $this->t("Choose frequency popup is displayed to the visitor"),
      '#states' => [
        'visible' => [
          ':input[name="use_time_frequency"]' => ['checked' => TRUE],
        ],
      ],
		
    ];
    $form['minimize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Minimize button'),
      '#default_value' => 1,
    ];
    $form['close'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Close button'),
      '#default_value' => 1,
    ];
    $form['button_configuration']['show_minimized_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show minimized button when popup is not automatically triggered'),
      '#default_value' => 0,
    ];
    $form['enable_escape'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('ESC key to close'),
      '#default_value' => 1,
    ];
    $form['overlay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Overlay'),
      '#default_value' => 1,
    ];
    $form['trigger_method'] = [
      '#type' => 'radios',
      '#title' => $this
        ->t('Trigger method'),
      '#default_value' => 0,
      '#options' => [
        0 => $this
          ->t('Automatic'),
        1 => $this
          ->t('Manual - on click event'),
        2 => $this
          ->t('Before browser/tab close'),
      ],
    ];
    $form['delay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delays'),
      '#size' => 5,
      '#default_value' => 0,
      '#description' => $this->t("Show popup after this seconds. 0 will show immediately after the page load."),
      '#states' => [
        'visible' => [
          ':input[name="trigger_method"]' => ['value' => 0],
        ],
      ],
    ];
  $form['trigger_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trigger width'),
      '#size' => 5,
      '#default_value' => "",
      '#description' => $this->t("Specify a pixel width at which the popup should trigger, or leave blank for all widths. Example: 800.")
    ];
    $form['trigger_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add css id or class starting with # or .'),
      '#default_value' => t("#custom-css-id"),
      '#description' => $this->t("Ex: #my-profile, #custom_div_cls, .someclass, .mypopup-class."),
      '#states' => [
        'visible' => [
          ':input[name="trigger_method"]' => ['value' => 1],
        ],
      ],
    ];
    $form['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => 400,
      '#description' => $this->t("Add popup width in pixels"),
    ];
    $form['cookie_expiry'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie expiry'),
      '#size' => 5,
      '#default_value' => 100,
      '#description' => $this->t("Delete the cookie after x days. Choose 0 to delete the cookie after the browser closes."),
    ];
    $form['adjustments'] = [
      '#type' => 'details',
      '#title' => $this->t('Adjustment class'),
      '#open' => TRUE,
      '#description' => $this->t("Once you created, you can see the css selectors to customize the popup designs."),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Popup'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('type') == 0) {
      $selector = 'block_list';
      $identifier = $form_state->getValue($selector);
    }
    else {
      $selector = 'custom_css';
      $identifier = $form_state->getValue($selector);
    }
    if (!isset($identifier) || empty($identifier)) {
      $form_state->setError($form[$selector], $this->t('You should provide some css selector.'));
    }
    if ($form_state->getValue('type') == 1) {
      // Get the first character using substr.
      $firstCharacter = substr($identifier, 0, 1);
      if (in_array($firstCharacter, ['.', '#'])) {
        $form_state->setError($form[$selector], $this->t('Selector should not start with . or # in %field.', ['%field' => $identifier]));
      }
    }
    $trigger_method = $form_state->getValue('trigger_method');
    if ($trigger_method == 1) {
      $trigger_selector = $form_state->getValue('trigger_selector');
      // Get the first character using substr.
      $firstCharacter = substr($trigger_selector, 0, 1);
      if (!in_array($firstCharacter, ['.', '#'])) {
        $form_state->setError($form['trigger_selector'], $this->t('Selector should start with . or # in %field.', ['%field' => $trigger_selector]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('type') == 0) {
      $identifier = $form_state->getValue('block_list');
    }
    else {
      $identifier = $form_state->getValue('custom_css');
    }
    $delay = $form_state->getValue('delay');
    if (empty($delay) || $delay < 0) {
      $delay = 0;
    }
    $width = $form_state->getValue('width');
    if (empty($width) || $width < 0) {
      $width = 400;
    }
    $cookie_expiry = $form_state->getValue('cookie_expiry');
    if (strlen($cookie_expiry) == 0 || $cookie_expiry < 0) {
      $cookie_expiry = 100;
    }
    if ($form_state->getValue('use_time_frequency')) {
      $visit_counts = serialize([0 => '0']);
    }
    else {
      $visit_counts = serialize($form_state->getValue('visit_counts'));
    }

    $uid = preg_replace('@[^a-z0-9_]+@','_',strtolower($form_state->getValue('uid')));

    $config = \Drupal::service('config.factory')->getEditable('simple_popup_blocks.popup_'.$uid);
    $config->set('identifier', $identifier)
      ->set('uid', $uid)
      ->set('type', $form_state->getValue('type'))
      ->set('css_selector', $form_state->getValue('css_selector'))
      ->set('layout', $form_state->getValue('layout'))
      ->set('visit_counts', $visit_counts)
      ->set('overlay', $form_state->getValue('overlay'))
      ->set('trigger_method', $form_state->getValue('trigger_method'))
      ->set('trigger_selector', $form_state->getValue('trigger_selector'))
      ->set('enable_escape', $form_state->getValue('enable_escape'))
      ->set('delay', $delay)
      ->set('minimize', $form_state->getValue('minimize'))
      ->set('close', $form_state->getValue('close'))
      ->set('use_time_frequency', $form_state->getValue('use_time_frequency'))
      ->set('time_frequency', $form_state->getValue('time_frequency'))
      ->set('show_minimized_button', $form_state->getValue('show_minimized_button'))		  
      ->set('width', $width)
      ->set('cookie_expiry', $cookie_expiry)
      ->set('status', $form_state->getValue('status'))
      ->save();
     parent::submitForm($form, $form_state);
  }

}
