<?php

namespace Drupal\docraptor;

use DocRaptor\ApiException as DocRaptorApiException;
use DocRaptor\Doc;
use DocRaptor\DocApi;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\key\KeyRepositoryInterface;

/**
 * Docraptor Manager class.
 */
class DocraptorManager {

  use StringTranslationTrait;

  /**
   * Config settings.
   *
   * @var mixed
   */
  protected $config;

  /**
   * Docraptor manager.
   *
   * @var \DocRaptor\DocRaptor\Doc
   */
  public $docraptor;

  /**
   * Docraptor API manager.
   *
   * @var \DocRaptor\DocRaptor\DocApi
   */
  public $docraptorApi;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The loggers service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   * @param \Drupal\key\KeyRepositoryInterface $keyRepository
   *   The key repository.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected CacheBackendInterface $cacheBackend,
    protected LanguageManagerInterface $languageManager,
    protected KeyRepositoryInterface $keyRepository,
  ) {

    $this->config = $this->configFactory->get('docraptor.settings');

    $encryptedUsernameKey = $this->config->get('username_key');
    $username = $this->keyRepository->getKey($encryptedUsernameKey)->getKeyValue();

    $this->docraptorApi = new DocApi();
    $this->docraptorApi->getConfig()->setUsername($username);

  }

  /**
   * Prepares the PDF document with the processed plan content.
   *
   * @param string $plan_processed
   *   The processed content to be included in the PDF document.
   * @param string $file_pdf_name
   *   The name of the generated PDF file.
   */
  public function preparePdfDocument($plan_processed, $file_pdf_name) {
    $this->docraptor = new Doc();
    $this->docraptor->setTest($this->config->get('enable_test'));
    $this->docraptor->setDocumentContent($plan_processed);
    $this->docraptor->setName($file_pdf_name);
    $this->docraptor->setDocumentType($this->config->get('document_type'));
    $this->docraptor->setPrinceOptions([
      'pdf_forms' => $this->config->get('enable_pdf_forms'),
      'pdf_profile' => $this->config->get('pdf_profile'),
      'color_conversion' => $this->config->get('color_conversion'),
      'icc_profile' => $this->config->get('enable_icc_profile'),
    ]);
  }

  /**
   * Saves the generated PDF document to a specified path.
   *
   * @param string $absolute_pdf_path
   *   The absolute file path where the PDF will be saved.
   *
   * @throws \Exception
   *   Throws an exception if the DocRaptor API fails to generate the PDF.
   */
  public function savePdfDocument($absolute_pdf_path) {

    try {
      $create_response = $this->docraptorApi->createDoc($this->docraptor);
      // Save the PDF.
      file_put_contents($absolute_pdf_path, $create_response);
    }
    catch (DocRaptorApiException $error) {
      // Handle any errors.
      $this->loggerFactory->get('docraptor')
        ->error('DocRaptor API Error: @error', ['@error' => $error->getMessage()]);
      throw new \Exception('Failed to generate PDF: ' . $error->getMessage());
    }
  }

}
