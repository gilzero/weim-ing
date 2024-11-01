<?php

namespace Drupal\Tests\manage_display\Functional;

use Drupal\comment\CommentInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\comment\Functional\CommentTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests comment display is configurable.
 *
 * @group field
 */
class CommentManageDisplayTest extends CommentTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'manage_display',
    'comment',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    BrowserTestBase::setUp();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->addDefaultCommentField('node', 'article');
    $this->setCommentAnonymous(CommentInterface::ANONYMOUS_MAY_CONTACT);

    // Enable anonymous user comments.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, [
      'access comments',
      'post comments',
      'skip comment approval',
    ]);
  }

  /**
   * Test basic comment display.
   */
  public function testComment() {
    // Create user, node, anonymous comment.
    $user = $this->drupalCreateUser();
    $node = $this->drupalCreateNode(['uid' => $user->id(), 'type' => 'article']);

    $author = $this->randomMachineName();
    $subject = $this->randomMachineName();
    $contact = ['name' => $author, 'mail' => "$author@example.com"];
    $comment = $this->postComment($node, $this->randomMachineName(), $subject, $contact);

    // Check node display.
    $assert = $this->assertSession();
    $permalink = $comment->permalink()->toString();
    $assert->elementTextContains('css', 'article.comment footer p span.field--name-uid', "$author (not verified)");
    $assert->elementTextContains('css', 'article.comment div.field--name-subject h3 a.permalink[href="' . $permalink . '"]', $subject);

    // Add a reply.
    $this->drupalGet('comment/reply/node/' . $node->id() . '/comment/' . $comment->id());
    $this->postComment(NULL, $this->randomMachineName(), $this->randomMachineName());
    $assert->elementTextContains('css', 'article.comment footer p.visually-hidden span.field--name-pid span', "$author (not verified)");
    $assert->elementTextContains('css', 'article.comment footer p.visually-hidden span.field--name-pid a.permalink[href="' . $permalink . '"]', $subject);
  }

}
