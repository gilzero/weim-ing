<?php

namespace Drupal\saml_sp\SAML;

use Drupal\Core\Url;
use OneLogin\Saml2\Metadata;
use OneLogin\Saml2\Utils;

/**
 * {@inheritdoc}
 */
class SamlSPMetadata extends Metadata {

  /**
   * {@inheritdoc}
   */
  public static function builder($sp, $authnsign = FALSE, $wsign = FALSE, $validUntil = NULL, $cacheDuration = NULL, $contacts = [], $organization = [], $attributes = [], $ignoreValidUntil = false) {

    if (!isset($validUntil)) {
      $validUntil = time() + self::TIME_VALID;
    }
    $validUntilTime = Utils::parseTime2SAML($validUntil);

    if (!isset($cacheDuration)) {
      $cacheDuration = self::TIME_CACHED;
    }

    $sls = '';

    if (isset($sp['singleLogoutService'])) {
      $slsUrl = htmlspecialchars($sp['singleLogoutService']['url'], ENT_QUOTES);
      $sls = <<<SLS_TEMPLATE
        <md:SingleLogoutService Binding="{$sp['singleLogoutService']['binding']}"
                                Location="{$slsUrl}" />

SLS_TEMPLATE;
    }

    if ($authnsign) {
      $strAuthnsign = 'true';
    }
    else {
      $strAuthnsign = 'false';
    }

    if ($wsign) {
      $strWsign = 'true';
    }
    else {
      $strWsign = 'false';
    }

    $strOrganization = '';

    if (!empty($organization)) {
      $organizationInfoNames = [];
      $organizationInfoDisplaynames = [];
      $organizationInfoUrls = [];
      foreach ($organization as $lang => $info) {
        $organizationInfoNames[] = <<<ORGANIZATION_NAME
       <md:OrganizationName xml:lang="{$lang}">{$info['name']}</md:OrganizationName>
ORGANIZATION_NAME;
        $organizationInfoDisplaynames[] = <<<ORGANIZATION_DISPLAY
       <md:OrganizationDisplayName xml:lang="{$lang}">{$info['displayname']}</md:OrganizationDisplayName>
ORGANIZATION_DISPLAY;
        $organizationInfoUrls[] = <<<ORGANIZATION_URL
       <md:OrganizationURL xml:lang="{$lang}">{$info['url']}</md:OrganizationURL>
ORGANIZATION_URL;
      }
      $orgData = implode("\n", $organizationInfoNames) . "\n" . implode("\n", $organizationInfoDisplaynames) . "\n" . implode("\n", $organizationInfoUrls);
      $strOrganization = <<<ORGANIZATIONSTR

    <md:Organization>
{$orgData}
    </md:Organization>
ORGANIZATIONSTR;
    }

    $strContacts = '';
    if (!empty($contacts)) {
      $contactsInfo = [];
      foreach ($contacts as $type => $info) {
        $contactsInfo[] = <<<CONTACT
    <md:ContactPerson contactType="{$type}">
        <md:GivenName>{$info['givenName']}</md:GivenName>
        <md:EmailAddress>{$info['emailAddress']}</md:EmailAddress>
    </md:ContactPerson>
CONTACT;
      }
      $strContacts = "\n" . implode("\n", $contactsInfo);
    }

    $strAttributeConsumingService = '';
    if (isset($sp['attributeConsumingService'])) {
      $attrCsDesc = '';
      if (isset($sp['attributeConsumingService']['serviceDescription'])) {
        $attrCsDesc = sprintf(
          '            <md:ServiceDescription xml:lang="en">%s</md:ServiceDescription>' . PHP_EOL,
          $sp['attributeConsumingService']['serviceDescription']
        );
      }
      if (!isset($sp['attributeConsumingService']['serviceName'])) {
        $sp['attributeConsumingService']['serviceName'] = 'Service';
      }
      $requestedAttributeData = [];
      foreach ($sp['attributeConsumingService']['requestedAttributes'] as $attribute) {
        $requestedAttributeStr = sprintf('            <md:RequestedAttribute Name="%s"', $attribute['name']);
        if (isset($attribute['nameFormat'])) {
          $requestedAttributeStr .= sprintf(' NameFormat="%s"', $attribute['nameFormat']);
        }
        if (isset($attribute['friendlyName'])) {
          $requestedAttributeStr .= sprintf(' FriendlyName="%s"', $attribute['friendlyName']);
        }
        if (isset($attribute['isRequired'])) {
          $requestedAttributeStr .= sprintf(' isRequired="%s"', $attribute['isRequired'] === TRUE ? 'true' : 'false');
        }
        $reqAttrAuxStr = " />";

        if (isset($attribute['attributeValue']) && !empty($attribute['attributeValue'])) {
          $reqAttrAuxStr = '>';
          if (is_string($attribute['attributeValue'])) {
            $attribute['attributeValue'] = [$attribute['attributeValue']];
          }
          foreach ($attribute['attributeValue'] as $attrValue) {
            $reqAttrAuxStr .= <<<ATTRIBUTEVALUE

                <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">{$attrValue}</saml:AttributeValue>
ATTRIBUTEVALUE;
          }
          $reqAttrAuxStr .= "\n            </md:RequestedAttribute>";
        }

        $requestedAttributeData[] = $requestedAttributeStr . $reqAttrAuxStr;
      }

      $requestedAttributeStr = implode(PHP_EOL, $requestedAttributeData);
      $strAttributeConsumingService = <<<METADATA_TEMPLATE
<md:AttributeConsumingService index="1">
            <md:ServiceName xml:lang="en">{$sp['attributeConsumingService']['serviceName']}</md:ServiceName>
{$attrCsDesc}{$requestedAttributeStr}
        </md:AttributeConsumingService>
METADATA_TEMPLATE;
    }

    $spEntityId = htmlspecialchars($sp['entityId'], ENT_QUOTES);
    $acsUrl = htmlspecialchars($sp['assertionConsumerService']['url'], ENT_QUOTES);

    $config = \Drupal::config('saml_sp.settings');

    // The consumer endpoint will always be /saml/consume.
    $endpoint_url = Url::fromRoute('saml_sp.consume', [], [
      'language' => FALSE,
      'alias' => TRUE,
      'absolute' => TRUE,
    ]);

    // Drupal URL to consume the response from the IdP:
    $assertion_urls = trim($config->get('assertion_urls'));
    if (empty($assertion_urls)) {
      $assertion_urls = [$endpoint_url->toString()];
    }
    else {
      $assertion_urls = explode("\n", $config->get('assertion_urls'));
      $assertion_urls = array_map('trim', $assertion_urls);
    }

    $acs = '';
    $acs_count = 0;
    foreach ($assertion_urls as $url) {
      if ($acs_count == 0) {
        $default = 'true';
      }
      else {
        $default = 'false';
      }
      $acs .= <<<ACS
<md:AssertionConsumerService Binding="{$sp['assertionConsumerService']['binding']}"
                                     Location="{$url}"
                                     index="{$acs_count}"
                                     isDefault="{$default}"/>
ACS;
      $acs_count++;
    }

    $metadata = <<<METADATA_TEMPLATE
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     validUntil="{$validUntilTime}"
                     cacheDuration="PT{$cacheDuration}S"
                     entityID="{$spEntityId}">
    <md:SPSSODescriptor AuthnRequestsSigned="{$strAuthnsign}" WantAssertionsSigned="{$strWsign}" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
{$sls}        <md:NameIDFormat>{$sp['NameIDFormat']}</md:NameIDFormat>
        {$acs}
        {$strAttributeConsumingService}
    </md:SPSSODescriptor>{$strOrganization}{$strContacts}
</md:EntityDescriptor>
METADATA_TEMPLATE;
    return $metadata;
  }

}
