<?php

/**
 * @file
 * Settings form for the Drulma base theme.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function drulma_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {

  $form['drulma'] = [
    '#type' => 'vertical_tabs',
    '#default_tab' => 'edit-drulma-general',
    '#weight' => -10,
  ];

  $form['general'] = [
    '#type' => 'details',
    '#title' => t('General settings'),
    '#description' => t('Contains general settings, layouts, etc.'),
    '#group' => 'drulma',
    '#tree' => TRUE,
  ];

  $form['general']['wrap_content_in_container'] = [
    '#type' => 'checkbox',
    '#title' => t('Wrap content in container'),
    '#description' => t('Wrap the content and sidebar regions with a .container class. <a href="@url">See Bulma documentation</a> and page.html.twig.', [
      '@url' => 'https://bulma.io/documentation/layout/container/',
    ]),
    '#default_value' => theme_get_setting('general.wrap_content_in_container') ?? TRUE,
  ];

  $form['general']['teaser_title_size'] = [
    '#type' => 'select',
    '#title' => t('Size of the teaser title'),
    '#description' => t('Size of teaser titles. <a href="@url">See Bulma documentation</a>.', [
      '@url' => 'https://bulma.io/documentation/modifiers/typography-helpers/',
    ]),
    '#default_value' => theme_get_setting('general.teaser_title_size') ?? '',
    '#options' => ['default' => t('Default')] + array_combine($sizes = range(1, 7), $sizes),
  ];

  $form['hero'] = [
    '#type' => 'details',
    '#title' => t('Hero header'),
    '#description' => t('Configure the hero element at the top of the page.'),
    '#group' => 'drulma',
    '#tree' => TRUE,
  ];
  $form['hero']['color'] = [
    '#type' => 'select',
    '#title' => t('Hero color'),
    '#default_value' => theme_get_setting('hero.color') ?? 'primary',
    '#description' => t('Colors according to the <a href="@url">Bulma hero color options</a>', [
      '@url' => 'https://bulma.io/documentation/layout/hero/#colors',
    ]),
    '#options' => [
      '' => t('Default'),
      'primary' => t('Primary'),
      'info' => t('Info'),
      'success' => t('Success'),
      'warning' => t('Warning'),
      'danger' => t('Danger'),
      'dark' => t('Dark'),
      'light' => t('Light'),
    ],
  ];

  $form['forms'] = [
    '#type' => 'details',
    '#title' => t('Form elements'),
    '#description' => t('<p>Contains settings for form elements, see <a href="@url">documentation</a></p>', [
      '@url' => 'http://bulma.io/documentation/elements/form/',
    ]),
    '#group' => 'drulma',
    '#tree' => TRUE,
  ];

  $form['forms']['input_size'] = [
    '#type' => 'select',
    '#title' => t('Form input size'),
    '#description' => t('Size of the form inputs, select, etc.'),
    '#options' => [
      'small' => t('Small'),
      '' => t('Normal'),
      'medium' => t('Medium'),
      'large' => t('Large'),
    ],
    '#default_value' => theme_get_setting('forms.input_size') ?? '',
  ];

  $form['forms']['label_size'] = [
    '#type' => 'select',
    '#title' => t('Form label size'),
    '#description' => t('Size of the form labels.'),
    '#options' => [
      'small' => t('Small'),
      '' => t('Normal'),
      'medium' => t('Medium'),
      'large' => t('Large'),
    ],
    '#default_value' => theme_get_setting('forms.label_size') ?? '',
  ];

  $form['forms']['input_rounded'] = [
    '#type' => 'checkbox',
    '#title' => t('Rounded inputs'),
    '#description' => t('The edges will be rounded'),
    '#default_value' => theme_get_setting('forms.input_rounded') ?? FALSE,
  ];

  $form['table'] = [
    '#type' => 'details',
    '#title' => t('Tables'),
    '#description' => t('<p>Contains settings for tables, see <a href="@url">documentation</a></p>', [
      '@url' => 'https://bulma.io/documentation/elements/table/',
    ]),
    '#group' => 'drulma',
    '#tree' => TRUE,
  ];

  $form['table']['bordered'] = [
    '#type' => 'checkbox',
    '#title' => t('Add borders to all the cells'),
    '#default_value' => theme_get_setting('table.bordered') ?? FALSE,
  ];
  $form['table']['striped'] = [
    '#type' => 'checkbox',
    '#title' => t('Add stripes to the table.'),
    '#description' => t('Odd and even rows will get different colors'),
    '#default_value' => theme_get_setting('table.striped') ?? FALSE,
  ];
  $form['table']['narrow'] = [
    '#type' => 'checkbox',
    '#title' => t('Make the cells narrower.'),
    '#default_value' => theme_get_setting('table.narrow') ?? FALSE,
  ];
  $form['table']['hoverable'] = [
    '#type' => 'checkbox',
    '#title' => t('Add a hover effect on each row'),
    '#default_value' => theme_get_setting('table.hoverable') ?? FALSE,
  ];
  $form['table']['fullwidth'] = [
    '#type' => 'checkbox',
    '#title' => t('Fullwidth tables'),
    '#default_value' => theme_get_setting('table.fullwidth') ?? FALSE,
  ];

  $form['typography'] = [
    '#type' => 'details',
    '#title' => t('Typography'),
    '#description' => t('<p>Contains settings for typography, see <a href="@url">documentation</a></p>', [
      '@url' => 'https://bulma.io/documentation/modifiers/typography-helpers/',
    ]),
    '#group' => 'drulma',
    '#tree' => TRUE,
  ];
  $form['typography']['content_font_family'] = [
    '#type' => 'select',
    '#title' => t('Content font family'),
    '#description' => t('Font family applied to elements such as long texts'),
    '#options' => [
      'primary' => t('Primary'),
      'secondary' => t('Secondary'),
    ],
    '#default_value' => theme_get_setting('typography.content_font_family') ?? 'primary',
  ];
}
