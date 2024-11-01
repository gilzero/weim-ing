<?php

namespace Drupal\Tests\module_builder\Unit;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\module_builder\EntityHandler\ComponentSectionFormHandler;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ComponentSectionFormHandler entity handler.
 *
 * @group module_builder
 */
class ComponentSectionFormHandlerTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'module_builder',
  ];

  /**
   * Tests the ComponentSectionFormHandler entity handler.
   */
  public function testComponentSectionFormHandler() {
    $entity_code_builder_annotation_data = [
      "section_forms" => [
        "name" => [
          "title" => "Edit basic properties",
          "tab_title" => "Name",
          "properties" => [
            "name_1",
            "name_2",
            "name_3",
          ],
        ],
        "hooks" => [
          "title" => "Edit hooks",
          "tab_title" => "Hooks",
          "properties" => [
            "hooks_1",
          ],
        ],
      ],
    ];

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->id()->willReturn('test');
    $entity_type->get('code_builder')->willReturn($entity_code_builder_annotation_data);

    // Create the handler, with mock passed in.
    $component_sections_handler = new ComponentSectionFormHandler(
      $entity_type->reveal()
    );

    $form_operations_expected = [
      'hooks',
      'misc',
      'generate',
      'adopt',
    ];
    $this->assertEquals($form_operations_expected, $component_sections_handler->getFormOperations());

    $form_tab_routes_expected = [
      'hooks' => "Edit hooks",
      'misc' => "Edit %label miscellaneous components",
      'generate' => 'Generate code for %label',
    ];
    $this->assertEquals($form_tab_routes_expected, $component_sections_handler->getFormTabRoutePaths());

    $form_tab_tasks_expected = [
      'hooks' => "Hooks",
      'misc' => "Misc",
      'generate' => 'Generate code',
    ];
    $this->assertEquals($form_tab_tasks_expected, $component_sections_handler->getFormTabLocalTasksData());

    $this->assertEquals([
      "name_1",
      "name_2",
      "name_3",
    ], $component_sections_handler->getSectionFormComponentProperties('name'));
    $this->assertEquals([
      "hooks_1",
    ], $component_sections_handler->getSectionFormComponentProperties('hooks'));

    $this->assertEquals([
      "name_1",
      "name_2",
      "name_3",
      "hooks_1",
    ], $component_sections_handler->getUsedComponentProperties());
  }

}
