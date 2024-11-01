<?php

namespace Drupal\ebt_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Color;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\media\Entity\MediaType;

/**
 * Plugin implementation of the 'ebt_settings_default' widget.
 *
 * @FieldWidget(
 *   id = "ebt_settings_default",
 *   label = @Translation("EBT default block settings"),
 *   field_types = {
 *     "ebt_settings"
 *   }
 * )
 */
class EbtSettingsDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#attached']['library'][] = 'ebt_core/colorpicker';

    if (\Drupal::moduleHandler()->moduleExists('field_group')) {
      $element['#attached']['library'][] = 'field_group/element.horizontal_tabs';
    }
    $element['#attached']['library'][] = 'ebt_core/ebt_settings';

    $allowed_media_types = [];
    if (class_exists('Drupal\media\Entity\MediaType')) {
      foreach (MediaType::loadMultiple() as $type) {
        if ($type->id() == 'image' && !in_array('image', $allowed_media_types)) {
          $allowed_media_types[] = 'image';
        }
        if ($type->id() == 'remote_video' && !in_array('remote_video', $allowed_media_types)) {
          $allowed_media_types[] = 'remote_video';
        }
        if ($type->id() == 'video' && !in_array('video', $allowed_media_types)) {
          $allowed_media_types[] = 'video';
        }
      }
    }

    $element['ebt_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Block settings'),
      '#open' => TRUE,
    ];

    $element['ebt_settings']['pass_options_to_javascript'] = [
      '#type' => 'hidden',
      '#value' => FALSE,
    ];

    $element['ebt_settings']['design_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Design options'),
      '#open' => FALSE,
    ];

    $element['ebt_settings']['design_options']['box1'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'design-options__margin-box',
      ],
    ];

    $element['ebt_settings']['design_options']['box1']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $this->t('Margin'),
    ];

    $element['ebt_settings']['design_options']['box1']['margin_top'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Top'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['box1']['margin_top'] ?? '',
      '#attributes' => [
        'placeholder' => '-',
      ],
      '#element_validate' => [[$this, 'validateBoxElement']],
    ];

    $element['ebt_settings']['design_options']['box1']['margin_right'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Right'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['box1']['margin_right'] ?? '',
      '#attributes' => [
        'placeholder' => '-',
      ],
      '#element_validate' => [[$this, 'validateBoxElement']],
    ];

    $element['ebt_settings']['design_options']['box1']['margin_bottom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bottom'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['box1']['margin_bottom'] ?? '',
      '#attributes' => [
        'placeholder' => '-',
      ],
      '#element_validate' => [[$this, 'validateBoxElement']],
    ];

    $element['ebt_settings']['design_options']['box1']['margin_left'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Left'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['box1']['margin_left'] ?? '',
      '#attributes' => [
        'placeholder' => '-',
      ],
      '#element_validate' => [[$this, 'validateBoxElement']],
    ];

    $element['ebt_settings']['design_options']['box1']['box2'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'design-options__border-box',
      ],
    ];

    $element['ebt_settings']['design_options']['box1']['box2']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $this->t('Border'),
    ];

    $element['ebt_settings']['design_options']['box1']['box2']['border_top'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Top'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['box1']['box2']['border_top'] ?? '',
      '#attributes' => [
        'placeholder' => '-',
      ],
      '#element_validate' => [[$this, 'validateBoxElement']],
    ];

    $element['ebt_settings']['design_options']['box1']['box2']['border_right'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Right'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['box1']['box2']['border_right'] ?? '',
      '#attributes' => [
        'placeholder' => '-',
      ],
      '#element_validate' => [[$this, 'validateBoxElement']],
    ];

    $element['ebt_settings']['design_options']['box1']['box2']['border_bottom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bottom'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['box1']['box2']['border_bottom'] ?? '',
      '#attributes' => [
        'placeholder' => '-',
      ],
      '#element_validate' => [[$this, 'validateBoxElement']],
    ];

    $element['ebt_settings']['design_options']['box1']['box2']['border_left'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Left'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['box1']['box2']['border_left'] ?? '',
      '#attributes' => [
        'placeholder' => '-',
      ],
      '#element_validate' => [[$this, 'validateBoxElement']],
    ];

    $element['ebt_settings']['design_options']['box1']['box2']['box3'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'design-options__padding-box',
      ],
    ];

    $element['ebt_settings']['design_options']['box1']['box2']['box3']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $this->t('Padding'),
    ];

    $element['ebt_settings']['design_options']['box1']['box2']['box3']['padding_top'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Top'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['box1']['box2']['box3']['padding_top'] ?? '',
      '#attributes' => [
        'placeholder' => '-',
      ],
      '#element_validate' => [[$this, 'validateBoxElement']],
    ];

    $element['ebt_settings']['design_options']['box1']['box2']['box3']['padding_right'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Right'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['box1']['box2']['box3']['padding_right'] ?? '',
      '#attributes' => [
        'placeholder' => '-',
      ],
      '#element_validate' => [[$this, 'validateBoxElement']],
    ];

    $element['ebt_settings']['design_options']['box1']['box2']['box3']['padding_bottom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bottom'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['box1']['box2']['box3']['padding_bottom'] ?? '',
      '#attributes' => [
        'placeholder' => '-',
      ],
      '#element_validate' => [[$this, 'validateBoxElement']],
    ];

    $element['ebt_settings']['design_options']['box1']['box2']['box3']['padding_left'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Left'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['box1']['box2']['box3']['padding_left'] ?? '',
      '#attributes' => [
        'placeholder' => '-',
      ],
      '#element_validate' => [[$this, 'validateBoxElement']],
    ];

    $element['ebt_settings']['design_options']['box1']['box2']['box3']['box4'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'design-options__logo-box',
      ],
    ];

    $element['ebt_settings']['design_options']['box1']['box2']['box3']['box4']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $this->t('EBT'),
    ];

    $element['ebt_settings']['design_options']['other_settings']['border_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border Color'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['border_color'] ?? '',
      '#attributes' => [
        'placeholder' => $this->t('Select Color'),
      ],
      '#element_validate' => [[$this, 'validateColorElement']],
    ];

    $element['ebt_settings']['design_options']['other_settings']['border_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Border Style'),
      '#options' => [
        'solid' => $this->t('Solid'),
        'dashed' => $this->t('Dashed'),
        'dotted' => $this->t('Dotted'),
        'none' => $this->t('None'),
        'hidden' => $this->t('Hidden'),
        'initial' => $this->t('Initial'),
        'inherit' => $this->t('Inherit'),
        'double' => $this->t('Double'),
        'groove' => $this->t('Groove'),
        'ridge' => $this->t('Ridge'),
        'inset' => $this->t('Inset'),
        'outset' => $this->t('Outset'),
      ],
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['border_style'] ?? '',
      '#description' => '',
    ];

    $element['ebt_settings']['design_options']['other_settings']['border_radius'] = [
      '#type' => 'select',
      '#title' => $this->t('Border Radius'),
      // @codingStandardsIgnoreStart
      '#options' => [
        'none' => $this->t('None'),
        '1px' => '1px',
        '2px' => '2px',
        '3px' => '3px',
        '4px' => '4px',
        '5px' => '5px',
        '10px' => '10px',
        '15px' => '15px',
        '20px' => '20px',
        '25px' => '25px',
        '30px' => '30px',
        '35px' => '35px',
      ],
      // @codingStandardsIgnoreEnd
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['border_radius'] ?? 'none',
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background Color'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_color'] ?? '',
      '#attributes' => [
        'placeholder' => $this->t('Select Color'),
      ],
      '#element_validate' => [[$this, 'validateColorElement']],
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_media'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => $allowed_media_types,
      '#title' => $this->t('Background Image/Video'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_media'] ?? NULL,
      '#description' => $this->t('Upload your image, video or Youtube video.'),
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_video_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Background video options'),
      '#open' => FALSE,
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['autoPlay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['autoPlay'] ?? 1,
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['showControls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show controls'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['showControls'] ?? 0,
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['mute'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mute'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['mute'] ?? 1,
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['startAt'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#title' => $this->t('Start at'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['startAt'] ?? 0,
      '#description' => $this->t('(number) Set the seconds the video should start at.'),
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['opacity'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#title' => $this->t('Opacity'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['opacity'] ?? 1,
      '#description' => $this->t('0 to 1 (number) define the opacity of the video'),
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['addOverlay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add overlay'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['addOverlay'] ?? 1,
      '#description' => $this->t('Show or hide a overlay image over the video'),
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['overlayColor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Overlay color'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['overlayColor'] ?? '#000000',
      '#description' => $this->t('RGB color for local videos, YouTube video uses image for overlay.'),
      '#element_validate' => [[$this, 'validateColorElement']],
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['overlayAlpha'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#title' => $this->t('Overlay opacity'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['overlayAlpha'] ?? '0.3',
      '#description' => $this->t('For local videos, YouTube video uses image for overlay.'),
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['loop'] ?? 1,
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['stopMovieOnBlur'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Stop movie on blur'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['stopMovieOnBlur'] ?? 1,
      '#description' => $this->t('Activate the pause behavior when the window loose focus'),
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['playOnlyIfVisible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Play only if visible'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['playOnlyIfVisible'] ?? 0,
      '#description' => $this->t('Activate the pause behavior when the player is out of screen'),
    ];

    if (in_array('image', $allowed_media_types)) {
      $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['coverImage'] = [
        '#type' => 'media_library',
        '#allowed_bundles' => ['image'],
        '#title' => $this->t('Cover Image'),
        '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['coverImage'] ?? NULL,
        '#description' => $this->t('Image used as background for the player if autoplay is set to false; if not set the player will show the first frame.'),
      ];
    }

    $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['useOnMobile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use on mobile'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['useOnMobile'] ?? 1,
      '#description' => $this->t('Define if the player should render on mobile devices'),
    ];

    if (in_array('image', $allowed_media_types)) {
      $element['ebt_settings']['design_options']['other_settings']['background_video_settings']['mobileFallbackImage'] = [
        '#type' => 'media_library',
        '#allowed_bundles' => ['image'],
        '#title' => $this->t('Mobile fallback Image'),
        '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_video_settings']['mobileFallbackImage'] ?? NULL,
        '#description' => $this->t('Fallback image in case of background video on mobile devices and the “useOnMobile” option is set to false.'),
      ];
    }

    $element['ebt_settings']['design_options']['other_settings']['background_image_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Background image options'),
      '#open' => FALSE,
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_image_settings']['background_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Background Image Position'),
      '#options' => [
        'default' => $this->t('Default'),
        'top left' => $this->t('Top Left'),
        'top' => $this->t('Top'),
        'top right' => $this->t('Top Right'),
        'center left' => $this->t('Center Left'),
        'center' => $this->t('Center'),
        'center right' => $this->t('Center Right'),
        'bottom left' => $this->t('Bottom Left'),
        'bottom' => $this->t('Bottom'),
        'bottom right' => $this->t('Bottom Right'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_image_settings']['background_position'] ?? 'default',
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_image_settings']['background_position_custom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Background Position'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_image_settings']['background_position_custom'] ?? '',
      '#description' => $this->t('See more information about CSS background-position <a href=":ebt_core_configuration_form">here</a>.', [
        ':ebt_core_configuration_form' => 'https://developer.mozilla.org/en-US/docs/Web/CSS/background-position',
      ]),
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_image_settings']['background_size_custom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Background Size'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_image_settings']['background_size_custom'] ?? '',
      '#description' => $this->t('See more information about CSS background-size <a href=":ebt_core_configuration_form">here</a>.', [
        ':ebt_core_configuration_form' => 'https://developer.mozilla.org/en-US/docs/Web/CSS/background-size',
      ]),
    ];

    $element['ebt_settings']['design_options']['other_settings']['background_image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Background Image Style'),
      '#options' => [
        'default' => $this->t('No repeat'),
        'parallax' => $this->t('Parallax'),
        'cover' => $this->t('Cover'),
        'contain' => $this->t('Contain'),
        'repeat' => $this->t('Repeat'),
      ],
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['background_image_style'] ?? 'default',
    ];

    $element['ebt_settings']['design_options']['other_settings']['edge_to_edge'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Edge to Edge'),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['edge_to_edge'] ?? 0,
      '#description' => $this->t('If checked, the styles will be applied to the entire screen, starting from the left and extending to the right.'),
    ];

    $element['ebt_settings']['design_options']['other_settings']['container_width'] = [
      '#type' => 'select',
      '#title' => $this->t('Container Max Width'),
      '#options' => [
        'auto' => $this->t('Auto (100%)'),
        'xxsmall' => $this->t('xxSmall'),
        'xsmall' => $this->t('xSmall'),
        'small' => $this->t('Small'),
        'default' => $this->t('Default'),
        'large' => $this->t('Large'),
        'xlarge' => $this->t('xLarge'),
        'xxlarge' => $this->t('xxLarge'),
        // 'custom' => $this->t('Set custom max width'),
        // @todo Add Custom Width field and show it when custom value is selected.
      ],

      '#description' => $this->t('See <a href=":ebt_core_configuration_form">EBT Core configuration form</a> to set Container Width and other default values.', [
        ':ebt_core_configuration_form' => Url::fromRoute('ebt_core.settings')->toString()
      ]),
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['container_width'] ?? 'auto',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value += ['ebt_settings' => []];
    }
    return $values;
  }

  /**
   * Validate if the box element has only numeric values.
   */
  public function validateBoxElement($element, FormStateInterface $form_state, $form) {
    if (empty($element['#value'])) {
      return;
    }

    // Get the element value.
    $elementValue = $element['#value'];

    // If the value isn't numeric, set error on validation.
    if (!is_numeric($elementValue)) {
      $form_state->setError($element, $this->t('Please use only numbers on box values'));
    }
  }

  /**
   * Validate if is the element has a valid color.
   */
  public static function validateColorElement($element, FormStateInterface $form_state, $form) {
    if (empty($element['#value'])) {
      return;
    }

    // Get the element value.
    $elementValue = $element['#value'];

    $isValidColor = Color::validateHex($elementValue);

    // If the value isn't a valid color, set error on validation.
    if (!$isValidColor) {
      $errorMessage = (string) new TranslatableMarkup('Please insert a valid color');
      $form_state->setError($element, $errorMessage);
    }
  }

}
