<?php

namespace Drupal\danse;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\danse\Entity\EventInterface;

/**
 * Interface for DANSE plugins.
 */
interface PluginInterface extends PluginInspectionInterface {

  /**
   * Asserts the given payload matching this plugin.
   *
   * @param \Drupal\danse\PayloadInterface $payload
   *   The payload.
   *
   * @return bool
   *   TRUE, if the payload matches this plugin's data structure, FALSE
   *   otherwise.
   */
  public function assertPayload(PayloadInterface $payload): bool;

  /**
   * Asserts that the provided subscription key parts are valid.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|bool
   *   FALSE, if the subscription key parts are invalid. TRUE or a plugin
   *   specific object (e.g. a content entity) otherwise.
   *
   * @see self::setSubscriptionKeyParts()
   */
  public function assertSubscriptionKey();

  /**
   * Sets the subscription key parts.
   *
   * @param array $parts
   *   The subscription key parts.
   */
  public function setSubscriptionKeyParts(array $parts): void;

  /**
   * Builds a subscription key from parts by prepending the plugin ID.
   *
   * @param string ...$parts
   *   The subscription key parts.
   *
   * @return string
   *   The subscription key.
   */
  public function subscriptionKey(string ...$parts): string;

  /**
   * Gets a list of supported subscriptions for given user roles.
   *
   * @param array $roles
   *   The list of user role IDs.
   *
   * @return array
   *   List of supported subscriptions.
   */
  public function getSupportedSubscriptions(array $roles): array;

  /**
   * Adds the plugin related settings to the given settings form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array with a specified submit handler in the form of
   *   "[PLUGIN, METHOD]", or an empty array.
   */
  public function buildForm(array &$form, FormStateInterface $form_state): array;

  /**
   * Gets the URL for the user to be redirected to when viewing a notification.
   *
   * @param \Drupal\danse\Entity\EventInterface $event
   *   The DANSE event.
   *
   * @return \Drupal\Core\Url
   *   The URL to be redirected to.
   */
  public function getRedirectUrl(EventInterface $event): Url;

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated label.
   */
  public function label(): string;

  /**
   * Creates all outstanding notifications for events of this plugin.
   */
  public function createNotifications(): void;

}
