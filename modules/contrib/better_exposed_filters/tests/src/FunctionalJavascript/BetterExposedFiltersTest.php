<?php

namespace Drupal\Tests\better_exposed_filters\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\better_exposed_filters\Traits\BetterExposedFiltersTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\views\Views;

/**
 * Tests the basic AJAX functionality of BEF exposed forms.
 *
 * @group better_exposed_filters
 */
class BetterExposedFiltersTest extends WebDriverTestBase {

  use BetterExposedFiltersTrait;
  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'node',
    'views',
    'taxonomy',
    'better_exposed_filters',
    'bef_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable AJAX on the test view.
    \Drupal::configFactory()->getEditable('views.view.bef_test')
      ->set('display.default.display_options.use_ajax', TRUE)
      ->save();

    // Create a few test nodes.
    $this->createNode([
      'title' => 'Page One',
      'field_bef_boolean' => '',
      'field_bef_email' => '1bef-test@drupal.org',
      'field_bef_integer' => '1',
      'field_bef_letters' => 'Aardvark',
      // Seattle.
      'field_bef_location' => '10',
      'type' => 'bef_test',
    ]);
    $this->createNode([
      'title' => 'Page Two',
      'field_bef_boolean' => '',
      'field_bef_email' => '2bef-test2@drupal.org',
      'field_bef_integer' => '2',
      'field_bef_letters' => 'Bumble & the Bee',
      // Vancouver.
      'field_bef_location' => '15',
      'type' => 'bef_test',
    ]);

  }

  /**
   * Tests if filtering via auto-submit works.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testAutoSubmitMinLength(): void {
    $view = Views::getView('bef_test');

    // Enable auto-submit, but disable for text fields.
    $this->setBetterExposedOptions($view, [
      'general' => [
        'autosubmit' => TRUE,
        'autosubmit_exclude_textfield' => FALSE,
        'autosubmit_textfield_minimum_length' => 3,
      ],
    ]);

    // Visit the bef-test page.
    $this->drupalGet('bef-test');

    $session = $this->getSession();
    $page = $session->getPage();

    // Ensure that the content we're testing for is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    // Enter value in email field.
    $field_bef_email = $page->find('css', '.form-item-field-bef-email-value input');
    $field_bef_email->setValue('1');
    // Verify that auto submit didn't run, due to less than 4 characters.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    $field_bef_email = $page->find('css', '.form-item-field-bef-email-value input');
    $field_bef_email->setValue('1bef');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);
  }

  /**
   * Tests if filtering via auto-submit works.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testAutoSubmit(): void {
    $view = Views::getView('bef_test');
    $display = &$view->storage->getDisplay('default');

    // Enable auto-submit, but disable for text fields.
    $this->setBetterExposedOptions($view, [
      'general' => [
        'autosubmit' => TRUE,
        'autosubmit_exclude_textfield' => TRUE,
      ],
    ]);

    // Visit the bef-test page.
    $this->drupalGet('bef-test');

    $session = $this->getSession();
    $page = $session->getPage();

    // Ensure that the content we're testing for is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    // Search for "Page One".
    $field_bef_integer = $page->findField('field_bef_integer_value');
    $field_bef_integer->setValue('1');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that only the "Page One" Node is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);

    // Enter value in email field.
    $field_bef_email = $page->find('css', '.form-item-field-bef-email-value input');
    $field_bef_email->setValue('qwerty@test.com');

    // Verify nothing has changed.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);

    // Submit form.
    $this->submitForm([], 'Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify no results are visible.
    $html = $page->getHtml();
    $this->assertStringNotContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);
  }

  /**
   * Tests if filtering via auto-submit works if exposed form is a block.
   */
  public function testAutoSubmitWithExposedFormBlock() {
    $view = Views::getView('bef_test');
    $display = &$view->storage->getDisplay('default');
    $block = $this->drupalPlaceBlock('views_exposed_filter_block:bef_test-page_2');

    // Enable auto-submit, but disable for text fields.
    $this->setBetterExposedOptions($view, [
      'general' => [
        'autosubmit' => TRUE,
        'autosubmit_exclude_textfield' => TRUE,
      ],
    ]);

    // Visit the bef-test page.
    $this->drupalGet('bef-test-with-block');

    $session = $this->getSession();
    $page = $session->getPage();

    // Ensure that the content we're testing for is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    // Search for "Page One".
    $field_bef_integer = $page->findField('field_bef_integer_value');
    $field_bef_integer->setValue('1');
    $field_bef_integer->blur();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that only the "Page One" Node is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);

    // Enter value in email field.
    $field_bef_email = $page->find('css', '.form-item-field-bef-email-value input');
    $field_bef_email->setValue('qwerty@test.com');

    // Verify nothing has changed.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);

    // Submit form.
    $this->submitForm([], 'Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify no results are visible.
    $html = $page->getHtml();
    $this->assertStringNotContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);
  }

  /**
   * Tests placing exposed filters inside a collapsible field-set.
   */
  public function testSecondaryOptions(): void {
    $view = Views::getView('bef_test');

    // Enable auto-submit, but disable for text fields.
    $this->setBetterExposedOptions($view, [
      'general' => [
        'allow_secondary' => TRUE,
        'secondary_label' => 'Secondary Options TEST',
      ],
      'sort' => [
        'plugin_id' => 'default',
        'advanced' => [
          'is_secondary' => TRUE,
        ],
      ],
      'pager' => [
        'plugin_id' => 'default',
        'advanced' => [
          'is_secondary' => TRUE,
        ],
      ],
      'filter' => [
        'field_bef_boolean_value' => [
          'plugin_id' => 'default',
          'advanced' => [
            'is_secondary' => TRUE,
          ],
        ],
        'field_bef_integer_value' => [
          'plugin_id' => 'default',
          'advanced' => [
            'is_secondary' => TRUE,
            'collapsible' => TRUE,
          ],
        ],
      ],
    ]);

    // Visit the bef-test page.
    $this->drupalGet('bef-test');

    $session = $this->getSession();
    $page = $session->getPage();

    // Assert our fields are initially hidden inside the collapsible field-set.
    $secondary_options = $page->find('css', '.bef--secondary');
    $this->assertFalse($secondary_options->hasAttribute('open'));
    $secondary_options->hasField('field_bef_boolean_value');
    $this->assertTrue($secondary_options->hasField('field_bef_integer_value'), 'Integer field should be present in secondary options');

    // Submit form and set a value for the boolean field.
    $secondary_options->click();
    $this->submitForm(['field_bef_boolean_value' => 1], 'Apply');
    $session = $this->getSession();
    $page = $session->getPage();

    // Verify our field-set is open and our fields visible.
    $secondary_options = $page->find('css', '.bef--secondary');
    $this->assertTrue($secondary_options->hasAttribute('open'));
  }

  /**
   * Tests when filter is marked to be collapsed.
   */
  public function testFilterCollapsible() {
    $view = Views::getView('bef_test');
    $session = $this->getSession();
    $page = $session->getPage();

    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_email_value' => [
          'plugin_id' => 'default',
          'advanced' => [
            'collapsible' => TRUE,
            'collapsible_disable_automatic_open' => TRUE,
          ],
        ],
      ],
    ]);

    // Visit the bef-test page.
    $this->drupalGet('bef-test');

    // Assert the field is closed by default.
    $details_summary = $page->find('css', '#edit-field-bef-email-value-collapsible summary');
    $this->assertTrue($details_summary->hasAttribute('aria-expanded'));
    $this->assertEquals('false', $details_summary->getAttribute('aria-expanded'));

    // Verify field_bef_email is 2nd in the filter.
    $email_details = $page->find('css', '.views-exposed-form .form-item:nth-child(2)');
    $this->assertEquals('edit-field-bef-email-value-collapsible', $email_details->getAttribute('id'));

    // Assert the field is closed by default.
    $details_summary = $page->find('css', '#edit-field-bef-email-value-collapsible summary');
    $this->assertTrue($details_summary->hasAttribute('aria-expanded'));
    $this->assertEquals('false', $details_summary->getAttribute('aria-expanded'));
  }

  /**
   * Tests when remember last selection is used.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testRememberLastSelection(): void {
    $this->drupalLogin($this->createUser());
    $this->drupalGet('bef-test');
    $this->getSession()->getPage()->fillField('field_bef_email_value', 'bef-test2@drupal.org');
    $this->getSession()->getPage()->pressButton('Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueEquals('field_bef_email_value', 'bef-test2@drupal.org');

    // Now go back and verify email was remembered.
    $this->drupalGet('bef-test');
    $this->assertSession()->fieldValueEquals('field_bef_email_value', 'bef-test2@drupal.org');

    // Click Reset button.
    $this->getSession()->getPage()->pressButton('Reset');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify field cleared.
    $this->assertSession()->fieldValueEquals('field_bef_email_value', '');
  }

}
