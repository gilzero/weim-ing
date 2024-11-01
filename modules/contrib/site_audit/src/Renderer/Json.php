<?php

namespace Drupal\site_audit\Renderer;

use Drupal\site_audit\Renderer;

/**
 *
 */
class Json extends Renderer {

  /**
   *
   */
  public function render($detail = FALSE) {
    $checklist = [
      'percent' => $this->checklist->getPercent(),
      'label' => $this->checklist->getLabel(),
      'checks' => [],
    ];
    foreach ($this->checklist->getCheckObjects() as $check) {
      $checklist['checks'][get_class($check)] = [
        'label' => $check->getLabel(),
        'description' => $check->getDescription(),
        'result' => $check->getResult(),
        'action' => $check->renderAction(),
        'score' => $check->getScore(),
      ];
    }
    return json_encode($checklist);
  }

}
