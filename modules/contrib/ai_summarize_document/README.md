# Introduction

Summarize document media (only PDF format for now) with the help of any large language model provider configured with
Drupal AI module.
Please note that this module is a work in progress!

For a full description of the module visit the [AI: Summarize Document Project Page](https://www.drupal.org/project/ai_summarize_document)

# Contributing

We appreciate contributions of all kinds for the project.

If you haven't already, familiarize yourself with [Drupal's Code of Conduct](https://www.drupal.org/dcoc).

We design improvements and prioritize issues based on our vision and values.

## Vision

The module helps you to parse already uploaded documents,
and have them summarized with a configured Drupal AI LLM provider
with a set of Tones and Length in which you want the response to come.

## Values

The AI: Summarize Document module is designed to be:

* **helpful** for authors
* **intuitive** for site builders
* **sustainable** for site owners
* **extendable** for developers

## Reporting Bugs and Requesting Features

* To submit bug reports and feature suggestions,
  visit https://www.drupal.org/project/issues/ai_summarize_document


# Requirements

This module requires the [Drupal AI module](https://www.drupal.org/project/ai).


# Installation

Install the AI: Summarize Document module as you would normally
install a contributed Drupal module.

# Configuration

1. Make sure to set up the Drupal AI module to have LLMs configured.
2. Create a Text Format with CKEditor and configure it to have AI Assistant plugin enabled.
3. Configure the AI Assistant in the same page and enable the Summarize Document plugin.
4. Optionally define taxonomies and terms for Tone and Length. Term description should contain prompt for these definitions that explains how to respond for the LLM.
5. Update and save.

# Maintainers

 * [Adam Nagy(joevagyok)](https://www.drupal.org/u/joevagyok)

Supporting organizations:

 * [European Commission](https://www.drupal.org/european-commission)
