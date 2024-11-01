<?php

namespace Drupal\ebt_core\Services;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ebt_core\Constants\EbtConstants;

/**
 * Transform Block settings in CSS.
 */
class GenerateCSS {

  /**
   * The EBT Core configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GenerateCSS object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThemeManagerInterface $theme_manager, FileUrlGeneratorInterface $file_url_generator, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $config_factory->get('ebt_core.settings');
    $this->themeManager = $theme_manager;
    $this->fileUrlGenerator = $file_url_generator;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Generate CSS from $settings.
   */
  public function generateFromSettings($settings, $block_class) {
    $styles = '';
    $global_styles = '';

    $positions = [
      'top',
      'right',
      'bottom',
      'left',
    ];

    if (!empty($settings['other_settings']['border_color'])) {
      $border_color = str_replace('#', '', $settings['other_settings']['border_color']);
      $border_color = '#' . Html::escape($border_color);
    }
    else {
      $border_color = EbtConstants::COLOR_BLACK;
    }
    $border_style = !empty($settings['other_settings']['border_style']) ? Html::escape($settings['other_settings']['border_style']) : 'solid';

    foreach ($positions as $position) {
      if (!empty($settings['box1']['margin_' . $position])) {
        $margin_width = str_replace('px', '', $settings['box1']['margin_' . $position]);
        $styles .= ' margin-' . $position . ': ' . Html::escape($margin_width) . 'px !important;';
      }

      if (!empty($settings['box1']['box2']['border_' . $position])) {
        $border_width = str_replace('px', '', $settings['box1']['box2']['border_' . $position]);
        $styles .= ' border-' . $position . ': ' . Html::escape($border_width) . 'px ' . Html::escape($border_color) . ' ' . Html::escape($border_style) . ' !important;';
      }

      if (!empty($settings['box1']['box2']['box3']['padding_' . $position])) {
        $padding_width = str_replace('px', '', $settings['box1']['box2']['box3']['padding_' . $position]);
        $styles .= ' padding-' . $position . ': ' . Html::escape($padding_width) . 'px !important;';
      }
    }

    if (!empty($settings['other_settings']['border_radius']) && $settings['other_settings']['border_radius'] != 'none') {
      $styles .= ' border-radius: ' . Html::escape($settings['other_settings']['border_radius']) . ';';
    }

    if (!empty($settings['other_settings']['background_media'])) {
      $media = $this->entityTypeManager->getStorage('media')->load($settings['other_settings']['background_media']);
      if (!empty($media) && $media->bundle() == 'image') {
        // It is for image media.
        /** @var \Drupal\file\Entity\File $file */
        $file = $media->field_media_image->entity;
        $uri = $file->getFileUri();
        $media_url = $this->fileUrlGenerator->generateAbsoluteString($uri);

        if (!empty($media_url)) {
          if (!empty($settings['other_settings']['background_image_style']) &&
            $settings['other_settings']['background_image_style'] != 'parallax') {
            $styles .= ' background: url("' . $media_url . '") center center no-repeat;';
          }

          switch ($settings['other_settings']['background_image_style']) {
            case 'cover':
              $styles .= ' background-size: cover !important;';
              break;

            case 'contain':
              $styles .= ' background-size: contain !important;';
              break;

            case 'repeat':
              $styles .= ' background-repeat: repeat !important;';
              break;
          }
        }
      }
    }

    if (!empty($settings['other_settings']['background_color'])) {
      $background_color = str_replace('#', '', $settings['other_settings']['background_color']);
      $styles .= ' background-color: #' . Html::escape($background_color) . ';';
    }

    if (!empty($settings['other_settings']['edge_to_edge'])) {
      $global_styles .= '.ebt-edge-to-edge {';
      $global_styles .= ' overflow: hidden;';
      $global_styles .= ' width: 100vw; ';
      $global_styles .= ' max-width: 100vw; ';
      $global_styles .= ' margin-left: calc(-50vw + 50%) !important; ';
      $global_styles .= ' margin-right: calc(-50vw + 50%) !important; ';
      $global_styles .= ' object-fit: cover; ';
      $global_styles .= '} ';

      // Center blocks in left aligned Olivero theme.
      $current_theme = $this->themeManager->getActiveTheme();
      if ($current_theme->getName() == 'olivero') {
        $global_styles .= '@media only screen and (min-width: 75rem) {';
        $global_styles .= '.ebt-edge-to-edge {';
        $global_styles .= ' margin-left: calc(-50vw + 50% - 5.625rem / 2) !important; ';
        $global_styles .= ' margin-right: calc(-50vw + 50%) !important; ';
        $global_styles .= '} ';
        $global_styles .= '} ';

        $global_styles .= '@media only screen and (min-width: 92.5rem) {';
        $global_styles .= '.ebt-edge-to-edge {';
        $global_styles .= 'margin-left: -19.6rem !important; ';

        $global_styles .= ' margin-right: calc(-50vw + 50% - (50vw - 92.5rem) / 2) !important; ';
        $global_styles .= '} ';
        $global_styles .= '} ';
      }

      $global_styles .= 'body { overflow-x: hidden; }';
    }

    if (!empty($settings['other_settings']['container_width']) &&
      $settings['other_settings']['container_width'] != 'auto') {
      $container_width_default = [
        'xxsmall' => '280',
        'xsmall' => '480',
        'small' => '780',
        'default' => '960',
        'large' => '1120',
        'xlarge' => '1320',
        'xxlarge' => '1600',
      ];

      $width = $this->config->get('ebt_core_' . $settings['other_settings']['container_width'] . '_width');
      if (empty($width)) {
        $width = $container_width_default[$settings['other_settings']['container_width']];
      }

      $global_styles .= ' .ebt-width-' . Html::escape($settings['other_settings']['container_width']) . ' .ebt-container { ';
      $global_styles .= ' max-width: ' . $width . 'px; ';
      $global_styles .= ' margin: 0 auto; ';
      $global_styles .= '} ';
    }

    if (!empty($settings['other_settings']['background_image_settings']['background_position']) &&
      $settings['other_settings']['background_image_settings']['background_position'] != 'default' &&
      $settings['other_settings']['background_image_settings']['background_position'] != 'custom') {
      $styles .= ' background-position: ' . Html::escape($settings['other_settings']['background_image_settings']['background_position']) . '; ';
    }

    if (!empty($settings['other_settings']['background_image_settings']['background_position_custom'])) {
      $styles .= ' background-position: ' . Html::escape($settings['other_settings']['background_image_settings']['background_position_custom']) . '; ';
    }

    if (!empty($settings['other_settings']['background_image_settings']['background_size_custom'])) {
      $styles .= ' background-size: ' . Html::escape($settings['other_settings']['background_image_settings']['background_size_custom']) . '; ';
    }

    if (empty($styles) && empty($global_styles)) {
      return '';
    }

    return '<style>.' . $block_class . '{' . $styles . '} ' . $global_styles . ' </style>';
  }

}
