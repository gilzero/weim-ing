<?php

namespace Drupal\social_auth_amazon;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\social_auth\AuthManager\OAuth2Manager;
use Drupal\social_auth\User\SocialAuthUser;
use Drupal\social_auth\User\SocialAuthUserInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Contains all the logic for Amazon OAuth2 authentication.
 */
class AmazonAuthManager extends OAuth2Manager {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Used for accessing configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Used to get the authorization code from the callback request.
   */
  public function __construct(
    ConfigFactory $configFactory,
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack,
  ) {
    parent::__construct(
      $configFactory->get('social_auth_amazon.settings'),
      $logger_factory,
      $this->request = $request_stack->getCurrentRequest()
    );
  }

  /**
   * Authenticates the users by using the access token.
   */
  public function authenticate(): void {
    try {
      $this->setAccessToken(
        $this->client->getAccessToken('authorization_code', ['code' => $this->request->query->get('code')])
      );
    }
    catch (IdentityProviderException $e) {
      $this->loggerFactory->get('social_auth_amazon')
        ->error('There was an error during authentication. Exception: ' . $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInfo(): ?SocialAuthUserInterface {
    $access_token = $this->getAccessToken();

    if (!$this->user && $access_token !== NULL) {
      $owner = $this->client->getResourceOwner($this->getAccessToken());
      $this->user = new SocialAuthUser(
        $owner->getName(),
        $owner->getId(),
        $access_token,
        $owner->getEmail(),
        additional_data: $this->getExtraDetails()
      );
    }

    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationUrl(): string {
    $scopes = ['profile'];

    $extra_scopes = $this->getScopes();
    if ($extra_scopes) {
      $scopes = array_merge($scopes, explode(',', $extra_scopes));
    }

    // Returns the URL where user will be redirected.
    return $this->client->getAuthorizationUrl([
      'scope' => $scopes,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function requestEndPoint($method, $path, $domain = NULL, array $options = []): mixed {
    // Amazon offers only 'login with' services.
  }

  /**
   * {@inheritdoc}
   */
  public function getState(): string {
    return $this->client->getState();
  }

}
