<?php

declare(strict_types=1);

namespace Drupal\Tests\smart_trim\Unit;

use Drupal\smart_trim\TruncateHTML;
use Drupal\Tests\UnitTestCase;

// cspell:ignore quickedit

/**
 * Unit Test coverage.
 *
 * @coversDefaultClass \Drupal\smart_trim\TruncateHTML
 *
 * @group smart_trim
 */
class TruncateHTMLTest extends UnitTestCase {

  /**
   * Testing truncateChars.
   *
   * @covers ::truncateChars
   *
   * @dataProvider truncateCharsDataProvider
   */
  public function testTruncateChars($html, $limit, $ellipsis, $expected) {
    $truncate = new TruncateHTML();
    $this->assertSame($expected, $truncate->truncateChars($html, $limit, $ellipsis));
  }

  /**
   * Data provider for testTruncateChars().
   */
  public static function truncateCharsDataProvider(): array {
    return [
      [
        'A test string',
        5,
        '…',
        'A…',
      ],
      [
        'A test string',
        5,
        '\u2026',
        'A…',
      ],
      [
        'A test string',
        5,
        '\\\\u2026',
        'A\u2026',
      ],
      [
        '“I like funky quotes”',
        5,
        '',
        '“I',
      ],
      [
        '“I <em>really, really</em> like funky quotes”',
        14,
        '',
        '“I <em>really</em>',
      ],
      [
        'Maximum nesting level protection.' . str_repeat(" <h4>Hello world</h4>", 500),
        63,
        '',
        'Maximum nesting level protection. <h4>Hello world</h4> <h4>Hello world</h4> <h4>Hello</h4>',
      ],
      [
        'Armenian character test Հ from issue 3334442',
        25,
        '',
        'Armenian character test Հ',
      ],
      [
        'This<!-- Hey now --> includes a HTML comment.',
        15,
        '',
        'This includes a',
      ],
      [
        'This<!-- Hey now //--> includes a slightly different style HTML comment.',
        30,
        '',
        'This includes a slightly',
      ],
      [
        '<!-- Hey now //-->This <div><!-- Hey now //--><span>includes <!-- Hey now //--></span> more complicated </div>HTML and a comment.',
        30,
        '',
        'This <div><span>includes </span> more</div>',
      ],
      [
        '<!-- THEME DEBUG --><!-- THEME HOOK: "node" --><!-- FILE NAME SUGGESTIONS:   * node--view--frontpage--page-1.html.twig   * node--view--frontpage.html.twig   x node.html.twig--><!-- BEGIN OUTPUT from "core/themes/classy/templates/content/node.html.twig" --><article data-history-node-id="1" data-quickedit-entity-id="node/1" role="article" class="contextual-region node node--type-article node--promoted node--view-mode-teaser" about="/node/1" typeof="schema:Article" data-quickedit-entity-instance-id="0">And like shipwrecked men turning to seawater foregoing uncontrollable thirst, many have died trying.</article><!-- END OUTPUT from "core/themes/classy/templates/content/node.html.twig" -->',
        23,
        '',
        '<article data-history-node-id="1" data-quickedit-entity-id="node/1" role="article" class="contextual-region node node--type-article node--promoted node--view-mode-teaser" about="/node/1" typeof="schema:Article" data-quickedit-entity-instance-id="0">And like shipwrecked</article>',
      ],
    ];
  }

  /**
   * Covers TruncateWords.
   *
   * @covers ::truncateWords
   *
   * @dataProvider truncateWordsDataProvider
   */
  public function testTruncateWords($html, $limit, $ellipsis, $expected): void {
    $truncate = new TruncateHTML();
    $this->assertSame($expected, $truncate->truncateWords($html, $limit, $ellipsis));
  }

