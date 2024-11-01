# Vertex AI Search

The initial focus of this module is to provide search capabilities using Vertex AI Search that are commensurate with the search capabilities currently provided by the [Google Programmable Search Engine](https://developers.google.com/custom-search/docs/overview) using the [google_json_api module](https://www.drupal.org/project/google_json_api).  The Site Restricted JSON API used by the google_json_api module will cease to operate on December 18, 2024; Google recommends Vertex AI Search as a replacement.

This Drupal module integrates with Drupal core search functionality by providing a
custom search page plugin that communicates with a [Vertex AI Search](https://cloud.google.com/enterprise-search) app hosted on the Google Cloud Platform.

For a full description of the Drupal module, visit the
[Vertex AI Search Project](https://www.drupal.org/project/vertex_ai_search).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/vertex_ai_search).

[Documentation for the Vertex AI Search module](https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/vertex-ai-search) is available on Drupal.org.


## Table of contents

- Requirements
- Installation
- Configuration
- Custom Autocomplete Plugins
- Troubleshooting
- Maintainers
- Developer Documentation

## Requirements

This module requires the following modules:

- [Token](https://www.drupal.org/project/token)
- Core Search module

This module requires the following Google Cloud Client Library:

- [Google Cloud Discovery Engine for PHP](https://cloud.google.com/php/docs/reference/cloud-discoveryengine/latest)

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

This module provides for the creation of search pages that communicate with a Vertex AI Search app on the Google Cloud Platform.  To create a Vertex AI Search Page on a Drupal site, a [Vertex AI Search app must be created](https://cloud.google.com/generative-ai-app-builder/docs/try-enterprise-search) within a Google Cloud Platform project.  A [Service Account](https://cloud.google.com/docs/authentication#service-accounts) must be created that has access to the project.   

A [Service Account](https://cloud.google.com/docs/authentication#service-accounts) is required to authenticate with Google Cloud Platform.  The Service Account key must be stored in a JSON file that is not accessible via the web.  The path to the file (relative to the website root) must be specified on the search page configuration.  When the SearchServiceClient is created, this module will pass the 'credentials' path to the constructor. 

[Serving Config](https://cloud.google.com/generative-ai-app-builder/docs/serving-configs#about-serving-configs) information is needed to configure the SearchRequest to the Vertex AI Search app.  [To retrieve a string representing a serving-config resource](https://cloud.google.com/php/docs/reference/cloud-discoveryengine/0.4.0/V1.Client.SearchServiceClient#_Google_Cloud_DiscoveryEngine_V1_Client_SearchServiceClient__servingConfigName__), the Google Cloud Project ID, Google Cloud Location, data store ID, and serving config name are needed.  The serving config name is 'default_search' by default.    

Once a Vertex AI Search app has been created on the Google Cloud Platform, a search page can be created and configured on a Drupal website:

1. Go to Administration » Configuration » Search and metadata » Search Pages
1. In the 'Search pages' section, select 'Vertex AI Search' as the Search page type and click 'Add search page'.
1. Add a Label and a Path for the search page. The Label will be used as a tab header if more than one search page is available.  Ensure the machine name is unique.
1. Provide a path to the Service Account credentials file.  This path is relative to the site root directory.
1. Provide the information needed to generate a serving-config resource.  This information can be found in the Google Cloud Platform account that contains the Vertex AI Search app.
1. Enable Autocomplete if desired.  Autocomplete can also be enabled for the search block form. Different options will be presented depending on the Autocomplete Source selected.
    - The 'Simple Autocomplete' source performs a very basic autocompletion using content titles and/or body text.
    - The 'Vertex Autocomplete' source allows selection of different vertex autocomplete models.  Search history is used for website search apps and requires a few days of traffic before providing suggestions.
1. Set the Search Results Page Display Options.
1. Customize the Search Results Page Messages.
1. Click 'Save'.

To set the new search page as the default: 

1. Go to Administration » Configuration » Search and metadata » Search Pages
1. Use the Operations pull down for the appropriate search page and 'set as default'.

To search:

1. Go to the Path specified for the Vertex AI Search page.

## Custom Autocomplete Plugins

This module defines a custom plugin type for autocompletion. Two plugins are provided (Simple Autocomplete and Vertex Autocomplete), but if custom autocomplete functionality is desired, additional plugins can be created.

Use the src/Plugin/Autocomplete/SimpleAutocomplete.php class as an example.

## Troubleshooting

If the search form does not appear on the Search Results Page (SERP):

- Go to Administration » Configuration » Search and metadata » Search Pages and
edit the appropriate search page.
- Make sure 'Display Search Form on results page' is checked.  The search form
does not display on the SERP by default.

If no autocomplete suggestions are being presented on the search form:

- If using the Vertex Autocomplete plugin, try switching to the 'Simple Autocomplete' plugin for autocompletion.  It may take a few days of real traffic to the website before Vertex will provide autocompletion suggestions.  It can be reenabled later.  

## Maintainers

- Ari Hylas - [ari.saves](https://www.drupal.org/u/arisaves)
- Michael Kinnunen - [mkinnune](https://www.drupal.org/u/mkinnune)
- Sam Lerner - [SamLerner](https://www.drupal.org/u/samlerner)
- Timo Zura - [TimoZura](https://www.drupal.org/u/timozura)

## Developer Documentation

If you wish to contribute, the [Developer Documentation](https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/vertex-ai-search/developer-documentation) on Drupal.org provides information on the following:
 - Setting up a local development environment using DDEV.
 - Creating a custom autocomplete plugin for the vertex_ai_search module. 
 - Creating a custom search results plugin to manipulate the results received from executing a search.
