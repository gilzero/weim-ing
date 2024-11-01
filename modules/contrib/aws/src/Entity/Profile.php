<?php

declare(strict_types=1);

namespace Drupal\aws\Entity;

use Aws\Credentials\CredentialProvider;
use Aws\Sts\StsClient;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\encrypt\EncryptServiceInterface;

/**
 * Defines the AWS Profile entity.
 *
 * @ConfigEntityType(
 *   id = "aws_profile",
 *   label = @Translation("AWS Profile"),
 *   label_collection = @Translation("AWS Profiles"),
 *   label_singular = @Translation("AWS profile"),
 *   label_plural = @Translation("AWS profiles"),
 *   label_count = @PluralTranslation(
 *     singular = "@count AWS profile",
 *     plural = "@count AWS profiles",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\aws\Entity\Storage\ProfileStorage",
 *     "list_builder" = "Drupal\aws\Entity\ListBuilder\ProfileListBuilder",
 *     "form" = {
 *       "default" = "Drupal\aws\Entity\Form\ProfileForm",
 *       "edit" = "Drupal\aws\Entity\Form\ProfileForm",
 *       "delete" = "Drupal\aws\Entity\Form\ProfileDeleteConfirmForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer aws",
 *   config_prefix = "profile",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "default",
 *     "aws_role_arn",
 *     "aws_role_session_name",
 *     "aws_access_key_id",
 *     "aws_secret_access_key",
 *     "region",
 *     "encryption_profile"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/aws/profile/{aws_profile}",
 *     "add-form" = "/admin/config/services/aws/add-profile",
 *     "edit-form" = "/admin/config/services/aws/profile/{aws_profile}/edit",
 *     "delete-form" = "/admin/config/services/aws/profile/{aws_profile}/delete",
 *     "collection" = "/admin/config/services/aws/profiles",
 *   }
 * )
 */
class Profile extends ConfigEntityBase implements ProfileInterface {

  /**
   * The ID of the profile.
   *
   * @var string
   */
  protected $id;

  /**
   * The name of the profile.
   *
   * @var string
   */
  protected $name;

  /**
   * Whether the profile is the default or not.
   *
   * @var int
   */
  protected $default;

  /**
   * The role arn of the profile.
   *
   * @var string
   */
  protected $aws_role_arn;

  /**
   * The role session name for temporary credentials for the profile.
   *
   * @var string
   */
  protected $aws_role_session_name;

  /**
   * The access key of the profile.
   *
   * @var string
   */
  protected $aws_access_key_id;

  /**
   * The secret access key of the profile.
   *
   * @var string
   */
  protected $aws_secret_access_key;

  /**
   * The region of the profile.
   *
   * @var string
   */
  protected $region;

  /**
   * The encryption profile for the profile.
   *
   * @var string
   */
  protected $encryption_profile;

  /**
   * The encryption service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface|null
   */
  protected $encryption;