  /**
   * Data provider for testTruncateWords().
   */
  public static function truncateWordsDataProvider(): array {
    return [
      [
        'A test string',
        2,
        '…',
        'A test…',
      ],
      [
        'A test string',
        2,
        '\u2026',
        'A test…',
      ],
      [
        'A test string',
        3,
        '…',
        'A test string',
      ],
      [
        '“I like funky quotes”',
        2,
        '',
        '“I like',
      ],
      [
        '“I like funky quotes”',
        4,
        '',
        '“I like funky quotes”',
      ],
      [
        '“I <em>really, really</em> like funky quotes”',
        2,
        '',
        '“I <em>really</em>',
      ],
      [
        'Maximum nesting level protection.' . str_repeat(" <h4>Hello world</h4>", 500),
        12,
        '',
        'Maximum nesting level protection. <h4>Hello world</h4> <h4>Hello world</h4> <h4>Hello world</h4> <h4>Hello world</h4>',
      ],
      [
        '<p><strong>Every <em>man <s>who has lotted here over the centuries, has looked up</s> to the light</em> and imagined climbing to freedom.</strong></p>',
        10,
        '',
        '<p><strong>Every <em>man <s>who has lotted here over the centuries, has</s></em></strong></p>',
      ],
      [
        'This<!-- Hey now --> includes a HTML comment.',
        2,
        '',
        'This includes',
      ],
      [
        'This<!-- Hey now //--> includes a slightly different style HTML comment.',
        4,
        '',
        'This includes a slightly',
      ],
      [
        '<!-- Hey now //-->This <div><!-- Hey now //--><span>includes <!-- Hey now //--></span> more complicated </div>HTML and a comment.',
        4,
        '',
        'This <div><span>includes </span> more complicated</div>',
      ],
      [
        '<!-- THEME DEBUG --><!-- THEME HOOK: "node" --><!-- FILE NAME SUGGESTIONS:   * node--view--frontpage--page-1.html.twig   * node--view--frontpage.html.twig   x node.html.twig--><!-- BEGIN OUTPUT from "core/themes/classy/templates/content/node.html.twig" --><article data-history-node-id="1" data-quickedit-entity-id="node/1" role="article" class="contextual-region node node--type-article node--promoted node--view-mode-teaser" about="/node/1" typeof="schema:Article" data-quickedit-entity-instance-id="0">And like shipwrecked men turning to seawater foregoing uncontrollable thirst, many have died trying.</article><!-- END OUTPUT from "core/themes/classy/templates/content/node.html.twig" -->',
        3,
        '',
        '<article data-history-node-id="1" data-quickedit-entity-id="node/1" role="article" class="contextual-region node node--type-article node--promoted node--view-mode-teaser" about="/node/1" typeof="schema:Article" data-quickedit-entity-instance-id="0">And like shipwrecked</article>',
      ],
    ];
  }

  /**
   * Test for removeHtmlComments() protected method.
   *
   * @covers ::removeHtmlComments
   */
  public function testRemoveHtmlComments(): void {
    $expectedDom = new \DOMDocument();
    $expectedDom->appendChild($expectedDom->createElement('div'));
    $expectedDom->appendChild($expectedDom->createElement('div'));
    $expectedDom->appendChild($expectedDom->createElement('span'));

    $testDom = new \DOMDocument();
    $testDom->appendChild($testDom->createElement('div'));
    $testDom->appendChild($testDom->createComment('This is only a test.'));
    $testDom->appendChild($testDom->createElement('div'));
    $element = $testDom->createElement('span');
    $element->appendChild($testDom->createComment('I am a comment.'));
    $testDom->appendChild($element);

    $truncate = new TruncateHTML();
    $reflection_remove_html_comments = new \ReflectionMethod($truncate, 'removeHtmlComments');
    // Remove after support for PHP 8.1 is no longer needed.
    $reflection_remove_html_comments->setAccessible(TRUE);
    $reflection_remove_html_comments->invokeArgs(
      $truncate,
      [&$testDom]
    );
    $this::assertEquals($expectedDom, $testDom, "The HTML comments were not removed.");
  }

}
