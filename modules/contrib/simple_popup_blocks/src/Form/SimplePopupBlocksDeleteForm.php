<?php

namespace Drupal\simple_popup_blocks\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SimplePopupBlocksDeleteForm extends ConfirmFormBase {

  /**
   * The ID of the configuration entity to be deleted.
   *
   * @var string|null
   */
  protected $uid;

  /**
   * The messenger service to display status messages.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The configuration factory service to interact with configuration objects.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a SimplePopupBlocksDeleteForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service to display status messages.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service to interact with configuration objects.
   */
  public function __construct(MessengerInterface $messenger, ConfigFactoryInterface $config_factory) {
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('config.factory')
    );
  }

  public function getFormId() {
    return 'simple_popup_blocks_delete_confirmation_form';
  }

  public function getQuestion() {
    return $this->t('Are you sure you want to delete this configuration?');
  }

  public function getCancelUrl() {
    return new RedirectResponse(Url::fromRoute('simple_popup_blocks.manage')->toString());
  }

  public function getConfirmText() {
    return $this->t('Delete');
  }

  public function buildForm(array $form, FormStateInterface $form_state, $uid = NULL) {
    if ($uid === NULL) {
      return new RedirectResponse(Url::fromRoute('simple_popup_blocks.manage')->toString());
    }

    $this->uid = $uid;

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('simple_popup_blocks.popup_' . $this->uid);
    $config->delete();

    $this->messenger->addStatus($this->t('Configuration deleted successfully.'));
    $form_state->setRedirect('simple_popup_blocks.manage');
  }
}