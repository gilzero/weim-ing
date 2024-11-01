<?php

declare(strict_types=1);

namespace Drupal\Tests\smart_trim\Kernel;

use Composer\Semver\Comparator;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Render\Markup;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\token\Kernel\TokenKernelTestBase;

/**
 * Test the smart trim tokens.
 *
 * @group smart_trim
 */
final class TokenTest extends TokenKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field', 'filter', 'text', 'smart_trim'];

  /**
   * Filter format.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  private FilterFormatInterface $testFormat;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // @todo Remove once 10.2 is no longer supported.
    if (Comparator::lessThanOrEqualTo(\Drupal::VERSION, '10.2')) {
      $this->markTestSkipped('The test setup on pre 10.2 fails.');
    }
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['filter', 'node', 'user']);

    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'description' => "Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.",
    ]);
    $node_type->save();
    node_add_body_field($node_type);

    $this->testFormat = FilterFormat::create([
      'format' => 'test',
      'name' => 'Test format',
      'weight' => 1,
      'filters' => [
        'filter_html_escape' => ['status' => TRUE],
      ],
    ]);
    $this->testFormat->save();
  }

  /**
   * Test summary field tokens.
   */
  public function testSummaryFieldTokens() {
    $value = 'A really long string that should be trimmed by the special formatter on token view we are going to have.';

    // The formatter we are going to use will eventually call Unicode::strlen.
    // This expects that the Unicode has already been explicitly checked, which
    // happens in DrupalKernel. But since that doesn't run in kernel tests, we
    // explicitly call this here.
    Unicode::check();

    // Create a node with a value in the text field and test its token.
    /** @var \Drupal\node\Entity\Node $entity */
    $entity = Node::create([
      'title' => 'Test node title',
      'type' => 'article',
      'body' => [
        'value' => $value,
        'format' => $this->testFormat->id(),
      ],
    ]);
    $entity->save();

    // Now, create a token view mode which sets a format for the body. When
    // replacing tokens, this formatter should be picked over the default
    // formatter for the field type.
    // @see field_tokens().
    $view_mode = EntityViewMode::create([
      'id' => 'node.token',
      'targetEntityType' => 'node',
    ]);
    $view_mode->save();
    $entity_display = \Drupal::service('entity_display.repository')->getViewDisplay('node', 'article', 'token');
    $entity_display->setComponent('body', [
      'type' => 'smart_trim',
      'label' => 'hidden',
      'settings' => [
        'trim_length' => 10,
        'trim_type' => 'words',
        'trim_options' => [
          'text' => TRUE,
        ],
        'summary_handler' => 'trim',
      ],
    ]);
    $entity_display->save();

    $format = "\n              %s\n\n\n      ";
    $token = sprintf($format, 'A really long string that should be trimmed by the');
    $this->assertToken('node', ['node' => $entity], 'body-smart-trim', Markup::create($token));

    // @phpstan-ignore-next-line
    $entity->get('body')->summary = 'A summarized prefix: ' . $value;
    $entity->save();
    $token = sprintf($format, 'A summarized prefix: A really long string that should be');
    $this->assertToken('node', ['node' => $entity], 'body-smart-trim', Markup::create($token));
  }

}
