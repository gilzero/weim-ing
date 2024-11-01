<?php

namespace Drupal\phpmailer_smtp\Plugin\Mail;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\phpmailer_smtp\PluginManager\PhpmailerOauth2PluginManagerInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;
use PHPMailer\PHPMailer\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the base PHPMailer SMTP class for the Drupal MailInterface.
 *
 * Base PHPMailer for Drupal implementation with support for SMTP keep-alive.
 *
 * @Mail(
 *   id = "phpmailer_smtp",
 *   label = @Translation("PHPMailer SMTP"),
 *   description = @Translation("Sends emails via SMTP using the PHPMailer library.")
 * )
 */
class PhpMailerSmtp extends PHPMailer implements MailInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * PHPMailer SMTP Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Whether to allow sending messages with an empty body.
   *
   * @var bool
   */
  public $AllowEmpty = TRUE;

  /**
   * Verbose debug output level configured for Drupal.
   *
   * @var int
   *
   * In order to be able to log error messages with actual error information and
   * see what actually went wrong for a particular message, PHPMailer::SMTPDebug
   * always needs to be enabled.
   *
   * PhpMailerSmtp::SmtpSend() overrides PHPMailer::SmtpSend() to capture the
   * debug output string and make it available for \Drupal::logger() calls.
   *
   * @see PHPMailer::SMTPDebug
   * @see SMTP::do_debug
   * @see PhpMailerSmtp::SmtpSend()
   * @see PhpMailerSmtp::drupalDebugOutput
   */
  public $drupalDebug = 0;

  /**
   * Overrides PHPMailer::SMTPDebug.
   *
   * @var int
   *
   * Capture SMTP communication errors by default.
   */
  public $SMTPDebug = 2;

  /**
   * Stores the verbose debug output of the SMTP communication.
   *
   * @var string
   */
  public $drupalDebugOutput = '';

  /**
   * Logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The PHPMailer OAuth2 plugin manager.
   *
   * @var \Drupal\phpmailer_smtp\PluginManager\PhpmailerOauth2PluginManagerInterface
   */
  protected $phpmailerOauth2PluginManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('plugin.manager.phpmailer_oauth2'),
      $container->get('renderer'),
      $container->get('file_system')
    );
  }

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $config, LoggerChannelFactoryInterface $logger_factory, MessengerInterface $messenger, PhpmailerOauth2PluginManagerInterface $plugin_manager, RendererInterface $renderer, FileSystemInterface $file_system) {
    // Throw exceptions instead of dying (since 5.0.0).
    if (method_exists(get_parent_class($this), '__construct')) {
      parent::__construct(TRUE);
    }

    $this->configFactory = $config;
    $this->config = $config->get('phpmailer_smtp.settings');
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;
    $this->phpmailerOauth2PluginManager = $plugin_manager;
    $this->renderer = $renderer;
    $this->fileSystem = $file_system;
  }

  /**
   * Initialise SMTP settings.
   */
  public function smtpInit() {
    $this->IsSMTP();
    $this->SMTPAutoTLS = FALSE;
    $this->CharSet = 'utf-8';

    $this->Host = $this->config->get('smtp_host');
    if ($backup = $this->config->get('smtp_hostbackup')) {
      $this->Host .= ';' . $backup;
    }
    $this->Port = $this->config->get('smtp_port');
    $smtp_protocol = $this->config->get('smtp_protocol');
    $this->SMTPSecure = $smtp_protocol;

    if (!empty($smtp_protocol)) {
      $ssl_verify_peer = $this->config->get('smtp_ssl_verify_peer');
      $this->SMTPOptions['ssl']['verify_peer'] = isset($ssl_verify_peer) ? $ssl_verify_peer : 1;
      $ssl_verify_peer_name = $this->config->get('smtp_ssl_verify_peer_name');
      $this->SMTPOptions['ssl']['verify_peer_name'] = isset($ssl_verify_peer_name) ? $ssl_verify_peer_name : 1;
      $ssl_allow_self_signed = $this->config->get('smtp_ssl_allow_self_signed');
      $this->SMTPOptions['ssl']['allow_self_signed'] = isset($ssl_allow_self_signed) ? $ssl_allow_self_signed : 0;
    }

    // Check for basic authentication.
    if ($this->config->get('smtp_authentication_type') === 'basic_auth') {
      // Use SMTP authentication if both username and password are given.
      $this->Username = $this->config->get('smtp_username');
      $this->Password = $this->config->get('smtp_password');
      $this->SMTPAuth = (bool) ($this->Username != '' && $this->Password != '');
    }
    // If not basic auth, check for OAuth2 plugins.
    else {
      $oauth2plugin = $this->phpmailerOauth2PluginManager->createInstance($this->config->get('smtp_authentication_type'));
      $oauth_options = $oauth2plugin->getAuthOptions();
      $oauth = new OAuth($oauth_options);
      $this->setOAuth($oauth);
      $this->AuthType = 'XOAUTH2';
      $this->SMTPAuth = TRUE;
    }

    $this->SMTPKeepAlive = $this->config->get('smtp_keepalive');

    if ($timeout = $this->config->get('smtp_timeout')) {
      $this->Timeout = $timeout;
    }

    $this->drupalDebug = $this->config->get('smtp_debug');
    if ($this->drupalDebug > $this->SMTPDebug && \Drupal::currentUser()->hasPermission('administer phpmailer smtp settings')) {
      $this->SMTPDebug = $this->drupalDebug;
    }
  }

  /**
   * Send mail via SMTP.
   *
   * Wrapper around PHPMailer::SmtpSend() with exception handling.
   */
  public function smtpSend($header, $body) {
    if ($this->SMTPDebug) {
      // Clear possibly previously captured debug output.
      $this->drupalDebugOutput = '';
      ob_start();
    }

    try {
      $result = parent::SmtpSend($header, $body);

      // Close connection when not using SMTP keep-alive.
      if (!$this->SMTPKeepAlive) {
        $this->SmtpClose();
      }
    }
    catch (Exception $exception) {
    }

    if ($this->SMTPDebug) {
      if ($this->drupalDebug && ($this->drupalDebugOutput = ob_get_contents())) {
        $this->messenger->addMessage(Markup::create($this->drupalDebugOutput));
        if ($this->config->get('smtp_debug_log')) {
          $this->loggerFactory->get('phpmailer_smtp')->debug('Output of communication with SMTP server:<br /><pre>{output}</pre>', [
            'output' => str_replace("<br>\n", "\n", Html::decodeEntities($this->drupalDebugOutput)),
          ]);
        }
      }
      ob_end_clean();
    }

    // Reinitialize properties.
    $this->reset();

    if (isset($exception)) {
      // Pass exception to caller.
      throw $exception;
    }
    return $result;
  }

  /**
   * Re-initialize properties after sending mail.
   */
  public function reset() {
    $this->clearAllRecipients();
    $this->clearReplyTos();
    $this->clearAttachments();
    $this->clearCustomHeaders();

    $this->Priority = 3;

    $this->CharSet     = 'utf-8';
    $this->ContentType = 'text/plain';
    $this->Encoding    = '8bit';

    // Set default From name.
    $from_name = $this->config->get('smtp_fromname');
    if ($from_name == '') {
      // Fall back on the site name.
      $from_name = $this->configFactory->get('system.site')->get('name');
    }
    $this->FromName  = $from_name;
    $this->Sender    = '';
    $this->MessageID = '';
  }

  /**
   * Overrides PHPMailer::__destruct().
   */
  public function __destruct() {
    // Disable debug output if SMTP keep-alive is enabled.
    // PHP is most likely shutting down altogether (this class is instantiated
    // as a static singleton). Since logging facilities (e.g., database
    // connection) quite potentially have been shut down already, simply turn
    // off SMTP debugging. Without this override, debug output would be printed
    // on the screen and CLI output.
    if ($this->SMTPKeepAlive && isset($this->smtp->do_debug)) {
      $this->smtp->do_debug = 0;
    }
    parent::__destruct();
  }

  /**
   * Provide more user-friendly error messages.
   *
   * Note: messages should not end with a dot.
   */
  public function setLanguage($langcode = 'en', $lang_path = 'language/') {
    // Retrieve English defaults to ensure all message keys are set.
    parent::SetLanguage('en');

    // Overload with Drupal translations.
    $this->language = [
      'authenticate'        => $this->t('SMTP error: Could not authenticate.'),
      'connect_host'        => $this->t('SMTP error: Could not connect to host.'),
      'data_not_accepted'   => $this->t('SMTP error: Data not accepted.'),
      'smtp_connect_failed' => $this->t('SMTP error: Could not connect to SMTP host.'),
      'smtp_error'          => $this->t('SMTP server error:'),

      // Messages used during email generation.
      'empty_message'       => $this->t('Message body empty'),
      'encoding'            => $this->t('Unknown encoding:'),
      'variable_set'        => $this->t('Cannot set or reset variable:'),

      'file_access'         => $this->t('File error: Could not access file:'),
      'file_open'           => $this->t('File error: Could not open file:'),

      // Non-administrative messages.
      'from_failed'         => $this->t('The following From address failed:'),
      'invalid_address'     => $this->t('Invalid address'),
      'provide_address'     => $this->t('You must provide at least one recipient e-mail address.'),
      'recipients_failed'   => $this->t('The following recipients failed:'),
    ] + $this->language;
    return TRUE;
  }

  /**
   * Wrap PHPMailer::RFCDate() to return the current timezone.
   */
  public static function rfcDate() {
    $tz = date('Z');
    $tzs = ($tz < 0) ? '-' : '+';
    $tz = abs($tz);
    $tz = (int) ($tz / 3600) * 100 + ($tz % 3600) / 60;
    $result = sprintf("%s %s%04d", date('D, j M Y H:i:s'), $tzs, $tz);

    return $result;
  }

  /**
   * Override PHPMailer's html2text() to handle newlines properly.
   *
   * {@inheritdoc}
   */
  public function html2text($html, $advanced = FALSE) {
    return MailFormatHelper::htmlToText(trim(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/si', '', $html)));
  }

  /**
   * Concatenates and wraps the e-mail body for plain-text mails.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   */
  public function format(array $message) {
    $message['params']['formatter'] = 'phpmailer_smtp';
    $config = $this->configFactory->get('phpmailer_smtp.format');
    $format = $config->get('format');
    $headers = array_change_key_case($message['headers']);

    // Check content type header and honour text/plain if set.
    if (isset($headers['content-type']) && !$config->get('force_html')) {
      $parts = explode(';', $headers['content-type']);
      $content_type = trim(array_shift($parts));
      if ($content_type === 'text/plain') {
        $format = 'plain_text';
      }
    }

    if ($format === 'html') {
      $this->isHTML();
      // Join the body array into one string.
      $message['body'] = implode("\n\n", $message['body']);

      // Generate the HTML.
      $render = [
        '#theme' => isset($message['params']['theme']) ? $message['params']['theme'] : 'phpmailer_smtp',
        '#body' => $message['body'],
        '#module' => $message['module'],
        '#key' => $message['key'],
        '#recipient' => $message['to'],
        '#subject' => $message['subject'],
      ];

      // Theme the body content.
      $rendered = $this->renderer->renderPlain($render);

      // Generate email HTML including inline images.
      $this->msgHTML($rendered, DRUPAL_ROOT, TRUE);

    }
    else {
      $this->isHTML(FALSE);
      // Join the body array into one string.
      $message['body'] = implode("\n\n", $message['body']);
      // Convert any HTML to plain-text.
      $message['body'] = MailFormatHelper::htmlToText($message['body']);
      // Wrap the mail body for sending.
      $message['body'] = MailFormatHelper::wrapMail($message['body']);
      $this->Body = $message['body'];
    }

    // Attach files under "files".
    if (!empty($message['params']['files']) && is_array($message['params']['files'])) {
      $this->addAttachments($message['params']['files']);
    }

    // Attach files under "attachments".
    if (!empty($message['params']['attachments']) && is_array($message['params']['attachments'])) {
      $this->addAttachments($message['params']['attachments']);
    }

    // Add the message body.
    $message['body'] = $this->Body;

    return $message;
  }

  /**
   * Add attachments.
   *
   * @param array $attachments
   *   An array of attachments.
   */
  public function addAttachments(array $attachments) {
    foreach ($attachments as $attachment) {
      if (is_array($attachment)) {
        $attachment = (object) $attachment;
      }
      // Only the file itself is required.
      if (empty($attachment->filepath) && empty($attachment->filecontent)) {
        continue;
      }

      // PHPMailer can determine filename and mime type if they aren't present.
      $filename = $attachment->filename ?? '';
      $filemime = $attachment->filemime ?? '';

      // Attach file.
      if (!empty($attachment->filepath)) {
        if (strpos($attachment->filepath, 'public://') === 0) {
          $attachment->filepath = $this->fileSystem->realpath($attachment->filepath);
        }
        $this->addAttachment($attachment->filepath, $filename, self::ENCODING_BASE64, $filemime);
      }
      else {
        $this->addStringAttachment($attachment->filecontent, $filename, self::ENCODING_BASE64, $filemime);
      }
    }
  }

  /**
   * Sends an e-mail message composed by drupal_mail().
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return bool
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   *
   * @see PHPMailer::Send()
   */
  public function mail(array $message) {
    // Initialise SMTP configuration.
    $this->smtpInit();

    // Default is to honour the content type header.
    $format = NULL;

    // Check if PHPMailer was the formatter.
    if (isset($message['params']['formatter']) && $message['params']['formatter'] === 'phpmailer_smtp') {
      // If so, set the format.
      $format = $this->configFactory->get('phpmailer_smtp.format')->get('format');
      if ($format === 'html') {
        $this->isHTML();
      }
      else {
        $this->isHTML(FALSE);
      }
    }

    try {
      // Convert headers to lowercase.
      $headers = array_change_key_case($message['headers']);
      unset($message['headers']);

      // Extract Content-Type and charset.
      if (isset($headers['content-type'])) {
        // Remove any trailing semicolon.
        $headers['content-type'] = rtrim($headers['content-type'], ';');
        if ($format === 'html' || is_null($format)) {
          $content_type = explode(';', $headers['content-type']);
          $this->ContentType = trim(array_shift($content_type));
          foreach ($content_type as $param) {
            $param = explode('=', $param, 2);
            $key = trim($param[0]);
            if ($key == 'charset') {
              $this->CharSet = trim($param[1]);
            }
            else {
              $this->ContentType .= '; ' . $key . '=' . trim($param[1]);
            }
          }
        }
        else {
          $this->ContentType = 'text/plain';
        }
        unset($headers['content-type']);
      }

      // Parse 'From' address.
      $from = $this->parseAddresses($headers['from'], TRUE, self::CHARSET_UTF8);
      $from = reset($from);
      $this->From = $from['address'];

      // Set From name.
      $this->FromName = $this->config->get('smtp_fromname');
      if ($this->FromName == '' && $from['name'] !== '') {
        $this->FromName = $from['name'];
      }
      unset($headers['from']);

      // @todo This \still\ might not be the correct way to do this.
      $phpmailer_smtp_debug_email = $this->configFactory->get('system.maintenance')->get('phpmailer_smtp_debug_email');
      if (empty($phpmailer_smtp_debug_email)) {
        // Set recipients.
        foreach ($this->parseAddresses($message['to'], TRUE, self::CHARSET_UTF8) as $address) {
          $this->AddAddress($address['address'], $address['name']);
        }
        // Extract CCs and BCCs from headers.
        if (!empty($headers['cc'])) {
          foreach ($this->parseAddresses($headers['cc'], TRUE, self::CHARSET_UTF8) as $address) {
            $this->AddCC($address['address'], $address['name']);
          }
        }
        if (!empty($headers['bcc'])) {
          foreach ($this->parseAddresses($headers['bcc'], TRUE, self::CHARSET_UTF8) as $address) {
            $this->AddBCC($address['address'], $address['name']);
          }
        }
      }
      else {
        // Reroute to debug e-mail address.
        // @todo This might not be the correct way to do this.
        $this->AddAddress($phpmailer_smtp_debug_email);
      }
      unset($headers['cc'], $headers['bcc']);

      // Extract Reply-To from headers.
      if (isset($headers['reply-to'])) {
        foreach ($this->parseAddresses($headers['reply-to'], TRUE, self::CHARSET_UTF8) as $address) {
          $this->AddReplyTo($address['address'], $address['name']);
        }
        unset($headers['reply-to']);
      }
      // @todo This might not be the correct way to do this.
      elseif ($this->config->get('smtp_always_replyto')) {
        // If no Reply-To header has been explicitly set, use the From address
        // to be able to respond to e-mails sent via Google Mail.
        $this->AddReplyTo($from['address'], $from['name']);
      }

      // Set additional properties.
      $properties = [
        'x-priority'                => 'Priority',
        'content-transfer-encoding' => 'Encoding',
        'message-id'                => 'MessageID',
      ];
      foreach ($properties as $source => $property) {
        if (isset($headers[$source])) {
          $this->$property = $headers[$source];
          unset($headers[$source]);
        }
      }

      // Return-Path should not be set by Drupal.
      if (isset($headers['return-path'])) {
        unset($headers['return-path']);
      }

      // X-Mailer is set by PHPMailer which is the mailer.
      if (isset($headers['x-mailer'])) {
        unset($headers['x-mailer']);
      }

      // Set default sender address.
      $envelopeSender = $this->parseAddresses($message['from'], TRUE, self::CHARSET_UTF8);
      $envelopeSender = reset($envelopeSender);
      $this->Sender = $envelopeSender['address'];

      // Check envelope sender option.
      $senderOption = $this->config->get('smtp_envelope_sender_option');

      if ($senderOption === 'site_mail') {
        $this->Sender = $this->configFactory->get('system.site')->get('mail');
      }

      if ($senderOption === 'from_address') {
        $this->Sender = $from['address'];
      }

      if ($senderOption === 'other') {
        $this->Sender = $this->config->get('smtp_envelope_sender');
      }

      if (!empty($this->config->get('smtp_ehlo_host'))) {
        $this->Hostname = $this->config->get('smtp_ehlo_host');
      }

      // This one is always set by PHPMailer.
      unset($headers['mime-version']);

      // Add remaining header lines.
      // Note: Any header lines MUST already be checked by the caller for
      // unwanted newline characters to avoid header injection.
      // @see PHPMailer::SecureHeader()
      foreach ($headers as $key => $value) {
        $this->AddCustomHeader($key, $value);
      }

      $this->Subject = $message['subject'];
      $this->Body = $message['body'];

      return $this->Send();
    }
    catch (Exception $e) {
      // Log the error including verbose debug information.
      // Since DBLog module is the most common case, we use HTML to format the
      // message for visual inspection. For sites running with Syslog or other
      // logging modules, we put the actual values on separate lines (\n), so
      // the surrounding HTML markup doesn't get too disturbing.
      // Message is a safe $this->t() string from PhpMailerSmtp::SetLanguage().
      $output = $e->getMessage();
      // Attempt to delimit summary from full message.
      $output .= " \n";
      $arguments = [];
      // Append SMTP communication output.
      if ($this->drupalDebugOutput) {
        // PHPMailer debug output contains HTML linebreaks. PRE is more
        // readable.
        $output .= '<p><strong>Server response:</strong></p>';
        $output .= "<pre>\n@smtp_output\n</pre>";
        $arguments += [
          '@smtp_output' => str_replace("<br>\n", "\n", Html::decodeEntities($this->drupalDebugOutput)),
        ];
      }
      // We need to log the message in order to be able to debug why the server
      // responded with an error. The mail body may contain passwords and other
      // sensitive information, which should not be logged. Since all kind of
      // mails are processed and Drupal provides no way to mark sensible data,
      // it is technically impossible prevent logging in all cases.
      // Remove $params; they've already been processed and may contain sensible
      // data.
      unset($message['params']);

      // Subject.
      $output .= "<p><strong>Subject:</strong> \n@subject\n</p>";
      $arguments += [
        '@subject' => $message['subject'],
      ];
      unset($message['subject']);
      // Body.
      $output .= '<p><strong>Body:</strong></p>';
      $output .= "<pre>\n@body\n</pre>";
      $arguments += [
        '@body' => $message['body'],
      ];
      unset($message['body']);
      // Rest of $message.
      $output .= '<p><strong>Message:</strong></p>';
      $output .= "<pre>\n@message\n</pre>";
      $arguments += [
        '@message' => var_export($message, TRUE),
      ];
      $this->loggerFactory->get('phpmailer_smtp')->error($output, $arguments);
      return FALSE;
    }
  }

}
