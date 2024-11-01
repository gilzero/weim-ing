<?php

namespace Drupal\eca_cm;

use Drupal\eca\Entity\Eca;

/**
 * Various helper methods for the Classic modeller.
 */
final class EcaCm {

  /**
   * Looks up whether the given config key is used by another config entry.
   *
   * @param \Drupal\eca\Entity\Eca $eca
   *   The ECA configuration entity.
   * @param string $config_key
   *   The config key to lookup. This identifies a configured event, condition
   *   or action plugin.
   * @param string $plugin_type
   *   The plugin type, either one of "event", "condition" or "action".
   *
   * @return bool
   *   Returns TRUE if used, FALSE otherwise.
   */
  public static function configKeyIsUsed(Eca $eca, string $config_key, string $plugin_type): bool {
    foreach (['events', 'actions'] as $group) {
      foreach (($eca->get($group) ?? []) as $config_array) {
        foreach (($config_array['successors'] ?? []) as $successor) {
          if (($plugin_type === 'condition') && isset($successor['condition']) && ($successor['condition'] === $config_key)) {
            return TRUE;
          }
          if ($plugin_type !== 'condition' && isset($successor['id']) && ($successor['id'] === $config_key)) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

}
