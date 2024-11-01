<?php

namespace Drupal\push_framework;

/**
 * Contains all events triggered by push framework channels.
 */
final class ChannelEvents {

  public const PREPARE_TEMPLATES = 'push_framework.channel.prepare_templates';

  public const PRE_BUILD = 'push_framework.channel.pre_build';

  public const PRE_RENDER = 'push_framework.channel.pre_render';

  public const POST_RENDER = 'push_framework.channel.post_render';

}
