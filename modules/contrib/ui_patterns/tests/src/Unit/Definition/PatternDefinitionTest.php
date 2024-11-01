<?php

namespace Drupal\Tests\ui_patterns\Unit\Definition;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\Tests\ui_patterns\Unit\UiPatternsTestBase;
use Drupal\ui_patterns\Definition\PatternDefinition;

/**
 * @coversDefaultClass \Drupal\ui_patterns\Definition\PatternDefinition
 *
 * @group ui_patterns
 */
class PatternDefinitionTest extends UiPatternsTestBase {

  /**
   * Test getters.
   *
   * @dataProvider definitionGettersProvider
   *
   * @covers ::getCategory
   * @covers ::getDescription
   * @covers ::getLabel
   * @covers ::getProvider
   * @covers ::getUse
   * @covers ::getWeight
   * @covers ::getTags
   * @covers ::getBasePath
   * @covers ::getClass
   * @covers ::getFileName
   * @covers ::getTemplate
   * @covers ::getThemeHook
   * @covers ::hasCustomThemeHook
   * @covers ::id
   */
  public function testGettersSetters($getter, $name, $value) {
    $pattern_definition = new PatternDefinition([$name => $value]);
    $this->assertEquals($value, call_user_func([$pattern_definition, $getter]));
  }

  /**
   * Provider.
   *
   * @return array
   *   Data.
   */
  public static function definitionGettersProvider() {
    return [
      ['getProvider', 'provider', 'my_module'],
      ['id', 'id', 'pattern_id'],
      ['getLabel', 'label', 'Pattern label'],
      ['getDescription', 'description', 'Pattern description.'],
      ['getCategory', 'category', 'Pattern category'],
      ['getUse', 'use', 'template.twig'],
      ['hasCustomThemeHook', 'custom theme hook', FALSE],
      ['getThemeHook', 'theme hook', 'eme hook: custom_my_theme_hook'],
      ['getTemplate', 'template', 'my-template.html.twig'],
      ['getFileName', 'file name', '/path/to/filename.ui_patterns.yml'],
      ['getClass', 'class', '\Drupal\ui_patterns\MyClass'],
      ['getBasePath', 'base path', '/path/to'],
      ['getTags', 'tags', ['a', 'b']],
      ['getWeight', 'weight', 10],
    ];
  }

  /**
   * Test field singleton.
   *
   * @covers ::getField
   * @covers ::setFields
   */
  public function testFields() {
    $fields = [
      'name' => [
        'name' => 'name',
        'label' => 'Label',
      ],
    ];
    $pattern_definition = new PatternDefinition();
    $pattern_definition->setFields($fields);
    $this->assertEquals(
      [
        $fields['name']['label'],
        $fields['name']['name'],
        NULL,
        NULL,
        NULL,
      ],
      [
        $pattern_definition->getField('name')->getLabel(),
        $pattern_definition->getField('name')->getName(),
        $pattern_definition->getField('name')->getType(),
        $pattern_definition->getField('name')->getDescription(),
        $pattern_definition->getField('name')->getPreview(),
      ]);

    $pattern_definition->getField('name')->setType('type');
    $pattern_definition->getField('name')->setPreview('preview');
    $pattern_definition->getField('name')->setDescription('description');

    $this->assertEquals(
      [
        'type',
        'description',
        'preview',
      ],
      [
        $pattern_definition->getField('name')->getType(),
        $pattern_definition->getField('name')->getDescription(),
        $pattern_definition->getField('name')->getPreview(),
      ]);
  }

  /**
   * Test fields processing.
   *
   * @dataProvider fieldsProcessingProvider
   *
   * @covers ::setFields
   */
  public function testFieldsProcessing($actual, $expected) {
    $pattern_definition = new PatternDefinition();
    $data = $pattern_definition->setFields($actual)->toArray();
    $this->assertEquals($expected, $data['fields']);
  }

  /**
   * Provider.
   *
   * @return array
   *   Data.
   */
  public function fieldsProcessingProvider() {
    return Yaml::decode(file_get_contents($this->getFixturePath() . '/definition/fields_processing.yml'));
  }

  /**
   * Test fields processing.
   *
   * @dataProvider variantsProcessingProvider
   *
   * @covers ::setVariants
   */
  public function testVariantsProcessing($actual, $expected) {
    $pattern_definition = new PatternDefinition();
    $data = $pattern_definition->setVariants($actual)->toArray();
    $this->assertEquals($expected, $data['variants']);
  }

