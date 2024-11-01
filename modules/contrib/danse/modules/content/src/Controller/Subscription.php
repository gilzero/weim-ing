<?php

namespace Drupal\danse_content\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\danse\Service as DanseService;
use Drupal\danse_content\Service;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Subscription controller.
 */
final class Subscription implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The DANSE services.
   *
   * @var \Drupal\danse\Service
   */
  protected DanseService $service;

  /**
   * The DANSE content services.
   *
   * @var \Drupal\danse_content\Service
   */
  protected Service $contentService;

  /**
   * The user data storage service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Subscription constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\danse\Service $service
   *   The DANSE services.
   * @param \Drupal\danse_content\Service $content_service
   *   The DANSE content services.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data storage service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DanseService $service, Service $content_service, UserDataInterface $user_data, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->service = $service;
    $this->contentService = $content_service;
    $this->userData = $user_data;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): Subscription {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('danse.service'),
      $container->get('danse_content.service'),
      $container->get('user.data'),
      $container->get('current_user')
    );
  }

  /**
   * Checks access for the DANSE subscription controller.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   * @param string $key
   *   The subscription key.
   * @param bool $checkSubscribe
   *   TRUE, if checking subscribe, FALSE if unsubscribe.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkAccess(string $entity_type, string $entity_id, string $key, bool $checkSubscribe): AccessResultInterface {
    try {
      /**
       * @var \Drupal\Core\Entity\ContentEntityInterface|null $entity
       */
      $entity = $this->entityTypeManager->getStorage($entity_type)
        ->load($entity_id);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return AccessResult::forbidden('Invalid entity type.');
    }
    if ($entity === NULL) {
      return AccessResult::forbidden('Not found.');
    }
    if (!$entity->access('view')) {
      return AccessResult::forbidden('Access denied.');
    }
    $plugin = $this->service->getPluginInstanceFromSubscriptionKey($key);
    if ($plugin === NULL) {
      return AccessResult::forbidden('Invalid key.');
    }
    /**
     * @var \Drupal\Core\Entity\ContentEntityInterface|bool $keyEntity
     */
    $keyEntity = $plugin->assertSubscriptionKey();
    if ($keyEntity === FALSE) {
      return AccessResult::forbidden('User not permitted for this key.');
    }
    if ($keyEntity instanceof ContentEntityInterface && ($entity->getEntityTypeId() !== $keyEntity->getEntityTypeId() || $entity->id() !== $keyEntity->id())) {
      return AccessResult::forbidden('Malformed request.');
    }
    $flag = $this->userData->get('danse', $this->currentUser->id(), $key);
    if ((bool) $flag === $checkSubscribe) {
      return AccessResult::forbidden('User already subscribed/unsubscribed.');
    }
    return AccessResult::allowed();
  }

  /**
   * Checks access for the DANSE subscription controller.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   * @param string $key
   *   The subscription key.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccessSubscribe(string $entity_type, string $entity_id, string $key): AccessResultInterface {
    return $this->checkAccess($entity_type, $entity_id, $key, TRUE);
  }

  /**
   * Checks access for the DANSE subscription controller.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   * @param string $key
   *   The subscription key.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccessUnsubscribe(string $entity_type, string $entity_id, string $key): AccessResultInterface {
    return $this->checkAccess($entity_type, $entity_id, $key, FALSE);
  }

  /**
   * Ajax callback from the subscription widget.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response.
   */
  protected function widget(string $entity_type, string $entity_id): AjaxResponse {
    $response = new AjaxResponse();
    try {
      /**
       * @var \Drupal\Core\Entity\ContentEntityInterface $entity
       */
      $entity = $this->entityTypeManager->getStorage($entity_type)
        ->load($entity_id);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // This should never happen as we checked that in access callback before.
      return $response;
    }
    if ($entity !== NULL) {
      $selector = '#' . $this->contentService->widgetId($entity);
      $response->addCommand(new ReplaceCommand($selector, $this->contentService->widget($entity, TRUE)));
    }
    return $response;
  }

  /**
   * Ajax callback from the subscription widget to subscribe.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   * @param string $key
   *   The subscription key.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response.
   */
  public function subscribe(string $entity_type, string $entity_id, string $key): AjaxResponse {
    $this->userData->set('danse', $this->currentUser->id(), $key, 1);
    return $this->widget($entity_type, $entity_id);
  }

  /**
   * Ajax callback from the subscription widget to unsubscribe.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   * @param string $key
   *   The subscription key.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response.
   */
  public function unsubscribe(string $entity_type, string $entity_id, string $key): AjaxResponse {
    $this->userData->set('danse', $this->currentUser->id(), $key, 0);
    return $this->widget($entity_type, $entity_id);
  }

}
