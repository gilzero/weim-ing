<?php

namespace Drupal\phpmailer_smtp\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation definition PhpmailerOauth2Client plugins.
 *
 * @Annotation
 */
class PhpmailerOauth2 extends Plugin {

  /**
   * The PHPMailer OAuth 2 plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

}
