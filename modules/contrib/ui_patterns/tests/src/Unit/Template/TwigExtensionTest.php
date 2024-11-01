<?php

namespace Drupal\Tests\ui_patterns\Unit\Template;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\Tests\UnitTestCase;
use Drupal\ui_patterns\Template\TwigExtension;

/**
 * Tests the twig extension.
 *
 * @group Template
 *
 * @coversDefaultClass \Drupal\Core\Template\TwigExtension
 */
class TwigExtensionTest extends UnitTestCase {

  /**
   * The system under test.
   *
   * @var \Drupal\Core\Template\TwigExtension
   */
  protected $systemUnderTest;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->systemUnderTest = new TwigExtension();
  }

  /**
   * Tests Twig 'add_class' filter.
   *
   * @covers ::addClass
   * @dataProvider providerTestTwigAddClass
   */
  public function testTwigAddClass($element, $classes, $expected_result) {
    $processed = $this->systemUnderTest->addClass($element, $classes);
    $this->assertEquals($expected_result, $processed);
  }

  /**
   * A data provider for ::testTwigAddClass().
   *
   * @return \Iterator
   *   An iterator.
   */
  public static function providerTestTwigAddClass(): \Iterator {
    yield 'should add a class on element' => [
      ['#type' => 'container'],
      'my-class',
      ['#type' => 'container', '#attributes' => ['class' => ['my-class']]],
    ];

    yield 'should add a class from a array of string keys on element' => [
      ['#type' => 'container'],
      ['my-class'],
      ['#type' => 'container', '#attributes' => ['class' => ['my-class']]],
    ];

    yield 'should add a class from a Markup value' => [
      ['#type' => 'container'],
      [Markup::create('my-class')],
      ['#type' => 'container', '#attributes' => ['class' => ['my-class']]],
    ];

    yield 'should add a class when an attributes array is already present' => [
      [
        '#type' =>
        'container',
        '#attributes' => [
          'foo' => 'bar',
        ],
      ],
      [Markup::create('my-class')],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['my-class'],
          'foo' => 'bar',
        ],
      ],
    ];

    yield 'should add a class when an attributes object is already present' => [
      [
        '#type' =>
        'container',
        '#attributes' => new Attribute([
          'foo' => 'bar',
        ]),
      ],
      [Markup::create('my-class')],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['my-class'],
          'foo' => 'bar',
        ],
      ],
    ];

    yield '#printed should be removed after class(es) added' => [
      [
        '#markup' => 'This content is already is rendered',
        '#printed' => TRUE,
      ],
      '',
      [
        '#markup' => 'This content is already is rendered',
        '#attributes' => [
          'class' => [''],
        ],
      ],
    ];
  }

  /**
   * Tests Twig 'set_attribute' filter.
   *
   * @covers ::setAttribute
   * @dataProvider providerTestTwigSetAttribute
   */
  public function testTwigSetAttribute($element, $key, $value, $expected_result) {
    $processed = $this->systemUnderTest->setAttribute($element, $key, $value);
    $this->assertEquals($expected_result, $processed);
  }

  /**
   * A data provider for ::testTwigSetAttribute().
   *
   * @return \Iterator
   *   An iterator.
   */
  public static function providerTestTwigSetAttribute(): \Iterator {
    yield 'should add attributes on element' => [
      ['#theme' => 'image'],
      'title',
      'Aloha',
      [
        '#theme' => 'image',
        '#attributes' => [
          'title' => 'Aloha',
        ],
      ],
    ];

    yield 'should merge existing attributes on element' => [
      [
        '#theme' => 'image',
        '#attributes' => [
          'title' => 'Aloha',
        ],
      ],
      'title',
      'Bonjour',
      [
        '#theme' => 'image',
        '#attributes' => [
          'title' => 'Bonjour',
        ],
      ],
    ];

    yield 'should add JSON attribute value correctly on element' => [
      ['#type' => 'container'],
      'data-slider',
      Json::encode(['autoplay' => TRUE]),
      [
        '#type' => 'container',
        '#attributes' => [
          'data-slider' => '{"autoplay":true}',
        ],
      ],
    ];

    yield '#printed should be removed after setting attribute' => [
      [
        '#markup' => 'This content is already is rendered',
        '#printed' => TRUE,
      ],
      'title',
      NULL,
      [
        '#markup' => 'This content is already is rendered',
        '#attributes' => [
          'title' => NULL,
        ],
      ],
    ];
  }

}
