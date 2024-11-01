<?php

namespace Drupal\hcaptcha\HCaptcha;

interface RequestMethod
{
  /**
   * Submit the request with the specified parameters.
   *
   * @param string $url
   * @param array $params Request parameters
   *
   * @return \stdClass Body of the hCaptcha response
   */
  public function submit($url, array $params);
}
