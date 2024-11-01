<?php

namespace Drupal\smtp_multiple\Plugin\Mail;

use Drupal\smtp\Plugin\Mail\SMTPMailSystem;

/**
 * Allow for sending SMTP messages with multiple configurations.
 *
 * @Mail(
 *   id = "smtp_multiple_mail_system",
 *   label = @Translation("SMTP Multiple Mailer"),
 *   description = @Translation("Allows for sending SMTP messages with multiple configurations.")
 * )
 */
class SmtpMultipleMailSystem extends SMTPMailSystem {

  public function mail(array $message) {
    if (isset($message['id'])) {

      $config = $this->smtpConfig->get();
      unset($config['langcode'], $config['_core']);

      // Alter config in hooks.
      \Drupal::moduleHandler()->alter('smtp_multiple_config', $config, $message['id']);

      // Alter config in settings file.
      $setting_overrides = \Drupal::service('settings')::get('smtp_multiple_config');
      if (isset($setting_overrides[$message['id']])) {
        foreach ($setting_overrides[$message['id']] as $key => $override) {
          $config[$key] = $override;
        }
      }

      $this->smtpConfig->setSettingsOverride($config);
    }

    return parent::mail($message);
  }

}
