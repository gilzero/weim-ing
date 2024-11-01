<?php

namespace Drupal\saml_sp\SAML;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Metadata;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\Utils;

/**
 * {@inheritdoc}
 */
class SamlSPSettings extends Settings {
  use StringTranslationTrait;

  // phpcs:disable Drupal.NamingConventions.ValidFunctionName

  /**
   * {@inheritdoc}
   */
  public function getSPMetadata($alwaysPublishEncryptionCert = FALSE, $validUntil = NULL, $cacheDuration = NULL) {
    $cert = $this->getSPcert();
    $certNew = $this->getSPcertNew();
    $sp_data = $this->getSPData();

    $validUntil = $validUntil ?? $sp_data['validUntil'];
    if (empty($validUntil)) {
      $validUntil = NULL;
    }
    elseif (strtolower($validUntil) === '<certificate>') {
      $cert_data = openssl_x509_parse($cert);
      $validUntil = $cert_data['validTo_time_t'];
    }
    else {
      try {
        $dti = new \DateTimeImmutable($validUntil);
        $validUntil = $dti->getTimestamp();
      }
      catch (\Throwable $e) {
        // PHP 8 throws a ValueError, but handle it the same as PHP 7.
        $validUntil = FALSE;
      }
      if (!$validUntil) {
        \Drupal::messenger()->addError($this->t('Cannot parse the "Valid until" date.'));
        $validUntil = NULL;
      }
    }

    $metadata = SamlSPMetadata::builder($sp_data, $this->getSecurityData()['authnRequestsSigned'], $this->getSecurityData()['wantAssertionsSigned'], $validUntil, $cacheDuration, $this->getContacts(), $this->getOrganization());

    if (!empty($certNew)) {
      $metadata = Metadata::addX509KeyDescriptors(
        $metadata,
        $certNew,
        $alwaysPublishEncryptionCert || $this->getSecurityData()['wantNameIdEncrypted'] || $this->getSecurityData()['wantAssertionsEncrypted']
      );
    }

    if (!empty($cert)) {
      $metadata = Metadata::addX509KeyDescriptors(
        $metadata,
        $cert,
        $alwaysPublishEncryptionCert || $this->getSecurityData()['wantNameIdEncrypted'] || $this->getSecurityData()['wantAssertionsEncrypted']
      );
    }

    // Sign Metadata.
    if (isset($this->getSecurityData()['signMetadata']) && $this->getSecurityData()['signMetadata'] != FALSE) {
      if ($this->getSecurityData()['signMetadata'] === TRUE) {
        $keyMetadata = $this->getSPkey();
        $certMetadata = $cert;

        if (!$keyMetadata) {
          throw new Error(
            'SP Private key not found.',
            Error::PRIVATE_KEY_FILE_NOT_FOUND
          );
        }

        if (!$certMetadata) {
          throw new Error(
            'SP Public cert not found.',
            Error::PUBLIC_CERT_FILE_NOT_FOUND
          );
        }
      }
      elseif (isset($this->getSecurityData()['signMetadata']['keyFileName']) &&
        isset($this->getSecurityData()['signMetadata']['certFileName'])) {
        $keyFileName = $this->getSecurityData()['signMetadata']['keyFileName'];
        $certFileName = $this->getSecurityData()['signMetadata']['certFileName'];

        $keyMetadataFile = $this->getCertPath() . $keyFileName;
        $certMetadataFile = $this->getCertPath() . $certFileName;

        if (!file_exists($keyMetadataFile)) {
          throw new Error(
            'SP Private key file not found: %s',
            Error::PRIVATE_KEY_FILE_NOT_FOUND,
            [$keyMetadataFile]
          );
        }

        if (!file_exists($certMetadataFile)) {
          throw new Error(
            'SP Public cert file not found: %s',
            Error::PUBLIC_CERT_FILE_NOT_FOUND,
            [$certMetadataFile]
          );
        }
        $keyMetadata = file_get_contents($keyMetadataFile);
        $certMetadata = file_get_contents($certMetadataFile);
      }
      elseif (isset($this->getSecurityData()['signMetadata']['privateKey']) &&
        isset($this->getSecurityData()['signMetadata']['x509cert'])) {
        $keyMetadata = Utils::formatPrivateKey($this->getSecurityData()['signMetadata']['privateKey']);
        $certMetadata = Utils::formatCert($this->getSecurityData()['signMetadata']['x509cert']);
        if (!$keyMetadata) {
          throw new Error(
            'Private key not found.',
            Error::PRIVATE_KEY_FILE_NOT_FOUND
          );
        }

        if (!$certMetadata) {
          throw new Error(
            'Public cert not found.',
            Error::PUBLIC_CERT_FILE_NOT_FOUND
          );
        }
      }
      else {
        throw new Error(
          'Invalid Setting: signMetadata value of the sp is not valid',
          Error::SETTINGS_INVALID_SYNTAX
              );

      }

      $signatureAlgorithm = $this->getSecurityData()['signatureAlgorithm'];
      $digestAlgorithm = $this->getSecurityData()['digestAlgorithm'];
      $metadata = Metadata::signMetadata($metadata, $keyMetadata, $certMetadata, $signatureAlgorithm, $digestAlgorithm);
    }
    return $metadata;
  }

  // phpcs:enable

}
