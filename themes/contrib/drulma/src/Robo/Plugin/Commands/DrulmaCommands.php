<?php

namespace Drulma\Robo\Plugin\Commands;

use Robo\Tasks;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class DrulmaCommands extends Tasks {

  /**
   * Retrieves info from the Bulma repository to generate the schema.
   *
   * @command lp:generate-schema
   */
  public function librariesProviderGenerateOptionsSchema($version = 'master') {
    $urls = [
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/utilities/initial-variables.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/utilities/derived-variables.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/utilities/controls.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/base/generic.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/layout/footer.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/layout/section.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/grid/columns.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/elements/box.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/elements/button.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/elements/content.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/elements/form.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/elements/icon.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/elements/image.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/elements/notification.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/elements/progress.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/elements/table.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/elements/tag.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/elements/title.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/components/breadcrumb.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/components/card.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/components/dropdown.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/components/menu.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/components/message.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/components/modal.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/components/navbar.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/components/pagination.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/components/panel.json',
      'https://raw.githubusercontent.com/jgthms/bulma/' . $version . '/docs/_data/variables/components/tabs.json',
    ];
    $schema = new \stdClass();
    foreach ($urls as $url) {
      $variables = json_decode(file_get_contents($url));
      if (!isset($variables->by_name)) {
        $this->say("The following URL can't be parsed, $url");
      }
      else {
        foreach ($variables->by_name as $optionName => $option) {
          $schema->$optionName = $option;
        }
      }
    }

    if ($version === 'master') {
      $version = 'default';
    }

    file_put_contents(
        __DIR__ . '/../../../../libraries_provider/custom_options.schema.' . $version . '.json',
        json_encode($schema, JSON_PRETTY_PRINT)
    );
  }

}
