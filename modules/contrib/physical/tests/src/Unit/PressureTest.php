<?php

namespace Drupal\Tests\physical\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\physical\Pressure;

/**
 * Tests the pressure class.
 *
 * @coversDefaultClass \Drupal\physical\Pressure
 * @group physical
 */
class PressureTest extends UnitTestCase {

  /**
   * The pressure.
   *
   * @var \Drupal\physical\Pressure
   */
  protected $pressure;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->pressure = new Pressure('3', 'bar');
  }

  /**
   * Tests response to invalid inputs.
   *
   * @covers ::__construct
   */
  public function testInvalidUnit() {
    $this->expectException(\InvalidArgumentException::class);
    new Pressure('1', 'm');
  }

  /**
   * Tests unit conversion.
   *
   * @covers ::convert
   */
  public function testConvert() {
    $this->assertEquals(new Pressure('43.51', 'psi'), $this->pressure->convert('psi')->round(2));
    $this->assertEquals(new Pressure('2250', 'torr'), $this->pressure->convert('torr')->round());
    $this->assertEquals(new Pressure('2.961', 'atm'), $this->pressure->convert('atm')->round(3));
    $this->assertEquals(new Pressure('3.0591', 'at'), $this->pressure->convert('at')->round(4));
    $this->assertEquals(new Pressure('300000', 'pa'), $this->pressure->convert('pa')->round());
    $this->assertEquals(new Pressure('1205.59', 'inh2o'), $this->pressure->convert('inh2o')->round(2));
    $this->assertEquals(new Pressure('88.59', 'inhg'), $this->pressure->convert('inhg')->round(2));
  }

}
