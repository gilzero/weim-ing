
# Tavily

## What is this

Tavily is a module that currently have two things available for it. The one thing is a service where you can get summaries or link from the [Tavily](https://tavily.com/) service for any third party module that would want to use it.

The other core feature is that it has two AI Automator types for the AI Automator module that can be found in the [AI module](https://www.drupal.org/project/ai). These makes it possible to take a search word and generate either summaries for that search word or get links with information for that search word.

For more information on how to use the AI Automator (previously AI Interpolator), check https://workflows-of-ai.com.

## Features
* Get up to 10 short summaries from 10 sources online that answers the question you ask.
* Use the AI Automator to take a text field with a search word and fill link fields (to scrape).
* Use the AI Automator to take a text field with a search word and fill string long or text long fields with summaries.
## Requirements
* Requires an account at [Tavily](https://tavily.com/). There is a free trial.
* To use it, you need to use a third party module using the service. Currently its only usable with the AI Automator submodule of the [AI module](https://www.drupal.org/project/ai)
## How to use as AI Automator type
1. Install the [AI module](https://www.drupal.org/project/ai).
2. Install this module.
3. Visit /admin/config/tavily/settings and add your api key from your Tavily account.
4. Create some entity or node type with a string field.
5. Create either a Link or Long String/Long Text field.
6. Enable AI Automator checkbox and configure it.
7. Create an entity of the type you generated, fill in some search word and save.
8. The links or texts will be filled out.
## How to use the Tavily service.
This is a code example on how you can get information from 3rd party sources about "Why should I use Drupal?".

See https://docs.tavily.com/docs/tavily-api/rest_api for most of the configs available.
```
$tavily = \Drupal::service('tavily.api');
// Configure how you want it to run.
$tavily_config = [
  'search_depth' => 'basic,
  'include_answer' => TRUE,
  'exclude_domains' => [
    'https://www.drupal.org',
  ],
];
// Get answers in a json array.
$response = $tavily->search('Why should I use Drupal', $tavily_config);
```
