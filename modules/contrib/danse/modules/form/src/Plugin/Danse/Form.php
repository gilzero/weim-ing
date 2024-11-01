<?php

namespace Drupal\danse_form\Plugin\Danse;

use Drupal\Core\Form\FormStateInterface;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadInterface;
use Drupal\danse\PluginBase;
use Drupal\danse_form\Payload;

/**
 * Plugin implementation of DANSE.
 *
 * @Danse(
 *   id = "form",
 *   label = @Translation("Form"),
 *   description = @Translation("Manages all form events.")
 * )
 */
class Form extends PluginBase {

  public const TOPIC_SUBMIT = 'submit';

  /**
   * {@inheritdoc}
   */
  public function assertPayload(PayloadInterface $payload): bool {
    return $payload instanceof Payload;
  }

  /**
   * Create a new DANSE form event.
   *
   * @param string $topic
   *   The topic.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\danse\Entity\EventInterface|null
   *   The DANSE form event, if is was possible to be created, NULL otherwise.
   */
  public function createFormEvent(string $topic, array $form, FormStateInterface $form_state): ?EventInterface {
    $message = $form['#form_id'];
    $payload = new Payload($form);
    return $this->createEvent($topic, $message, $payload);
  }

}
