<?php

namespace Drupal\phpmailer_smtp\Plugin\PhpmailerOauth2;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface for PHPMailer Oauth2 plugins.
 */
interface PhpmailerOauth2PluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface, PluginFormInterface, ConfigurableInterface {

  /**
   * Retrieves the human-readable name of the PHPMailer Oauth2 plugin.
   *
   * @return string
   *   The name of the plugin.
   */
  public function getName();

  /**
   * Retrieves the id of the PHPMailer OAuth2 plugin.
   *
   * @return string
   *   The id of the plugin.
   */
  public function getId();

  /**
   * Retrieves the auth options for PHPMailer OAuth.
   *
   * @return array
   *   The array of auth options, including the provider.
   */
  public function getAuthOptions();

}