  /**
   * Constructs an Entity object.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   * @param string $entity_type
   *   The type of the entity to create.
   * @param \Drupal\encrypt\EncryptServiceInterface|null $encryption
   *   The encryption service.
   */
  public function __construct(array $values, $entity_type, ?EncryptServiceInterface $encryption) {
    parent::__construct($values, $entity_type);
    $this->encryption = $encryption;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return (bool) $this->default;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault(bool $default) {
    $this->default = (int) $default;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoleArn() {
    return $this->aws_role_arn;
  }

  /**
   * {@inheritdoc}
   */
  public function setRoleArn(string $aws_role_arn) {
    $this->aws_role_arn = $aws_role_arn;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoleSessionName() {
    return $this->aws_role_session_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setRoleSessionName(string $aws_role_session_name) {
    $this->aws_role_session_name = $aws_role_session_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessKey() {
    return $this->aws_access_key_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessKey(string $aws_access_key_id) {
    $this->aws_access_key_id = $aws_access_key_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecretAccessKey() {
    if (empty($this->aws_secret_access_key)) {
      return '';
    }
    return $this->decryptSecret($this->aws_secret_access_key);
  }

  /**
   * {@inheritdoc}
   */
  public function setSecretAccessKey(string $aws_secret_access_key) {
    $this->aws_secret_access_key = $this->encryptSecret($aws_secret_access_key);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegion() {
    return $this->region;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegion(string $region) {
    $this->region = $region;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptionProfile() {
    return $this->encryption_profile;
  }

  /**
   * {@inheritdoc}
   */
  public function setEncryptionProfile(string $encryption_profile) {
    $this->encryption_profile = $encryption_profile;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function encryptSecret(string $text) {
    if (!$this->encryption || $this->encryption_profile == '_none') {
      return $text;
    }

    $storage = $this->entityTypeManager()->getStorage('encryption_profile');
    /** @var \Drupal\encrypt\EncryptionProfileInterface  $encryption_profile */
    $encryption_profile = $storage->load($this->encryption_profile);
    return $this->encryption->encrypt($text, $encryption_profile);
  }

  /**
   * {@inheritdoc}
   */
  public function decryptSecret(string $text) {
    if (empty($text) || !$this->encryption || $this->encryption_profile == '_none') {
      return $text;
    }

    $storage = $this->entityTypeManager()->getStorage('encryption_profile');
    /** @var \Drupal\encrypt\EncryptionProfileInterface  $encryption_profile */
    $encryption_profile = $storage->load($this->encryption_profile);
    return $this->encryption->decrypt($text, $encryption_profile);
  }

  /**
   * {@inheritdoc}
   */
  public function getClientArgs(string $version = 'latest') {
    $rtn = [];

    // If a role arn is set, obtain temporary credentials from AWS.
    $roleArn = $this->getRoleArn();
    if (!empty($roleArn)) {
      $rtn['credentials'] = $this->getTemporaryCredentials($roleArn, $version);
    }
    else {
      // Otherwise, use the stored credentials.
      $accessKey = $this->getAccessKey();
      $secretKey = $this->getSecretAccessKey();
      if (!empty($accessKey) && !empty($secretKey)) {
        $rtn['credentials'] = [
          'key' => $accessKey,
          'secret' => $secretKey,
        ];
      }
    }

    // Fall through to defaults if we have nothing.
    if (empty($rtn['credentials'])) {
      $rtn['credentials'] = CredentialProvider::defaultProvider();
    }

    $rtn['region'] = $this->getRegion();
    $rtn['version'] = $version;
    return $rtn;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemporaryCredentials(string $roleArn, string $version = 'latest') {

    // Fetch credentials from the key_value_expiry store.
    $kve_store = \Drupal::service('keyvalue.expirable')->get('aws_profile');
    $credentials = $kve_store->get($this->id, null);

    // If we got a value they are valid, so early return them.
    if (!empty($credentials)) {
      $credentials['secret'] = $this->decryptSecret($credentials['secret']);
      return $credentials;
    }

    // No credentials, request some.
    try {
      $stsClient = new StsClient([
        'region' => $this->getRegion(),
        'version' => $version,
      ]);
      $stsResult = $stsClient->AssumeRole([
        'RoleArn' => $roleArn,
        'RoleSessionName' => $this->getRoleSessionName() ?: 'aws-session-' . $this->id,
        ]);
    } catch (Exception $e) {
      \Drupal::logger('aws')->error($e->getMessage());
      return FALSE;
    }
    // Success. Write a log detailing what we did.
    \Drupal::logger('aws')->notice('Obtained temporary credentials for role @roleArn', ['@roleArn' => $stsResult['AssumedRoleUser']['Arn']]);

    $credentials = [
      'key'    => $stsResult['Credentials']['AccessKeyId'],
      'secret' => $stsResult['Credentials']['SecretAccessKey'],
      'token'  => $stsResult['Credentials']['SessionToken'],
    ];

    // Calculate the expiry, minus a minute to avoid edge cases.
    $expiry = $stsResult['Credentials']['Expiration']->getTimestamp() - time() - 60;

    // Encrypt the secret before storing, if required.
    $store_credentials = $credentials;
    $store_credentials['secret'] = $this->encryptSecret($credentials['secret']);

    // Store the temporary credentials.
    $kve_store->setWithExpire($this->id, $store_credentials, $expiry);

    // Return non-encrypted credentials for use.
    return $credentials;
  }
}
