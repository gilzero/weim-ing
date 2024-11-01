<?php

namespace Drupal\ebt_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ebt_core\Constants\EbtConstants;

/**
 * Configure Extra Block Types settings for this site.
 */
class EbtCoreSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'ebt_core.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ebt_core_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['ebt_core_colors'] = [
      '#type' => 'details',
      '#title' => $this->t('Colors'),
      '#open' => TRUE,
    ];

    $form['ebt_core_colors']['ebt_core_primary_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Primary Color'),
      '#default_value' => $config->get('ebt_core_primary_color'),
      '#description' => $this->t('HEX color, for example #ff0000.'),
      '#element_validate' => [
        [
          '\Drupal\ebt_core\Plugin\Field\FieldWidget\EbtSettingsDefaultWidget', 'validateColorElement',
        ],
      ],
    ];

    $form['ebt_core_colors']['ebt_core_primary_button_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Primary Button Text color'),
      '#default_value' => $config->get('ebt_core_primary_button_text_color'),
      '#description' => $this->t('HEX color, for example #ffffff.'),
      '#element_validate' => [
        [
          '\Drupal\ebt_core\Plugin\Field\FieldWidget\EbtSettingsDefaultWidget', 'validateColorElement',
        ],
      ],
    ];

    $form['ebt_core_colors']['ebt_core_secondary_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secondary Color'),
      '#default_value' => $config->get('ebt_core_secondary_color'),
      '#description' => $this->t('HEX color, for example #0000ff.'),
      '#element_validate' => [
        [
          '\Drupal\ebt_core\Plugin\Field\FieldWidget\EbtSettingsDefaultWidget', 'validateColorElement',
        ],
      ],
    ];

    $form['ebt_core_colors']['ebt_core_secondary_button_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secondary Button Text color'),
      '#default_value' => $config->get('ebt_core_secondary_button_text_color'),
      '#description' => $this->t('HEX color, for example #ffffff.'),
      '#element_validate' => [
        [
          '\Drupal\ebt_core\Plugin\Field\FieldWidget\EbtSettingsDefaultWidget', 'validateColorElement',
        ],
      ],
    ];

    $form['ebt_core_colors']['ebt_core_background_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background Color'),
      '#default_value' => $config->get('ebt_core_background_color'),
      '#description' => $this->t('HEX color for Background color. If empty the default value will be @ebt_color_blue@', [
        '@ebt_color_blue@' => EbtConstants::COLOR_BLUE,
      ]),
      '#element_validate' => [
        [
          '\Drupal\ebt_core\Plugin\Field\FieldWidget\EbtSettingsDefaultWidget',
          'validateColorElement',
        ],
      ],
    ];

    $form['ebt_core_breakpoint'] = [
      '#type' => 'details',
      '#title' => $this->t('Breakpoints'),
      '#open' => TRUE,
    ];

    $form['ebt_core_breakpoint']['ebt_core_mobile_breakpoint'] = [
      '#type' => 'number',
      '#title' => $this->t('Mobile breakpoint'),
      '#default_value' => $config->get('ebt_core_mobile_breakpoint'),
    ];

    $form['ebt_core_breakpoint']['ebt_core_tablet_breakpoint'] = [
      '#type' => 'number',
      '#title' => $this->t('Tablet breakpoint'),
      '#default_value' => $config->get('ebt_core_tablet_breakpoint'),
    ];

    $form['ebt_core_breakpoint']['ebt_core_desktop_breakpoint'] = [
      '#type' => 'number',
      '#title' => $this->t('Desktop breakpoint'),
      '#default_value' => $config->get('ebt_core_desktop_breakpoint'),
    ];

    $form['ebt_core_width'] = [
      '#type' => 'details',
      '#title' => $this->t('Width'),
      '#open' => TRUE,
    ];

    $form['ebt_core_width']['ebt_core_xxsmall_width'] = [
      '#type' => 'number',
      '#title' => $this->t('xxSmall width'),
      '#default_value' => $config->get('ebt_core_xxsmall_width'),
    ];

    $form['ebt_core_width']['ebt_core_xsmall_width'] = [
      '#type' => 'number',
      '#title' => $this->t('xSmall width'),
      '#default_value' => $config->get('ebt_core_xsmall_width'),
    ];

    $form['ebt_core_width']['ebt_core_small_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Small width'),
      '#default_value' => $config->get('ebt_core_small_width'),
    ];

    $form['ebt_core_width']['ebt_core_default_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Default width'),
      '#default_value' => $config->get('ebt_core_default_width'),
    ];

    $form['ebt_core_width']['ebt_core_large_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Large width'),
      '#default_value' => $config->get('ebt_core_large_width'),
    ];

    $form['ebt_core_width']['ebt_core_xlarge_width'] = [
      '#type' => 'number',
      '#title' => $this->t('xLarge width'),
      '#default_value' => $config->get('ebt_core_xlarge_width'),
    ];

    $form['ebt_core_width']['ebt_core_xxlarge_width'] = [
      '#type' => 'number',
      '#title' => $this->t('xxLarge width'),
      '#default_value' => $config->get('ebt_core_xxlarge_width'),
    ];

    // Include the library to load the colorpicker pop-up in the fields.
    $form['#attached']['library'][] = 'ebt_core/colorpicker';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Get the breakpoint values for mobile, tablet and desktop.
    $ebt_core_mobile_breakpoint = $form_state->getValue('ebt_core_mobile_breakpoint');
    $ebt_core_tablet_breakpoint = $form_state->getValue('ebt_core_tablet_breakpoint');
    $ebt_core_desktop_breakpoint = $form_state->getValue('ebt_core_desktop_breakpoint');

    // Validate if breakpoints for mobile, tablet and desktop are different.
    if ($ebt_core_mobile_breakpoint == $ebt_core_tablet_breakpoint || $ebt_core_mobile_breakpoint == $ebt_core_desktop_breakpoint || $ebt_core_tablet_breakpoint == $ebt_core_desktop_breakpoint) {

      // Set the validation message.
      $error_message = $this->t('The mobile, tablet and desktop breakpoints must be different');

      // Set the form error.
      $form_state->setErrorByName('ebt_core_mobile_breakpoint', $error_message);
      $form_state->setErrorByName('ebt_core_tablet_breakpoint', $error_message);
      $form_state->setErrorByName('ebt_core_desktop_breakpoint', $error_message);
    }

    // Get all variants of width.
    $ebt_core_xxsmall_width = $form_state->getValue('ebt_core_xxsmall_width');
    $ebt_core_xsmall_width = $form_state->getValue('ebt_core_xsmall_width');
    $ebt_core_small_width = $form_state->getValue('ebt_core_small_width');
    $ebt_core_default_width = $form_state->getValue('ebt_core_default_width');
    $ebt_core_large_width = $form_state->getValue('ebt_core_large_width');
    $ebt_core_xlarge_width = $form_state->getValue('ebt_core_xlarge_width');
    $ebt_core_xxlarge_width = $form_state->getValue('ebt_core_xxlarge_width');

    // Define the array with width values.
    $breakpoint_values = [
      $ebt_core_xxsmall_width,
      $ebt_core_xsmall_width,
      $ebt_core_small_width,
      $ebt_core_default_width,
      $ebt_core_large_width,
      $ebt_core_xlarge_width,
      $ebt_core_xxlarge_width,
    ];

    // Get the unique width values removing the repeated ones.
    $unique_breakpoints = array_unique($breakpoint_values);

    // If there is repeated width, set a validation in the form error.
    if (count($breakpoint_values) !== count($unique_breakpoints)) {
      $form_state->setError($form, $this->t('All the width values must be different'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // If the "ebt_core_background_color" is empty, let's use the default value.
    $ebt_core_background_color = !empty($form_state->getValue('ebt_core_background_color')) ? $form_state->getValue('ebt_core_background_color') : EbtConstants::COLOR_BLUE;

    $this->config(static::SETTINGS)
      ->set('ebt_core_primary_color', $form_state->getValue('ebt_core_primary_color'))
      ->set('ebt_core_primary_button_text_color', $form_state->getValue('ebt_core_primary_button_text_color'))
      ->set('ebt_core_secondary_color', $form_state->getValue('ebt_core_secondary_color'))
      ->set('ebt_core_secondary_button_text_color', $form_state->getValue('ebt_core_secondary_button_text_color'))
      ->set('ebt_core_background_color', $ebt_core_background_color)
      ->set('ebt_core_mobile_breakpoint', $form_state->getValue('ebt_core_mobile_breakpoint'))
      ->set('ebt_core_tablet_breakpoint', $form_state->getValue('ebt_core_tablet_breakpoint'))
      ->set('ebt_core_desktop_breakpoint', $form_state->getValue('ebt_core_desktop_breakpoint'))
      ->set('ebt_core_xxsmall_width', $form_state->getValue('ebt_core_xxsmall_width'))
      ->set('ebt_core_xsmall_width', $form_state->getValue('ebt_core_xsmall_width'))
      ->set('ebt_core_small_width', $form_state->getValue('ebt_core_small_width'))
      ->set('ebt_core_default_width', $form_state->getValue('ebt_core_default_width'))
      ->set('ebt_core_large_width', $form_state->getValue('ebt_core_large_width'))
      ->set('ebt_core_xlarge_width', $form_state->getValue('ebt_core_xlarge_width'))
      ->set('ebt_core_xxlarge_width', $form_state->getValue('ebt_core_xxlarge_width'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
