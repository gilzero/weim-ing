<?php

declare(strict_types=1);

namespace Drupal\monolog\Logger\ConditionResolver;

/**
 * Interface for condition resolvers.
 */
interface ConditionResolverInterface {

  /**
   * Resolve which formatter should be used.
   *
   * @return bool
   *   TRUE if the first formatter should be used, FALSE to use the second.
   */
  public function resolve(): bool;

}
