<?php

namespace Drupal\copyright_footer\Plugin\Block;

/**
 * @file
 * Contains \Drupal\copyright_footer\Plugin\Block\CopyrightFooter.
 */

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Copyright Footer module for Block.
 *
 * @Block (
 *   id = "copyright_footer",
 *   admin_label = @Translation("Copyright Footer"),
 *   category = @Translation("Custom")
 * )
 */
class CopyrightFooter extends BlockBase {

  /**
   * A version.
   *
   * @var string
   */
  public const VERSION = '3.x-dev';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {

    return [
      'organization_name' => '',
      'organization_url' => '',
      'year_origin' => '',
      'year_to_date' => '',
      'version' => '',
      'version_url' => '',
      'label_display' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {

    $form['organization_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization name'),
      '#default_value' => $this->configuration['organization_name'],
    ];

    $form['organization_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Organization URL'),
      '#description' => $this->t('Leave blank if not necessary.'),
      '#default_value' => $this->configuration['organization_url'],
    ];

    $form['year_origin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Year origin from'),
      '#description' => $this->t('Leave blank if not necessary.'),
      '#default_value' => $this->configuration['year_origin'],
    ];

    $date = new \DateTime();
    $form['year_to_date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Year to date'),
      '#description' => $this->t('Leave blank then the current year (@year) automatically shows up.',
        ['@year' => $date->format('Y')]),
      '#default_value' => $this->configuration['year_to_date'],
    ];

    $form['version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Version'),
      '#description' => $this->t('Leave blank if not necessary.'),
      '#default_value' => $this->configuration['version'],
    ];

    $form['version_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Version URL'),
      '#description' => $this->t('Leave blank if not necessary. It works w/ the version number above.')
      . $this->t("If you don't input the version number, this field will be simply ignored."),
      '#default_value' => $this->configuration['version_url'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['organization_name'] = $form_state->getValue('organization_name');
    $this->configuration['organization_url'] = $form_state->getValue('organization_url');
    $this->configuration['year_origin'] = $form_state->getValue('year_origin');
    $this->configuration['year_to_date'] = $form_state->getValue('year_to_date');
    $this->configuration['version'] = $form_state->getValue('version');
    $this->configuration['version_url'] = $form_state->getValue('version_url');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $date = new \DateTime();

    // From $year_to_date to Present.
    $year_to_date = empty($this->configuration['year_to_date'])
      ? $date->format('Y')
      : $this->configuration['year_to_date'];

    // Organization.
    $organization = $this->configuration['organization_name'];

    // Organization w/ URL.
    if (!empty($organization)
    && !empty($this->configuration['organization_url'])) {
      $url = Url::fromUri($this->configuration['organization_url']);
      $organization = Link::fromTextAndUrl($this->configuration['organization_name'], $url)->toString();
    }

    // Version.
    $version = $this->configuration['version'];
    if (!empty($version)) {

      // Version only.
      $version = $this->t('ver.@version', [
        '@version' => $version,
      ]);

      // Version w/ URL.
      if ($this->configuration['version_url']) {
        $url = Url::fromUri($this->configuration['version_url']);
        $version = $this->t('ver.@version', [
          '@version' => Link::fromTextAndUrl($this->configuration['version'], $url)->toString(),
        ]);
      }
    }

    $year = $date->format('Y');
    return empty($this->configuration['year_origin'])
      // Convert to a string, use "{...}" against a string variable $year.
      || ("{$this->configuration['year_origin']}" === $year)
      ? [
        '#type' => 'markup',
        '#markup' => $this->t('Copyright &copy; @year @organization @version', [
          '@year' => $year,
          '@organization' => $organization,
          '@version' => $version,
        ]),
      ]
      : [
        '#type' => 'markup',
        '#markup' => $this->t('Copyright &copy; @year_origin-@year_to_date @organization @version', [
          '@year_origin' => $this->configuration['year_origin'],
          '@year_to_date' => $year_to_date,
          '@organization' => $organization,
          '@version' => $version,
        ]),
      ];
  }

}
