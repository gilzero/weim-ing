<?php

declare(strict_types=1);

namespace Drupal\Tests\regcode\Unit\Integration\Event;

/**
 * Checks that the event "regcode.code_used" is correctly defined.
 *
 * @coversDefaultClass \Drupal\regcode\Event\RegcodeUsedEvent
 * @group regcode
 */
class RegcodeUsedTest extends EventTestBase {

  /**
   * Tests the event metadata.
   */
  public function testRegcodeUsedEvent(): void {
    $event = $this->eventManager->createInstance('regcode.code_used');

    $user_context_definition = $event->getContextDefinition('user');
    $this->assertSame('entity:user', $user_context_definition->getDataType());
    $this->assertSame('The user using the code', $user_context_definition->getLabel());

    $regcode_context_definition = $event->getContextDefinition('regcode');
    $this->assertSame('string', $regcode_context_definition->getDataType());
    $this->assertSame('The regcode which was used', $regcode_context_definition->getLabel());
  }

}
