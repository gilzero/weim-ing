<?php

namespace Drupal\xray_audit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\xray_audit\Services\CacheManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to flush Xray Audit Cache.
 */
final class FlushCacheForm extends FormBase {

  /**
   * Cache manager.
   *
   * @var \Drupal\xray_audit\Services\CacheManagerInterface
   */
  protected $cacheManager;

  /**
   * Constructor for MenuSelectorForm.
   *
   * @param \Drupal\xray_audit\Services\CacheManagerInterface $cache_manager
   *   Cache manager service.
   */
  public function __construct(CacheManagerInterface $cache_manager) {
    $this->cacheManager = $cache_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('xray_audit.cache_manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "xray_audit_flush_cache_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Flush Xray Audit cache'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->cacheManager->clearAllCache();
    $this->messenger()->addMessage($this->t('Xray Audit Cache flushed.'));
  }

}
