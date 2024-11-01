<?php

namespace Drupal\Tests\moderation_dashboard\Functional;

use Drupal\Core\Session\AccountInterface;

/**
 * Tests personalized moderation dashboard components.
 *
 * @group moderation_dashboard
 */
class ModerationDashboardPersonalizedComponentsTest extends ModerationDashboardTestBase {

  /**
   * Moderator user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $moderatorUser;

  /**
   * Regular user without moderation dashboard permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $regularUser;

  /**
   * Personalized elements to test.
   *
   * @var array
   *   An array of text asserts keyed by the CSS selector of the element to
   *   assert.
   *   - contains: array of strings patters which have to be found. '%s' will be
   *     replaced by the display name of the user in context.
   *   - not_contains: array of strings patterns shouldn't be found. '%s' will
   *     be replaced by the display name of the user in context.
   *   - empty: the expected empty message of the component (string).
   */
  protected array $personalizedModerationElements = [
    // Your drafts.
    '.view-id-content_moderation_dashboard_in_review.view-display-id-block_3' => [
      'contains' => ['Draft node of %s'],
      'not_contains' => ['Published node of %s'],
      'empty' => 'No draft content was found.',
    ],
    // Your activity.
    '.view-id-moderation_dashboard_recent_changes.view-display-id-block_2' => [
      'contains' => ['Published node of %s', 'Draft node of %s'],
      'empty' => 'Activity log is empty.',
    ],
    // Content you created.
    '.view-id-moderation_dashboard_recently_created.view-display-id-block_2' => [
      'contains' => ['Published node of %s', 'Draft node of %s'],
      'empty' => 'No content was found.',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moderatorUser = $this->createUser($this->userPermissions, 'moderator user');
    $this->regularUser = $this->createUser([
      'access content',
      'use moderation dashboard',
    ], 'regular user');

    foreach ([$this->user, $this->moderatorUser, $this->regularUser] as $user) {
      $this->drupalCreateNode([
        'title' => 'Draft node of ' . $user->getDisplayName(),
        'moderation_state' => 'draft',
        'uid' => $user->id(),
      ]);

      $this->drupalCreateNode([
        'title' => 'Published node of ' . $user->getDisplayName(),
        'moderation_state' => 'published',
        'uid' => $user->id(),
      ]);
    }

    $this->drupalLogin($this->user);
  }

  /**
   * Tests that blocks and other elements exist on the user dashboard.
   */
  public function testModerationElement(): void {
    $users = [$this->user, $this->moderatorUser, $this->regularUser];

    foreach ($users as $delta => $user) {
      $this->drupalGet('/user/' . $user->id() . '/moderation-dashboard');

      // Iterating over personalized elements.
      foreach ($this->personalizedModerationElements as $selector => $asserts) {
        $moderation_element = $this->assertSession()->elementExists('css', $selector);
        $moderation_element_text = $moderation_element->getText();

        if (!empty($asserts['empty'])) {
          $this->assertFalse(strpos($moderation_element_text, $asserts['empty']));
        }

        if (!empty($asserts['contains'])) {
          foreach ($asserts['contains'] as $pattern_to_find) {
            $this->assertNotSame(FALSE, strpos($moderation_element_text, sprintf($pattern_to_find, $user->getDisplayName())));
          }
        }

        if (!empty($asserts['not_contains'])) {
          foreach ($asserts['not_contains'] as $pattern_should_not_find) {
            $this->assertFalse(strpos($moderation_element_text, sprintf($pattern_should_not_find, $user->getDisplayName())));
          }
        }

        $other_users = $users;
        unset($other_users[$delta]);

        foreach ($other_users as $other_user) {
          $this->assertFalse(strpos($moderation_element->getText(), sprintf('%s', $other_user->getDisplayName())));
        }
      }
    }

    $user_without_content = $this->createUser([
      'access content',
      'use moderation dashboard',
    ]);

    // Check user's dashboard who does not own node revisions at all.
    $this->drupalGet('/user/' . $user_without_content->id() . '/moderation-dashboard');
    // Verify that every personalized component shows the expected empty
    // message.
    foreach ($this->personalizedModerationElements as $selector => $asserts) {
      $moderation_element = $this->assertSession()->elementExists('css', $selector);

      if (!empty($asserts['empty'])) {
        $this->assertNotSame(FALSE, strpos($moderation_element->getText(), $asserts['empty']));
      }
    }
  }

}
