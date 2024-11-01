<?php

namespace Drupal\security_review;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin Manager for SecurityChecks.
 */
class SecurityCheckPluginManager extends DefaultPluginManager {

  use LoggerChannelTrait;
  use SecurityReviewHelperTrait;
  use StringTranslationTrait;

  /**
   * Constructs a new SecurityCheckPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/SecurityCheck', $namespaces, $module_handler, 'Drupal\security_review\SecurityCheckInterface', 'Drupal\security_review\Annotation\SecurityCheck');
    $this->alterInfo('security_review_check_info');
    $this->setCacheBackend($cache_backend, 'security_review_check');
  }

  /**
   * Returns every Check.
   *
   * @return array
   *   Array of SecurityCheckInterfaces.
   */
  public function getChecks(): array {
    $checks = [];
    $definitions = $this->getDefinitions();
    foreach ($definitions as $definition) {
      try {
        $checks[] = $this->createInstance($definition['id']);
      }
      catch (PluginException) {
        $this->getLogger('security_review')->log(RfcLogLevel::ERROR, $this->t('Error creating instance for plugin with ID: @id', ['@id' => $definition['id']]));
      }
    }

    // Sort the checks.
    usort($checks, [$this, 'compareChecks']);

    return $checks;
  }

  /**
   * Finds a Check by its id.
   *
   * @param string $id
   *   The machine namespace of the requested check.
   *
   * @return null|\Drupal\security_review\SecurityCheckInterface
   *   The Check or null if it doesn't exist.
   */
  public function getCheckById(string $id): ?SecurityCheckInterface {
    foreach (static::getChecks() as $check) {
      if ($check->getPluginId() === $id) {
        return $check;
      }
    }
    return NULL;
  }

  /**
   * Finds a check by its namespace and title.
   *
   * @param string $namespace
   *   The machine namespace of the requested check.
   * @param string $title
   *   The machine title of the requested check.
   *
   * @return null|\Drupal\security_review\SecurityCheckInterface
   *   The Check or null if it doesn't exist.
   */
  public function getCheck(string $namespace, string $title): ?SecurityCheckInterface {
    foreach (static::getChecks() as $check) {
      $same_namespace = $this->getMachineName($check->getNamespace()) == $namespace;
      $same_title = $this->getMachineName($check->getTitle()) == $title;
      if ($same_namespace && $same_title) {
        return $check;
      }
    }
    return NULL;
  }

  /**
   * Helper function for sorting checks.
   *
   * @param \Drupal\security_review\SecurityCheckInterface $a
   *   Check A.
   * @param \Drupal\security_review\SecurityCheckInterface $b
   *   Check B.
   *
   * @return int
   *   The comparison's result.
   */
  private function compareChecks(SecurityCheckInterface $a, SecurityCheckInterface $b): int {
    // If one comes from security_review and the other doesn't, prefer the one
    // with the security_review namespace.
    $a_is_local = $this->getMachineName($a->getNamespace()) == 'security_review';
    $b_is_local = $this->getMachineName($b->getNamespace()) == 'security_review';
    if ($a_is_local && !$b_is_local) {
      return -1;
    }
    elseif (!$a_is_local && $b_is_local) {
      return 1;
    }
    else {
      if ($a->getNamespace() == $b->getNamespace()) {
        // If the namespaces match, sort by title.
        return strcmp($a->getTitle(), $b->getTitle());
      }
      else {
        // If the namespaces don't mach, sort by namespace.
        return strcmp($a->getNamespace(), $b->getNamespace());
      }
    }
  }

}
