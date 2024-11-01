# Smart Trim
Smart Trim implements a new field formatter for textfields (text, text_long,
and text_with_summary) that improves upon the "Summary or Trimmed" formatter
built into Drupal core.

## Requirements
Drupal contrib modules

* [Token](https://www.drupal.org/project/token)
* [Token filter](https://www.drupal.org/project/token_filter) (development dependency)

## Installation
Install and enable Smart Trim as you would any other contributed module. See
https://www.drupal.org/docs/extending-drupal/installing-modules

## Configuration
After installing and enabling Smart Trim, you will see a "Smart trimmed" option
in the format dropdown for your text fields. For content types, this is located
on the "Manage Display" page. With Smart Trim, you have control over:

*  The trim length
*  Whether the trim length is measured in characters or words
*  Appending an optional suffix at the trim point
*  Configuring (optional) "More" link immediately after the trimmed text
*  Stripping out HTML tags from the field
*  Ability to customize the trimmed content via template override

The "More" link functionality may not make sense in many contexts, and may be
redundant in situations where "Read More" is included in set of links included
with the node. But it's there if you need it.

As Smart Trim provides a field formatter, use it anywhere field formatters are
available, including Layout Builder and Views.

## Twig Filter
Smart Trim also provides a Twig filter that can be used anywhere Twig is
available. Usage as follows:
  * `{{ text | smart_trim_chars(length, suffix, strip_html) }}`
    * length - the number of characters to retain.
    * suffix - to be appended if text trimmed (optional, blank if omitted).
    * strip_html - strip HTML tags from the input (optional, default true).
  * `{{ text | smart_trim_words(length, suffix, strip_html) }}`
    * length - the number of words to retain.
    * suffix - to be appended if text trimmed (optional, blank if omitted).
    * strip_html - strip HTML tags from the input (optional, default true).

Note input text must be a single string. If it may contain an array or object,
chain with `render`. E.g.: `{{ value | render | smart_trim_chars(25) }}`


## Mission statement
Smart Trim is designed to be a focused, lightweight improvement over Drupal
core's current formatter trimming capabilities. The maintainers' focus is
stability and ease-of-use. Customizations to the module are encouraged with
template overrides and Smart Trim hook implementations.

## Documentation

### Getting started (example)
1. Navigate to Administration > Extend and enable the module.
1. Navigate to Administration > Structure > Content types > Article > Manage
display, then click to configure the "Teaser" view mode.
(/admin/structure/types/manage/article/display/teaser)
1. Select _Smart trimmed_ as the Format for the Body field.
1. Click the configuration gear for the Body field.
1. Update Smart Trim formatter configuration as desired. Configuration options
include:
   * Trim by number of characters or words.
   * Customize the "More" link.

As Smart Trim provides a field formatter, use it anywhere field formatters are
available, including Layout Builder and Views.

Smart Trim also provides a couple of Twig filters, for situations where smart
trimmed text is desired via a Twig template. For example:

* {{ some_text_value|smart_trim_chars(20, '…') }} - trims to 20 characters (not
breaking words) and adds an ellipses at the end.
* {{ some_text_value|smart_trim_words(10, '') }} - trims to 10 words.

### Full documentation
https://www.drupal.org/docs/contributed-modules/smart-trim

## Maintenance plan
The latest release is compatible with Drupal 9 and 10. Drupal 7 is minimally
maintained.

## Maintainers

* Mark Casias - [markie](https://www.drupal.org/u/markie)
* Michael Anello - [ultimike](https://www.drupal.org/u/ultimike)
* AmyJune Hineline - [volkswagenchick](https://www.drupal.org/u/volkswagenchick)

## Supporting organizations
* [DrupalEasy](https://www.drupal.org/drupaleasy)
