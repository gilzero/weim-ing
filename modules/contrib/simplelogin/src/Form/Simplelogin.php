<?php

/**
 * @file
 * Contains \Drupal\simplelogin\Form\SettingsForm.
 * Simplelogin settings form.
 */

namespace Drupal\simplelogin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\file\Entity\File;
use Drupal\Core\Render\RendererInterface;

/**
 * Defines a form that configure settings.
 */
class Simplelogin extends ConfigFormBase {

  /**
   * Image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   *   Image factory.
   */
  protected $imageFactory;


  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ImageFactory $image_factory, RendererInterface $renderer) {
    parent::__construct($config_factory);
    $this->imageFactory = $image_factory;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('image.factory'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simplelogin_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simplelogin.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    $form_state->disableCache();

    $simplelogin_config = $this->config('simplelogin.settings');
    $imageid = $simplelogin_config->get('background_image');

    $image_render = array();
    if (isset($imageid[0])) {
      $file = File::load($imageid[0]);  // File Load
      if ($file) {
        $fileUrl = $file->getFileUri();

        // Check if image is valid.
        $image = $this->imageFactory->get($fileUrl);
        if ($image->isValid()) {
          $image_render = array(
            '#theme' => 'image_style',
            '#width' => $image->getWidth(),
            '#height' => $image->getHeight(),
            '#style_name' => 'medium',
            '#uri' => $fileUrl,
          );
        }
      }
    }

    $form['simplelogin'] = array(
      '#type'            => 'details',
      '#title'           => $this->t('Configuration'),
      '#open'            => TRUE,

      'background_active' => array(
        '#type'          => 'checkbox',
        '#title'         => $this->t('Background Image'),
        '#default_value' => $simplelogin_config->get('background_active'),
        '#description'   => $this->t('If enabled, the background image will be added on the simple login pages.'),
      ),
      'settings'          => array(
        '#type'           => 'details',
        '#title'          => $this->t('Background Image Settings'),
        '#open'            => TRUE,
          'background_image'   =>  array(
            '#type'            => 'managed_file',
            '#name'            => 'Background Image',
            '#title'           => $this->t('Image'),
            '#default_value'   => $simplelogin_config->get('background_image') ? $simplelogin_config->get('background_image') : '',
            '#description'     =>  $this->t('Upload an image file for the Simplelogin pages'),
            '#upload_location' => 'public://simplelogin/',
            '#multiple'        => FALSE,
            '#upload_validators'    => array(
              'file_validate_is_image'      => array(),
              'file_validate_extensions'    => array('gif png jpg jpeg'),
              'file_validate_size'          => array(25600000)
            ),
          ),
          'background_url' => array(
            '#type'   => 'item',
            '#markup' => $this->renderer->render($image_render),
          ),
          'background_opacity' => array(
            '#type'          => 'checkbox',
            '#title'         => $this->t('Opacity'),
            '#default_value' => $simplelogin_config->get('background_opacity'),
            '#description'   => $this->t('If enabled, the opacity will be added to the SimpleLogin pages. so the background image will take up with opacity.'),
          ),
      ),
      'background_color' => array(
        '#type' => 'color',
        '#title' => $this->t('Color'),
        '#required' => TRUE,
        '#default_value' => $simplelogin_config->get('background_color') ? $simplelogin_config->get('background_color') : '#00bfff', //#76b852',
        '#description' => $this->t('If you want the background color you need to remove the background image. (example: [Red: 0, Green:191, Blue:255] , [red:118, Green:184, Blue:82])'),
      ),
      'button_background' => array(
          '#type'          => 'checkbox',
          '#title'         => $this->t('Change button backgrounds'),
          '#default_value' => $simplelogin_config->get('button_background'),
          '#description'   => $this->t('If enabled, the background colour will be used for button backgrounds too on the simple login pages.'),
      ),
      'wrapper_width' => array(
        '#type' => 'textfield',
        '#title' => $this->t('Wrapper width'),
        '#default_value' => $simplelogin_config->get('wrapper_width') ? $simplelogin_config->get('wrapper_width') : '360',
        '#description' => $this->t('Simplelogin wrapper width in pixels.(example:360)'),
        '#field_suffix' => 'PX',
        '#size' => 5,
        '#required' => TRUE,
      ),
      'advanced' => array(
        '#type'  => 'details',
        '#title' => $this->t('Advanced Settings'),
        '#open'  => FALSE,
          'unset_active_css' => array(
            '#type'          => 'checkbox',
            '#title'         => $this->t('Unset Active theme CSS files'),
            '#default_value' => $simplelogin_config->get('unset_active_css'),
            '#description'   => $this->t('If enabled, active theme CSS files are removed from the simple login pages.'),
          ),
          'unset_css' => array(
            '#type' => 'textarea',
            '#title' => $this->t('Unset CSS file path'),
            '#default_value' => $simplelogin_config->get('unset_css') ? $simplelogin_config->get('unset_css') : '',
            '#description' => $this->t('Remove unwanted CSS files each path as a separate line(example: core/themes/classy/css/components/button.css)'),
          ),
      ),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $background_image = $form_state->getValue(['background_image']);
    $opacity = $form_state->getValue(['background_opacity']);

    if (empty($background_image) && !empty($opacity)) {
      $form_state->setErrorByName('background_image', "Opacity is applicable only for images. if image empty means we won't need Opacity. Please uncheck");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $image_id = $values['background_image'] ?? [];
    if (!empty($image_id)) {
      $file = File::load($image_id[0]);
      if ($file instanceof File) {
        $file->setPermanent();  // FILE_STATUS_PERMANENT;
        $file->save();
      }
    }

    $this->config('simplelogin.settings')
      ->set('background_active', $values['background_active'])
      ->set('background_image', $image_id)
      ->set('background_color', $values['background_color'])
      ->set('background_opacity', $values['background_opacity'])
      ->set('button_background', $values['button_background'])
      ->set('wrapper_width', $values['wrapper_width'])
      ->set('unset_active_css', $values['unset_active_css'])
      ->set('unset_css', $values['unset_css'])
      ->save();

    drupal_flush_all_caches();
  }
}
