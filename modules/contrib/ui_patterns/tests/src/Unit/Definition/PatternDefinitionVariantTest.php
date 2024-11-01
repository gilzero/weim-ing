<?php

declare(strict_types = 1);

namespace Drupal\Tests\ui_patterns\Unit\Definition;

use Drupal\Tests\ui_patterns\Unit\UiPatternsTestBase;
use Drupal\ui_patterns\Definition\PatternDefinitionVariant;

/**
 * @coversDefaultClass \Drupal\ui_patterns\Definition\PatternDefinitionVariant
 *
 * @group ui_patterns
 */
class PatternDefinitionVariantTest extends UiPatternsTestBase {

  /**
   * Test constructor with either scalar or array.
   *
   * @covers ::__construct
   * @covers ::hasUse
   * @covers ::setDescription
   * @covers ::setUse
   */
  public function testConstructor(): void {
    // Scalar value.
    $patternDefinitionVariant = new PatternDefinitionVariant('test', 'my label');
    $this->assertEquals('test', $patternDefinitionVariant->getName());
    $this->assertEquals('my label', $patternDefinitionVariant->getLabel());
    $this->assertEquals(NULL, $patternDefinitionVariant->getDescription());
    $this->assertEquals(NULL, $patternDefinitionVariant->getUse());
    $this->assertFalse($patternDefinitionVariant->hasUse());

    // Array without name.
    $patternDefinitionVariant = new PatternDefinitionVariant('test', [
      'label' => 'my label',
    ]);
    $this->assertEquals('test', $patternDefinitionVariant->getName());
    $this->assertEquals('my label', $patternDefinitionVariant->getLabel());
    $this->assertEquals(NULL, $patternDefinitionVariant->getDescription());
    $this->assertEquals(NULL, $patternDefinitionVariant->getUse());
    $this->assertFalse($patternDefinitionVariant->hasUse());

    // Array with name.
    $patternDefinitionVariant = new PatternDefinitionVariant('test', [
      'name' => 'my name',
      'label' => 'my label',
    ]);
    $this->assertEquals('my name', $patternDefinitionVariant->getName());
    $this->assertEquals('my label', $patternDefinitionVariant->getLabel());
    $this->assertEquals(NULL, $patternDefinitionVariant->getDescription());
    $this->assertEquals(NULL, $patternDefinitionVariant->getUse());
    $this->assertFalse($patternDefinitionVariant->hasUse());

    // Other attributes.
    $patternDefinitionVariant = new PatternDefinitionVariant('test', [
      'name' => 'my name',
      'label' => 'my label',
      'description' => 'my description',
      'use' => 'template.twig',
    ]);
    $this->assertEquals('my name', $patternDefinitionVariant->getName());
    $this->assertEquals('my label', $patternDefinitionVariant->getLabel());
    $this->assertEquals('my description', $patternDefinitionVariant->getDescription());
    $this->assertEquals('template.twig', $patternDefinitionVariant->getUse());
    $this->assertTrue($patternDefinitionVariant->hasUse());

    // Setters.
    $patternDefinitionVariant->setDescription('new description');
    $this->assertEquals('new description', $patternDefinitionVariant->getDescription());
    $patternDefinitionVariant->setUse('new use');
    $this->assertEquals('new use', $patternDefinitionVariant->getUse());
  }

  /**
   * Test getters.
   *
   * @dataProvider definitionGettersProvider
   *
   * @covers ::getName
   * @covers ::getLabel
   * @covers ::getDescription
   * @covers ::getUse
   */
  public function testGetters($getter, $name, $value): void {
    $patternDefinitionVariant = new PatternDefinitionVariant('test', [$name => $value]);
    $this->assertEquals($value, call_user_func([$patternDefinitionVariant, $getter]));
  }

  /**
   * Provider.
   *
   * @return array
   *   Data.
   */
  public static function definitionGettersProvider(): array {
    return [
      ['getName', 'name', 'Variant name'],
      ['getLabel', 'label', 'Variant label'],
      ['getDescription', 'description', 'Variant description.'],
      ['getUse', 'use', 'template.twig'],
    ];
  }

}
