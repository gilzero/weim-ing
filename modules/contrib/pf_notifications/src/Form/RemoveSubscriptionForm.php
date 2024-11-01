<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Form;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\pf_notifications\Service\BaseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remove push notification subscription.
 *
 * @phpstan-consistent-constructor
 */
class RemoveSubscriptionForm extends ConfirmFormBase {

  /**
   * Unique subscription token value.
   *
   * @var string
   */
  protected string $token;

  /**
   * Unique DANSE key in users_data table.
   *
   * @var string
   */
  protected string $danseKey;

  /**
   * The original value (0 or 1) for DANSE content key, before our data.
   *
   * @var int
   */
  protected int $danseActive;

  /**
   * User id.
   *
   * @var int
   */
  protected int $uid;

  /**
   * Test notification id.
   *
   * @var bool|string
   */
  protected bool|string $test;

  /**
   * Constructs a RemoveSubscription object.
   */
  public function __construct(
    protected BaseInterface $service,
  ) {
    $this->token = $this->getRequest()->query->get('token');
    $this->danseKey = $this->getRequest()->query->get('danse_key');
    $this->danseActive = (int) $this->getRequest()->query->get('danse_active');
    $this->uid = (int) $this->getRequest()->query->get('uid');
    $this->test = $this->getRequest()->query->get('test') ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('pf_notifications.base'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'subscription_remove_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): MarkupInterface {
    $user_link = Link::createFromRoute('User: ' . $this->uid, 'entity.user.canonical',
      ['user' => $this->uid],
      [
        'attributes' => [
          'target' => '_blank',
        ],
      ]);
    return $this->t('<p>Subscription data:</p><ul><li>@user_link</li><li>Content: %danse_key</li><li>Token: %token</li></ul>', [
      '@user_link' => $user_link->toString(),
      '%token' => $this->token,
      '%danse_key' => $this->danseKey,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): MarkupInterface {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): MarkupInterface {
    return $this->t('Remove notification subscription?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute(BaseInterface::REDIRECT_ROUTE);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->service->deleteSubscription($this->uid, $this->danseKey, $this->token, $this->danseActive, $this->test);
    $form_state->setRedirect(BaseInterface::REDIRECT_ROUTE);
  }

}
