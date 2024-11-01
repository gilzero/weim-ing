<?php

namespace Drupal\module_builder\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Defines a custom form element for a list of generated files to write.
 *
 * Properties:
 *  - #files: An array of the generated files' code, keyed by filename relative
 *    to the module folder.
 *  - merge_statuses: An array of static::MERGE_* constants, keyed by the
 *    relative filename.
 *  - git_statuses: An array of static::VERSION_CONTROL_* constants, keyed by
 *    the relative filename.
 *
 * @RenderElement("module_builder_generated_files")
 */
class GeneratedFiles extends FormElement {

  /**
   * Indicates there is no existing file.
   */
  public const MERGE_NEW = 0;

  /**
   * Indicates the generated code has been merged with the existing file.
   *
   * The merge may include overwritten sections, such as if functions are common
   * to both sides.
   */
  public const MERGE_MERGED = 1;

  /**
   * Indicates the existing file would be entirely overwritten.
   */
  public const MERGE_OVERWRITTEN = 2;

  /**
   * Indicates there is no existing file.
   */
  public const VERSION_CONTROL_NEW = 0;

  /**
   * Indicates the existing file is under version control and up to date.
   */
  public const VERSION_CONTROL_MANAGED = 1;

  /**
   * Indicates the existing file is under version control but has changes.
   */
  public const VERSION_CONTROL_CHANGED = 2;

  /**
   * Indicates the existing file is not under version control.
   */
  public const VERSION_CONTROL_UNMANAGED = 3;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processFiles'],
      ],
      '#pre_render' => [
        [$class, 'preRender'],
      ],
      '#theme' => 'generated_files',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Element process callback.
   */
  public static function processFiles(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['generate'] = [
      '#tree' => TRUE,
    ];

    $element['show'] = [
      '#type' => 'radios',
      '#options' => array_fill_keys(array_keys($element['#files']), ''),
      '#default_value' => array_key_first($element['#files']),
    ];

    foreach ($element['#files'] as $filename => $code) {
      $element['filename_list'][$filename] = [];

      // Add word breaks after every . and / character.
      $display_filename = preg_replace('@[./]@', '\0<wbr>', $filename);

      $element['generate'][$filename] = [
        '#type' => 'checkbox',
        '#title' => $display_filename,
        '#attributes' => [
          'data-generated-file' => $filename,
        ],
      ];

      $element['show'][$filename] = [
        '#attributes' => [
          'data-generated-file' => $filename,
        ],
      ];

      switch ($element['#merge_statuses'][$filename]) {
        case static::MERGE_NEW:
          $merge_status_label = t('New');
          break;

        case static::MERGE_MERGED:
          $merge_status_label = t('Merged');
          break;

        case static::MERGE_OVERWRITTEN:
          $merge_status_label = t('Overwritten');
          break;
      }
      $element['filename_list'][$filename]['merge'] = [
        '#markup' => $merge_status_label,
      ];

      switch ($element['#git_statuses'][$filename]) {
        case static::VERSION_CONTROL_NEW:
          $git_status_label = t('New');
          break;

        case static::VERSION_CONTROL_MANAGED:
            $git_status_label = t('OK');
            break;

        case static::VERSION_CONTROL_UNMANAGED:
          $git_status_label = t('UNMANAGED');
          break;

        case static::VERSION_CONTROL_CHANGED:
          $git_status_label = t('UNCOMMITTED CHANGES');
          break;

      }
      $element['filename_list'][$filename]['version_control'] = [
        '#markup' => $git_status_label,
      ];

      // Determine whether to show a warning on the row.
      $element['filename_list'][$filename]['#warn'] =
        $element['#merge_statuses'][$filename] != static::MERGE_NEW
        ||
        $element['#git_statuses'][$filename] != static::VERSION_CONTROL_NEW;

      $element['code'][$filename]['code'] = [
        '#type' => 'textarea',
        '#title' => t("@filename code", [
          '@filename' => $filename,
          ])
          . ' ' . ($element['#statuses'][$filename] ?? ''),
        '#rows' => count(explode("\n", $code)),
        '#default_value' => $code,
        // This creates an item in form values that just contains the code, so
        // we don't need to filter out the values from button labels when
        // writing code.
        '#parents' => ['file_code', $filename],
        '#attributes' => [
          'data-generated-file' => $filename,
        ],
      ];
    }

    $element['#attached'] = [
      'library' => ['module_builder/generated_files'],
    ];

    return $element;
  }

  /**
   * Element prerender callback.
   */
  public static function preRender($element) {
    // Render each radio element separately, so they can be in the different
    // table rows.
    foreach (Element::children($element['show']) as $key) {
      $element['filename_list'][$key]['show'] = \Drupal::service('renderer')->render($element['show'][$key]);
    }
    return $element;
  }

}
