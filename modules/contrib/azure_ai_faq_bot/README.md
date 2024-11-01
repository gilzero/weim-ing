## INTRODUCTION

The Azure AI FAQ Bot module is a Drupal module that integrates Azure's AI capabilities to provide a FAQ chatbot on your Drupal site. This module allows you to leverage Azure's AI services to create an interactive and intelligent FAQ bot that can answer user queries based on a predefined knowledge base.

The primary use case for this module is:

- Provide an interactive FAQ chatbot to assist users with common questions.
- Leverage Azure's AI services to enhance user experience with intelligent responses.
- Easily configure and manage the FAQ bot within the Drupal admin interface.

## REQUIREMENTS

This module requires the following dependencies:

- Azure AI services (specifically the Azure Language Studio QnA Maker)

- Follow the quickstart guide to set up the Azure AI QnA services: [Quickstart Guide](https://learn.microsoft.com/en-us/azure/ai-services/language-service/question-answering/quickstart/sdk?tabs=macos&pivots=studio)

- Publish Bot Service: [Bot Service Tutorial](https://learn.microsoft.com/en-us/azure/ai-services/language-service/question-answering/tutorials/bot-service)


## INSTALLATION

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/node/895232 for further information.

1. Download and enable the module:
   ```bash
   composer require drupal/azure_ai_faq_bot
   drush en azure_ai_faq_bot
   ```
2. Configure the necessary Azure AI services and obtain the required API keys.

## CONFIGURATION

1. Navigate to /admin/config/azure-ai-faq-bot/settings to configure the module settings.
2. Enter the Direct Line API secret key.
3. Configure the FAQ bot block and place it in the desired region of your site.

## MAINTAINERS

Current maintainers for Drupal 10/11:

- Phuoc Hoang (phoang) - https://www.drupal.org/u/phoang