  /**
   * Provider.
   *
   * @return array
   *   Data.
   */
  public function variantsProcessingProvider() {
    return Yaml::decode(file_get_contents($this->getFixturePath() . '/definition/variants_processing.yml'));
  }

  /**
   * Test hasUse method.
   *
   * @dataProvider hasUseProvider
   *
   * @covers ::hasUse
   */
  public function testHasUse(array $pattern, bool $expected): void {
    $patternDefinition = new PatternDefinition($pattern);
    $this->assertEquals($expected, $patternDefinition->hasUse());
  }

  /**
   * Provider.
   *
   * @return array
   *   Data.
   */
  public static function hasUseProvider(): array {
    return [
      'pattern_without_use' => [
        [
          'id' => 'test',
        ],
        FALSE,
      ],
      'pattern_with_use' => [
        [
          'id' => 'test',
          'use' => 'my use',
        ],
        TRUE,
      ],
      'pattern_with_use_with_variant' => [
        [
          'id' => 'test',
          'use' => 'my use',
          'variants' => [
            'default' => [
              'label' => 'Default',
            ],
          ],
        ],
        TRUE,
      ],
      'pattern_with_variant' => [
        [
          'id' => 'test',
          'variants' => [
            'default' => [
              'label' => 'Default',
            ],
          ],
        ],
        FALSE,
      ],
      'pattern_with_variant_with_use' => [
        [
          'id' => 'test',
          'variants' => [
            'default' => [
              'label' => 'Default',
              'use' => 'my use',
            ],
          ],
        ],
        TRUE,
      ],
      'pattern_with_use_with_variant_with_use' => [
        [
          'id' => 'test',
          'use' => 'my use',
          'variants' => [
            'default' => [
              'label' => 'Default',
              'use' => 'my use',
            ],
          ],
        ],
        TRUE,
      ],
    ];
  }

  /**
   * Test getLinks.
   *
   * @param array $links
   *   The links like in the YAML declaration.
   * @param array $expected
   *   The expected result.
   *
   * @covers ::getLinks
   *
   * @dataProvider definitionGetLinksProvider
   */
  public function testGetLinks(array $links, array $expected): void {
    $definition = new PatternDefinition([
      'links' => $links,
    ]);
    $this->assertEquals($expected, $definition->getLinks());
  }

  /**
   * Provider.
   *
   * @return array
   *   Data.
   */
  public static function definitionGetLinksProvider(): array {
    return [
      [
        [
          'https://test.com',
          [
            'url' => 'https://example.com',
            'title' => 'Example',
            'options' => [
              'attributes' => [
                'query' => [
                  'test_param' => 'test_value',
                ],
                'target' => '_blank',
              ],
            ],
          ],
        ],
        [
          [
            'url' => 'https://test.com',
            'title' => 'External documentation',
          ],
          [
            'url' => 'https://example.com',
            'title' => 'Example',
            'options' => [
              'attributes' => [
                'query' => [
                  'test_param' => 'test_value',
                ],
                'target' => '_blank',
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Test getRenderLinks.
   *
   * @param array $links
   *   The links like in the YAML declaration.
   * @param array $expected
   *   The expected result.
   *
   * @covers ::getRenderLinks
   *
   * @dataProvider definitionGetRenderLinksProvider
   */
  public function testGetRenderLinks(array $links, array $expected): void {
    $definition = new PatternDefinition([
      'links' => $links,
    ]);
    $this->assertEquals($expected, $definition->getRenderLinks());
  }

  /**
   * Provider.
   *
   * @return array
   *   Data.
   */
  public static function definitionGetRenderLinksProvider(): array {
    return [
      [
        [
          'https://test.com',
          [
            'url' => 'https://example.com',
            'title' => 'Example',
            'options' => [
              'attributes' => [
                'query' => [
                  'test_param' => 'test_value',
                ],
                'target' => '_blank',
              ],
            ],
          ],
        ],
        [
          [
            '#type' => 'link',
            '#url' => Url::fromUri('https://test.com'),
            '#title' => 'External documentation',
          ],
          [
            '#type' => 'link',
            '#url' => Url::fromUri('https://example.com'),
            '#title' => 'Example',
            '#options' => [
              'attributes' => [
                'query' => [
                  'test_param' => 'test_value',
                ],
                'target' => '_blank',
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
