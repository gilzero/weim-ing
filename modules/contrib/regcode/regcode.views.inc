<?php

/**
 * @file
 * Views integration for regcode module.
 */

// cspell:ignore lastused newfields

/**
 * Implements hook_views_handlers().
 */
// phpcs:disable
/*function regcode_views_handlers() {
  return [
    'info' => [
      'path' => drupal_get_path('module', 'regcode') . '/includes',
    ),
    'handlers' => [
      'views_handler_field_term_regcode_tid' => [
        'parent' => 'views_handler_field_prerender_list',
      ),
    ),
  );
}*/
// phpcs:enable

/**
 * Implements hook_views_data().
 */
function regcode_views_data() {
  $data = [];

  $data['regcode']['table']['group'] = t('Regcode');

  $data['regcode']['table']['base'] = [
    'field' => 'rid',
    'title' => t('Registration code'),
    'help' => t('Registration codes created by the regcode module.'),
    'weight' => -10,
  ];

  $data['regcode']['table']['join'] = [
    'users' => [
      'left_field' => 'uid',
      'field' => 'uid',
    ],
  ];

  $data['regcode']['rid'] = [
    'title' => t('ID'),
    'help' => t('The unique index for the regcode'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  $data['regcode']['uid'] = [
    'title' => t('User'),
    'help' => t('The last user to use this registration code.'),
    'relationship' => [
      'base' => 'users_field_data',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('Last user'),
    ],
  ];

  $data['regcode']['code'] = [
    'title' => t('Code'),
    'help' => t('The registration code.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['regcode']['created'] = [
    'title' => t('Created'),
    'help' => t('Date the registration code was created.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  $data['regcode']['lastused'] = [
    'title' => t('Last used'),
    'help' => t('Date the registration code was last used.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  $data['regcode']['begins'] = [
    'title' => t('Begins'),
    'help' => t('Date the registration code becomes available for use.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  $data['regcode']['expires'] = [
    'title' => t('Expires'),
    'help' => t('Date the registration code expires.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  $data['regcode']['is_active'] = [
    'title' => t('Active'),
    'help' => t('Whether the registration code is active.'),
    'field' => [
      'id' => 'boolean',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'boolean',
      'label' => t('Active'),
      'type' => 'yes-no',
      'use_equal' => TRUE,
    ],
  ];

  $data['regcode']['maxuses'] = [
    'title' => t('Maximum uses'),
    'help' => t('The maximum times this code can be used.'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  $data['regcode']['uses'] = [
    'title' => t('Uses'),
    'help' => t('The number of times this code has been used.'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  return $data;
}

/**
 * Implements hook_views_default_views().
 */
function regcode_views_default_views_alter(&$views) {
  if (isset($views['regcode']) && \Drupal::moduleHandler()->moduleExists('views_bulk_operations')) {
    $view = $views['regcode'];
    $handler = $view->display['default'];

    $newfields = [];

    // Field: Bulk operations: Regcode.
    $newfields['views_bulk_operations']['id'] = 'views_bulk_operations';
    $newfields['views_bulk_operations']['table'] = 'regcode';
    $newfields['views_bulk_operations']['field'] = 'views_bulk_operations';
    $newfields['views_bulk_operations']['label'] = '';
    $newfields['views_bulk_operations']['element_label_colon'] = FALSE;
    $newfields['views_bulk_operations']['vbo_settings']['display_type'] = '0';
    $newfields['views_bulk_operations']['vbo_settings']['enable_select_all_pages'] = 1;
    $newfields['views_bulk_operations']['vbo_settings']['force_single'] = 0;
    $newfields['views_bulk_operations']['vbo_settings']['entity_load_capacity'] = '10';
    $newfields['views_bulk_operations']['vbo_operations'] = [
      'action::regcode_activate_action' => [
        'selected' => 1,
        'postpone_processing' => 0,
        'skip_confirmation' => 0,
        'override_label' => 0,
        'label' => '',
      ],
      'action::regcode_deactivate_action' => [
        'selected' => 1,
        'postpone_processing' => 0,
        'skip_confirmation' => 0,
        'override_label' => 0,
        'label' => '',
      ],
      'action::regcode_delete_action' => [
        'selected' => 1,
        'postpone_processing' => 0,
        'skip_confirmation' => 0,
        'override_label' => 0,
        'label' => '',
      ],
      'action::regcode_mailer_mail_action' => [
        'selected' => 1,
        'postpone_processing' => 0,
        'skip_confirmation' => 0,
        'override_label' => 0,
        'label' => '',
      ],
      'action::regcode_tag_action' => [
        'selected' => 1,
        'postpone_processing' => 0,
        'skip_confirmation' => 0,
        'override_label' => 0,
        'label' => '',
      ],
    ];

    $handler->display_options['fields'] = $newfields + $handler->display_options['fields'];
  }
}
