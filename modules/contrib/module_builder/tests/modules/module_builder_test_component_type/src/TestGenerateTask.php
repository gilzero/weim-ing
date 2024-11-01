<?php

namespace Drupal\module_builder_test_component_type;

use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
// For ease of manual testing, so that definitions from MTD can be used here
// without changing the definition class.
use DrupalCodeBuilder\Definition\PropertyDefinition as DataDefinition;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use DrupalCodeBuilder\Task\Generate;
use MutableTypedData\Definition\DataDefinition as BasePropertyDefinition;
use MutableTypedData\Definition\DefinitionProviderInterface;

/**
 * Mock Generate task for functional tests.
 *
 * We can't make this as a mock in TestDrupalCodeBuilder because some of the
 * methods we need to stub have reference arguments, which Prophecy doesn't
 * support, and it's too much of a PITA to create PHPUnit mock objects outside
 * of a test class.
 */
class TestGenerateTask extends Generate implements DefinitionProviderInterface {

  public function getRootComponentData($component_type = 'module') {
    $data = DrupalCodeBuilderDataItemFactory::createFromProvider(static::class);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefinition(): BasePropertyDefinition {
    $definition = GeneratorDefinition::createFromGeneratorType('module', 'complex');

    // The component form expects these to always exist.
    $definition->addProperty(
      PropertyDefinition::create('string')
        ->setName('root_name')
        ->setLabel('Extension machine name')
    );
    $definition->addProperty(
      PropertyDefinition::create('string')
        ->setName('readable_name')
        ->setLabel('Extension readable name')
    );

    // These need to be defined for the 'Name' form to work.
    $definition->addProperties([
      'short_description' => PropertyDefinition::create('string')
        ->setLabel('Description'),
      'module_package' => PropertyDefinition::create('string')
        ->setLabel('Package'),
      'module_dependencies' => PropertyDefinition::create('string')
        ->setLabel('Dependencies')
        ->setMultiple(TRUE),
    ]);

    // These will show on the 'Misc' form because the entity annotation
    // doesn't declare them.
    $test_properties = [
      'string_empty' => PropertyDefinition::create('string'),
      'string_default' => PropertyDefinition::create('string')
        ->setLiteralDefault('default value'),
      'checkbox_empty' => PropertyDefinition::create('boolean'),
      'checkbox_default' => PropertyDefinition::create('boolean')
        ->setLiteralDefault(TRUE),
      'radios_empty' => PropertyDefinition::create('string')
        ->setOptionsArray([
          'alpha' => 'alpha',
          'beta' => 'beta',
          'gamma' => 'gamma',
        ]),
      'radios_default' => PropertyDefinition::create('string')
        ->setOptionsArray([
          'alpha' => 'alpha',
          'beta' => 'beta',
          'gamma' => 'gamma',
        ])
        ->setLiteralDefault('beta'),
      'array_empty' => PropertyDefinition::create('string')
        ->setMultiple(TRUE),
      'array_default' => PropertyDefinition::create('string')
        ->setMultiple(TRUE)
        ->setLiteralDefault([
          'value 1',
          'value 2',
        ]),
      'compound_empty' => PropertyDefinition::create('complex')
        ->setProperties([
          'one' => PropertyDefinition::create('string')
            ->setLabel('one')
            ->setRequired(TRUE),
          'two' => PropertyDefinition::create('string')
            ->setLabel('two'),
        ]),
      ];

    foreach ($test_properties as $key => $property) {
      $property->setLabel($key);
    }

    $definition->addProperties($test_properties);

    $definition->setName('module');


    return $definition;
  }

}
