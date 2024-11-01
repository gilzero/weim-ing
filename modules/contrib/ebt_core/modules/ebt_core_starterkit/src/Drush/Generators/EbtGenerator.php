<?php declare(strict_types = 1);

namespace Drupal\ebt_core_starterkit\Drush\Generators;

use DrupalCodeGenerator\Asset\AssetCollection as Assets;
use DrupalCodeGenerator\Attribute\Generator;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\GeneratorType;

#[Generator(
  name: 'ebt:module',
  description: 'Generates Extra Block Type (EBT) module',
  aliases: ['ebt-module'],
  templatePath: __DIR__ . '/../../../generator',
  type: GeneratorType::MODULE_COMPONENT,
  label: 'EBT module',
)]
final class EbtGenerator extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  protected function generate(array &$vars, Assets $assets): void {
    $ir = $this->createInterviewer($vars);

    $vars['machine_name'] = $ir->askMachineName();
    // @todo add validation for ebt_* prefix.
    $vars['name'] = $ir->askName();
    $vars['machine_name_with_dashes'] = str_replace('_', '-', $vars['machine_name']);

    $assets->addFile('{machine_name}.info.yml', 'ebt_starterkit.info.yml.twig');
    $assets->addFile('{machine_name}.libraries.yml', 'ebt_starterkit.libraries.yml.twig');

    $assets->addFile('config/install/block_content.type.{machine_name}.yml', 'config/install/block_content.type.ebt_starterkit.yml.twig');
    $assets->addFile('config/install/core.entity_form_display.block_content.{machine_name}.default.yml', 'config/install/core.entity_form_display.block_content.ebt_starterkit.default.yml.twig');
    $assets->addFile('config/install/core.entity_view_display.block_content.{machine_name}.default.yml', 'config/install/core.entity_view_display.block_content.ebt_starterkit.default.yml.twig');
    $assets->addFile('config/install/field.field.block_content.{machine_name}.body.yml', 'config/install/field.field.block_content.ebt_starterkit.body.yml.twig');
    $assets->addFile('config/install/field.field.block_content.{machine_name}.field_ebt_settings.yml', 'config/install/field.field.block_content.ebt_starterkit.field_ebt_settings.yml.twig');

    $assets->addFile('templates/block--block-content--{machine_name_with_dashes}.html.twig', 'twig_files/block--block-content--ebt-starterkit.html.twig.twig');
    $assets->addFile('templates/block--inline-block--{machine_name_with_dashes}.html.twig', 'twig_files/block--inline-block--ebt-starterkit.html.twig.twig');

    $assets->addFile('tests/src/Functional/InstallTest.php', 'tests/src/Functional/InstallTest.php.twig');
    $assets->addFile('logo.png');
    $assets->addFile('logo.svg');

    $assets->addFile('.gitignore', '.gitignore.twig');

    $assets->addFile('README.md', 'README.md.twig');
    $assets->addFile('composer.json', 'composer.json.twig');

    $assets->addFile('gulpfile.js', 'gulpfile.js.twig');
    $assets->addFile('css/styles.css', 'css/styles.css.twig');
    $assets->addFile('scss/styles.scss', 'scss/styles.scss.twig');
    $assets->addFile('package.json', 'package.json.twig');
    $assets->addFile('package-lock.json', 'package-lock.json.twig');
  }

}
