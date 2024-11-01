<?php

namespace Drupal\aws\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an AWS Profile entity.
 */
interface ProfileInterface extends ConfigEntityInterface {

  /**
   * Whether the profile is the default or not.
   *
   * @return bool
   *   TRUE if the profile is the default.
   */
  public function isDefault();

  /**
   * Set the profile as the default.
   *
   * @return $this
   */
  public function setDefault(bool $default);

  /**
   * Get the role arn of the profile.
   *
   * @return string
   *   The role arn of the profile.
   */
  public function getRoleArn();

  /**
   * Set the role arn of the profile.
   *
   * @return $this
   */
  public function setRoleArn(string $aws_role_arn);

  /**
   * Get the role session name for the temporary credentials.
   *
   * @return string
   *   The role session name of the profile.
   */
  public function getRoleSessionName();

  /**
   * Set the role session name for the temporary credentials.
   *
   * @return $this
   */
  public function setRoleSessionName(string $aws_role_session_name);

  /**
   * Get the access key of the profile.
   *
   * @return string
   *   The access key of the profile.
   */
  public function getAccessKey();

  /**
   * Set the access key of the profile.
   *
   * @return $this
   */
  public function setAccessKey(string $aws_access_key_id);

  /**
   * Get the secret access key of the profile.
   *
   * @return string
   *   The secret access key of the profile.
   */
  public function getSecretAccessKey();

  /**
   * Set the secret access key of the profile.
   *
   * @return $this
   */
  public function setSecretAccessKey(string $aws_secret_access_key);

  /**
   * Get the region of the profile.
   *
   * @return string
   *   The region of the profile.
   */
  public function getRegion();

  /**
   * Set the region of the profile.
   *
   * @return $this
   */
  public function setRegion(string $region);

  /**
   * Get the encryption profile for the profile.
   *
   * @return string
   *   The encryption profile of the profile.
   */
  public function getEncryptionProfile();

  /**
   * Set the encryption profile for the profile.
   *
   * @return $this
   */
  public function setEncryptionProfile(string $encryption_profile);

  /**
   * Encrypt plaintext using $encryption_profile.
   *
   * @param string $text
   *   Plain text that should be encrypted if $encryption_prpofile is set.
   *
   * @return string
   *   The encrypted input text if a profile is set, unmodified otherwise.
   */
  public function encryptSecret(string $text);

  /**
   * Decrypt ciphertext using $encryption_profile.
   *
   * @param string $text
   *   Ciphertext that should be decrypted if $encryption_profile is set.
   *
   * @return string
   *   The decrypted input text if a profile is set, unmodifed otherwise.
   */
  public function decryptSecret(string $text);

  /**
   * Returns the arguments required to instantiate an AWS service client.
   *
   * @param string $version
   *   The API version to use. Defaults to "latest".
   *
   * @return array
   *   The client arguments.
   */
  public function getClientArgs(string $version = 'latest');

  /**
   * Creates and returns temporary credentials.
   *
   * @param string $roleArn
   *   The ARN for the role to assume.
   * @param string $version
   *   The API version to use. Defaults to "latest".
   *
   * @return array
   *   The credentials array or FALSE on error.
   */
  public function getTemporaryCredentials(string$roleArn, string $version = 'latest');

}
