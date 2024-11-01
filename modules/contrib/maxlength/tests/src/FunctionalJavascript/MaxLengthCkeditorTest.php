<?php

namespace Drupal\Tests\maxlength\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\editor\Entity\Editor;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Assert;

/**
 * Tests Javascript behavior of Maxlength module with CKEditor.
 *
 * @group maxlength
 */
class MaxLengthCkeditorTest extends WebDriverTestBase {

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'maxlength',
    'text',
    'ckeditor5',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => [],
    ])->save();
    FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'weight' => 2,
      'filters' => [],
    ])->save();

    $this->user = $this->drupalCreateUser([
      'access administration pages',
      'administer entity_test content',
      'administer site configuration',
      'administer filters',
      'use text format full_html',
      'use text format basic_html',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests the character count and limit works with CKEditor 5 version.
   */
  public function testCkeditor5() {
    Editor::create([
      'format' => 'full_html',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => [
            'heading',
            'bold',
            'italic',
            // Ensure we enable the source button for the test.
            'sourceEditing',
          ],
        ],
      ],
    ])->save();
    Editor::create([
      'format' => 'basic_html',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => [
            'heading',
            'bold',
            'italic',
            // Ensure we enable the source button for the test.
            'sourceEditing',
          ],
        ],
      ],
    ])->save();
    FieldStorageConfig::create([
      'type' => 'text_long',
      'entity_type' => 'entity_test',
      'field_name' => 'foo',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'foo',
      'label' => 'Foo',
      'description' => 'Description of a text field',
    ])->save();
    $widget = [
      'type' => 'text_textarea_with_summary',
      'settings' => [
        'show_summary' => TRUE,
        'summary_rows' => 3,
      ],
      'third_party_settings' => [
        'maxlength' => [
          'maxlength_js' => 200,
          'maxlength_js_label' => 'Content limited to @limit characters, remaining: <strong>@remaining</strong> and total @count',
        ],
      ],
    ];
    EntityFormDisplay::load('entity_test.entity_test.default')
      ->setComponent('foo', $widget)
      ->save();

    $entity = EntityTest::create(['type' => 'entity_test', 'name' => 'Test']);
    $entity->save();

    $page = $this->getSession()->getPage();
    $this->drupalLogin($this->user);
    $this->drupalGet($entity->toUrl('edit-form'));

    // Assert CKEditor5 is present.
    $settings = $this->getDrupalSettings();
    $this->assertContains('ckeditor5/internal.drupal.ckeditor5.emphasis', explode(',', $settings['ajaxPageState']['libraries']), 'CKEditor5 glue library is present.');

    // Assert the maxlength counter labels.
    $this->assertSession()->pageTextContainsOnce('Content limited to 200 characters, remaining: 200 and total 0');

    // Give maxlength.js some time to manipulate the DOM.
    $this->assertSession()->waitForElement('css', 'div.counter');

    // Check that only a counter div is found on the page.
    $this->assertSession()->elementsCount('css', 'div.counter', 1);

    // Check that the counter div follows the description of the field.
    $found = $this->xpath('//div[@data-drupal-selector="edit-foo-0"]/following-sibling::div[@id="edit-foo-0-value-counter"]');
    $this->assertCount(1, $found);

    // Add some text to the field and assert the maxlength counters changed
    // accordingly.
    $this->enterTextInCkeditor5('Foo', 'Some text with <strong>html</strong>');

    $this->assertSession()->pageTextContainsOnce('Content limited to 200 characters, remaining: 181 and total 19');

    // Fill the body field with more characters than the limit.
    $this->enterTextInCkeditor5('Foo', '<b>Lorem ipsum</b> dolor sit amet, <u>consectetur adipiscing</u> elit. Ut accumsan justo non interdum fermentum. Phasellus semper risus eu arcu eleifend dignissim. Class aptent taciti sociosqu ad litora erat curae. Extra characters');
    // The counter now should show "-17" for the extra characters.
    $this->assertSession()->pageTextContainsOnce('Content limited to 200 characters, remaining: -17 and total 217');

    // Change the text format.
    $page->selectFieldOption('foo[0][format]', 'basic_html');
    $this->assertNotEmpty($this->assertSession()->waitForText('Change text format?'));
    $page->pressButton('Continue');
    $this->getSession()->wait(1000);

    // Add some text to the field and assert the maxlength counters changed
    // accordingly.
    $this->enterTextInCkeditor5('Foo', 'Some text with <strong>html</strong>');

    $this->assertSession()->pageTextContainsOnce('Content limited to 200 characters, remaining: 181 and total 19');

    // Fill the body field with more characters than the limit.
    $this->enterTextInCkeditor5('Foo', '<b>Lorem ipsum</b> dolor sit amet, <u>consectetur adipiscing</u> elit. Ut accumsan justo non interdum fermentum. Phasellus semper risus eu arcu eleifend dignissim. Class aptent taciti sociosqu ad litora erat curae. Extra characters');
    // The counter now should show "-17" for the extra characters.
    $this->assertSession()->pageTextContainsOnce('Content limited to 200 characters, remaining: -17 and total 217');

    // Now change the maxlength configuration to use "Hard limit".
    $widget['third_party_settings']['maxlength']['maxlength_js_enforce'] = TRUE;
    $display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('entity_test.entity_test.default');
    $display->setComponent('foo', $widget)->save();

    // Reload the page.
    $this->getSession()->reload();
    // Fill the body field with more characters than the limit.
    $this->enterTextInCkeditor5('Foo', '<b>Lorem ipsum</b> dolor sit amet, <br><u>consectetur adipiscing</u> elit. <img src=""><embed type="video/webm" src="">Ut accumsan justo non interdum fermentum. Phasellus semper risus eu arcu eleifend dignissim. Class aptent taciti sociosqu ad litora erat curae. Extra characterss');
    // Assert the "Extra characters" string is truncated.
    $this->assertSession()->pageTextContainsOnce('Content limited to 200 characters, remaining: 0 and total 200');
  }

  /**
   * Enters the given text in the textarea of the specified CKEditor 5.
   *
   * If there is any text existing it will be replaced.
   *
   * @param string $field
   *   The label of the field to which the CKEditor is attached. For example
   *   'Body'.
   * @param string $text
   *   The text to enter in the textarea.
   */
  protected function setCkeditor5Text(string $field, string $text): void {
    $wysiwyg = $this->getCkeditor5($field);
    $ckeditor5_id = $this->getCkeditor5Id($wysiwyg);
    $javascript = <<<JS
(function(){
  Drupal.CKEditor5Instances.get('$ckeditor5_id').setData(`$text`);
  // Add temporary mechanism to update the source element.
  // @see https://www.drupal.org/i/2722319
  const editor = Drupal.CKEditor5Instances.get('$ckeditor5_id');
  if (editor) {
    jQuery(once('ckeditor5-states-binding', editor.sourceElement)).each(
      function () {
        editor.model.document.on('change', function () {
          if (editor.getData() !== editor.sourceElement.textContent) {
            editor.updateSourceElement();
            jQuery(editor.sourceElement).trigger('change', [true]);
          }
        });
      },
    );
  }
})();
JS;
    $this->getSession()->evaluateScript($javascript);
    $wysiwyg->click();
  }

  /**
   * Returns the CKEditor 5 that is associated with the given field label.
   *
   * @param string $field
   *   The label of the field to which the CKEditor is attached.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The WYSIWYG editor.
   */
  protected function getCkeditor5(string $field): NodeElement {
    $driver = $this->getSession()->getDriver();
    $label_elements = $driver->find('//label[text()="' . $field . '"]');
    Assert::assertNotEmpty($label_elements, "Could not find the '$field' field label.");
    Assert::assertCount(1, $label_elements, "Multiple '$field' labels found in the page.");

    $wysiwyg_elements = $driver->find('//label[contains(text(), "' . $field . '")]/following::div[contains(@class, " ck-editor ")][1]');
    Assert::assertNotEmpty($wysiwyg_elements, "Could not find the '$field' wysiwyg editor.");
    Assert::assertCount(1, $wysiwyg_elements, "Multiple '$field' wysiwyg editors found in the page.");

    return reset($wysiwyg_elements);
  }

  /**
   * Enters the given text in the given CKEditor 5.
   *
   * @param string $label
   *   The label of the field containing the CKEditor.
   * @param string $text
   *   The text to enter in the CKEditor.
   */
  protected function enterTextInCkeditor5(string $label, string $text): void {
    $this->setCkeditor5Text($label, $text);
  }

  /**
   * Gets the "data-ckeditor5-id" attribute value.
   *
   * @param \Behat\Mink\Element\NodeElement $wysiwyg
   *   The WYSIWYG element.
   *
   * @return string|int
   *   Returns the "data-ckeditor5-id" attribute value.
   */
  protected function getCkeditor5Id(NodeElement $wysiwyg): string|int {
    $textarea = $this->getSession()->getDriver()->find($wysiwyg->getXpath() . '/preceding-sibling::textarea');
    Assert::assertNotEmpty($textarea, "Could not find the textarea element.");

    $textarea = reset($textarea);
    $ckeditor_id = $textarea->getAttribute('data-ckeditor5-id');
    Assert::assertNotEmpty($ckeditor_id, "Could not find the textarea element's ckeditor5 id.");

    return $ckeditor_id;
  }

}
