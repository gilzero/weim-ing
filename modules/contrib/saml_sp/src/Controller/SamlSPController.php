<?php

namespace Drupal\saml_sp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\Response as Saml2_Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides route responses for the SAML SP module.
 */
class SamlSPController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;
  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;



  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('messenger'),
      $container->get('logger.factory')->get('saml_sp'),
      $container->get('config.factory')
    );
  }

  /**
   * Creates the controller with a RequestStack.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(RequestStack $request_stack, MessengerInterface $messenger, LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    $this->requestStack = $request_stack;
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   * Generate the XMl metadata for the given IdP.
   */
  public function metadata($return_string = FALSE) {
    [$metadata, $errors] = saml_sp__get_metadata();

    $output = $metadata;

    if ($return_string) {
      return $output;
    }
    $response = new Response();
    $response->setContent($metadata);
    $response->headers->set('Content-Type', 'text/xml');
    return $response;
  }

  /**
   * Receive data back from the IdP.
   */
  public function consume() {
    $request = $this->requestStack->getCurrentRequest();
    if (!$this->validAuthenticationResponse($request)) {
      return new RedirectResponse(Url::fromRoute('<front>')->toString());
    }

    // The \OneLogin\Saml2\Response object uses the settings to verify the
    // validity of a request, in \OneLogin\Saml2\Response::isValid(), via
    // XMLSecurityDSig. Extract the incoming ID (the `inresponseto` parameter
    // of the `<samlp:response` XML node).
    $response_data = $request->request->get('SAMLResponse');
    if ($inbound_id = _saml_sp__extract_inbound_id($response_data)) {
      if ($tracked_request = saml_sp__get_tracked_request($inbound_id)) {
        $idp = saml_sp_idp_load($tracked_request['idp']);

        // Try to check the validity of the samlResponse.
        try {
          $saml_response = NULL;
          $certs = $idp->getX509Cert();
          if (!is_array($certs)) {
            $certs = [$certs];
          }
          $is_valid = FALSE;
          // Go through each cert and see if any provides a valid response.
          foreach ($certs as $cert) {
            $idp->setX509Cert([$cert]);
            $settings = saml_sp__get_settings($idp);
            // Creating Saml2 Settings object from array:
            $saml_settings = new Settings($settings);
            $saml_response = new Saml2_Response($saml_settings, $response_data);
            // $saml_response->isValid() will throw various exceptions
            // to communicate any errors. Sadly, these are all of type
            // Exception - no subclassing.
            $is_valid = $saml_response->isValid();
            if ($is_valid) {
              break;
            }
          }
          assert(!is_null($saml_response));
        }
        catch (\Exception $e) {
          // @todo Inspect the Exceptions, and log a meaningful error condition.
          $this->logger->error('Invalid response, %exception', ['%exception' => $e->getMessage()]);
          $is_valid = FALSE;
        }
        // Remove the now-expired tracked request.
        $store = saml_sp_get_tempstore('track_request');
        $store->delete($inbound_id);

        if (!$is_valid) {
          $exception = $saml_response->getErrorException();
          $exception_vars = Error::decodeException($exception);
          $this->logger->error('%type: @message in %function (line %line of %file).', $exception_vars);
          $error = $saml_response->getError();
          [$problem] = array_reverse(explode(' ', $error));

          switch ($problem) {
            case 'Responder':
              $message = $this->t('There was a problem with the response from @idp_name. Please try again later.', [
                '@idp_name' => $idp->label(),
              ]);
              break;

            case 'Requester':
              $message = $this->t('There was an issue with the request made to @idp_name. Please try again later.', [
                '@idp_name' => $idp->label(),
              ]);
              break;

            case 'VersionMismatch':
              $message = $this->t('SAML VersionMismatch between @idp_name and @site_name. Please try again later.', [
                '@idp_name' => $idp->label(),
                '@site_name' => $this->configFactory->get('system.site')->get('name'),
              ]);
              break;
          }
          if (!empty($message)) {
            $this->messenger->addMessage($message, MessengerInterface::TYPE_ERROR);
          }
          $this->logger->error('Invalid response, @error: <pre>@response</pre>', [
            '@error' => $error,
            '@response' => print_r($saml_response->response, TRUE),
          ]);
        }

        // Invoke the callback function.
        $callback = $tracked_request['callback'];
        $result = $callback($is_valid, $saml_response, $idp);

        // The callback *should* redirect the user to a valid page.
        // Provide a fail-safe just in case it doesn't.
        if (empty($result)) {
          return new RedirectResponse(Url::fromRoute('user.page')->toString());
        }
        else {
          return $result;
        }
      }
      else {
        $this->logger->error('Request with inbound ID @id not found.', ['@id' => $inbound_id]);
      }
    }
    // Failover: redirect to the homepage.
    $this->logger->warning('Failover: redirect to the homepage. No inbound ID or something.');
    return new RedirectResponse(Url::fromRoute('<front>')->toString());
  }

  /**
   * Check that a request is a valid SAML authentication response.
   *
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The current request object.
   *
   * @return bool
   *   TRUE if the response is valid.
   */
  private function validAuthenticationResponse($request = NULL) {
    if (is_null($request)) {
      $this->logger->warning('SamlSPController::validAuthenticationResponse() requires an argument as of version 4.2.0 and will fail without it in version 5.0.0');
      $request = $this->requestStack->getCurrentRequest();
    }
    $method = $request->server->get('REQUEST_METHOD');
    return ($method === 'POST' && !empty($request->request->get('SAMLResponse')));
  }

  /**
   * Log the user out.
   */
  public function logout() {

  }

}
