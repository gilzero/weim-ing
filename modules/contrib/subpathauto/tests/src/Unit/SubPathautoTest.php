<?php

namespace Drupal\Tests\subpathauto\Unit;

use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\subpathauto\PathProcessor;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\subpathauto\PathProcessor
 * @group subpathauto
 */
class SubPathautoTest extends UnitTestCase {

  /**
   * The mocked path alias processor.
   *
   * @var \Drupal\path_alias\PathProcessor\AliasPathProcessor|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $aliasProcessor;

  /**
   * The mocked language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The mocked path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pathValidator;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The mocked configuration entity.
   *
   * @var \Drupal\Core\Config\ConfigBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $subPathautoSettings;

  /**
   * The mocked configuration entity.
   *
   * @var \Drupal\Core\Config\ConfigBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageNegotiation;

  /**
   * Language negotiation settings.
   *
   * @var array
   */
  protected $languageNegotiationSettings = [
    'source' => LanguageNegotiationUrl::CONFIG_PATH_PREFIX,
    'prefixes' => [
      'en' => 'default_language',
    ],
  ];

  /**
   * The path processor service.
   *
   * @var \Drupal\subpathauto\PathProcessor
   */
  protected $pathProcessor;

  /**
   * List of aliases used in the tests.
   *
   * @var string[]
   */
  protected $aliases = [
    '/content/first-node' => '/node/1',
    '/content/first-node-test' => '/node/1/test',
    '/malicious-path' => '/admin',
    '' => '<front>',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->aliasProcessor = $this->getMockBuilder('Drupal\path_alias\PathProcessor\AliasPathProcessor')
      ->disableOriginalConstructor()
      ->getMock();

    $this->languageManager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->willReturn(new Language(Language::$defaultValues));

    $this->pathValidator = $this->createMock('Drupal\Core\Path\PathValidatorInterface');
    $this->languageNegotiation = $this->createMock('Drupal\Core\Config\ConfigBase');

    $this->subPathautoSettings = $this->createMock('Drupal\Core\Config\ConfigBase');

    $this->configFactory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
    $this->configFactory->expects($this->any())
      ->method('get')
      ->with($this->logicalOr(
        $this->equalTo('subpathauto.settings'),
        $this->equalTo('language.negotiation')
      ))
      ->willReturnCallback(
        function ($param) {
          $config = func_get_arg(0);
          if ($config == 'subpathauto.settings') {
            return $this->subPathautoSettings;
          }
          elseif ($config == 'language.negotiation') {
            return $this->languageNegotiation;
          }

          return NULL;
        }
      );

    $this->languageNegotiation->expects($this->any())
      ->method('get')
      ->willReturn($this->languageNegotiationSettings);

    $module_handler = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandler')
      ->disableOriginalConstructor()
      ->getMock();
    $module_handler->expects($this->any())
      ->method('moduleExists')
      ->willReturn(FALSE);

    $this->pathProcessor = new PathProcessor($this->aliasProcessor, $this->languageManager, $this->configFactory, $module_handler);
    $this->pathProcessor->setPathValidator($this->pathValidator);
  }

  /**
   * @covers ::processInbound
   */
  public function testInboundSubPath(): void {
    $this->aliasProcessor->expects($this->any())
      ->method('processInbound')
      ->willReturnCallback([$this, 'pathAliasCallback']);
    $this->pathValidator->expects($this->any())
      ->method('getUrlIfValidWithoutAccessCheck')
      ->willReturn(new Url('any_route'));
    $this->subPathautoSettings->expects($this->atLeastOnce())
      ->method('get')
      ->willReturn(0);

    // Look up a subpath of the 'content/first-node' alias.
    $processed = $this->pathProcessor->processInbound('/content/first-node/a', Request::create('/content/first-node/a'));
    $this->assertEquals('/node/1/a', $processed);

    // Look up a subpath of the 'content/first-node' alias when request has
    // language prefix.
    $processed = $this->pathProcessor->processInbound('/content/first-node/a', Request::create('/default_language/content/first-node/a'));
    $this->assertEquals('/node/1/a', $processed);

    // Look up a multilevel subpath of the '/content/first-node' alias.
    $processed = $this->pathProcessor->processInbound('/content/first-node/kittens/more-kittens', Request::create('/content/first-node/kittens/more-kittens'));
    $this->assertEquals('/node/1/kittens/more-kittens', $processed);

    // Look up a subpath of the 'content/first-node-test' alias.
    $processed = $this->pathProcessor->processInbound('/content/first-node-test/a', Request::create('/content/first-node-test/a'));
    $this->assertEquals('/node/1/test/a', $processed);

    // Look up an admin sub-path of the 'content/first-node' alias without
    // disabling sub-paths for admin.
    $processed = $this->pathProcessor->processInbound('/content/first-node/edit', Request::create('/content/first-node/edit'));
    $this->assertEquals('/node/1/edit', $processed);

    // Look up an admin sub-path without disabling sub-paths for admin.
    $processed = $this->pathProcessor->processInbound('/malicious-path/modules', Request::create('/malicious-path/modules'));
    $this->assertEquals('/admin/modules', $processed);
  }

  /**
   * @covers ::processInbound
   */
  public function testInboundPathProcessorMaxDepth(): void {
    $this->pathValidator->expects($this->any())
      ->method('getUrlIfValidWithoutAccessCheck')
      ->willReturn(new Url('any_route'));
    $this->subPathautoSettings->expects($this->exactly(2))
      ->method('get')
      ->willReturn(3);

    $this->aliasProcessor->expects($this->any())
      ->method('processInbound')
      ->willReturnCallback([$this, 'pathAliasCallback']);

    // Subpath shouldn't be processed since the iterations has been limited.
    $processed = $this->pathProcessor->processInbound('/content/first-node/first/second/third/fourth', Request::create('/content/first-node/first/second/third/fourth'));
    $this->assertEquals('/content/first-node/first/second/third/fourth', $processed);

    // Subpath should be processed when the max depth doesn't exceed.
    $processed = $this->pathProcessor->processInbound('/content/first-node/first/second/third', Request::create('/content/first-node/first/second/third'));
    $this->assertEquals('/node/1/first/second/third', $processed);
  }

  /**
   * @covers ::processInbound
   */
  public function testInboundAlreadyProcessed(): void {
    // The subpath processor should ignore this and not pass it on to the
    // alias processor.
    $processed = $this->pathProcessor->processInbound('node/1', Request::create('/content/first-node'));
    $this->assertEquals('node/1', $processed);
  }

  /**
   * @covers ::processOutbound
   */
  public function testOutboundSubPath(): void {
    $this->aliasProcessor->expects($this->any())
      ->method('processOutbound')
      ->willReturnCallback([$this, 'aliasByPathCallback']);
    $this->subPathautoSettings->expects($this->atLeastOnce())
      ->method('get')
      ->willReturn(0);

    // Look up a subpath of the 'content/first-node' alias.
    $processed = $this->pathProcessor->processOutbound('/node/1/a');
    $this->assertEquals('/content/first-node/a', $processed);

    // Look up a multilevel subpath of the '/content/first-node' alias.
    $processed = $this->pathProcessor->processOutbound('/node/1/kittens/more-kittens');
    $this->assertEquals('/content/first-node/kittens/more-kittens', $processed);

    // Look up a subpath of the 'content/first-node-test' alias.
    $processed = $this->pathProcessor->processOutbound('/node/1/test/a');
    $this->assertEquals('/content/first-node-test/a', $processed);

    // Look up an admin sub-path of the 'content/first-node' alias without
    // disabling sub-paths for admin.
    $processed = $this->pathProcessor->processOutbound('/node/1/edit');
    $this->assertEquals('/content/first-node/edit', $processed);

    // Look up an admin sub-path without disabling sub-paths for admin.
    $processed = $this->pathProcessor->processOutbound('/admin/modules');
    $this->assertEquals('/malicious-path/modules', $processed);
  }

  /**
   * @covers ::processOutbound
   */
  public function testOutboundPathProcessorMaxDepth(): void {
    $this->pathValidator->expects($this->any())
      ->method('getUrlIfValidWithoutAccessCheck')
      ->willReturn(new Url('any_route'));
    $this->subPathautoSettings->expects($this->exactly(2))
      ->method('get')
      ->willReturn(3);

    $this->aliasProcessor->expects($this->any())
      ->method('processOutbound')
      ->willReturnCallback([$this, 'aliasByPathCallback']);

    // Subpath shouldn't be processed since the iterations has been limited.
    $processed = $this->pathProcessor->processOutbound('/node/1/first/second/third/fourth');
    $this->assertEquals('/node/1/first/second/third/fourth', $processed);

    // Subpath should be processed when the max depth doesn't exceed.
    $processed = $this->pathProcessor->processOutbound('/node/1/first/second/third');
    $this->assertEquals('/content/first-node/first/second/third', $processed);
  }

  /**
   * @covers ::processOutbound
   */
  public function testOutboundAbsoluteUrl(): void {
    // The subpath processor should ignore this and not pass it on to the
    // alias processor.
    $options = ['absolute' => TRUE];
    $processed = $this->pathProcessor->processOutbound('node/1', $options);
    $this->assertEquals('node/1', $processed);
  }

  /**
   * Return value callback for getPathByAlias() method on the alias manager.
   *
   * Ensures that by default the call to getPathAlias() will return the first
   * argument that was passed in. We special-case the paths for which we wish it
   * to return an actual alias.
   *
   * @param string $path
   *   The path.
   *
   * @return string
   *   The path represented by the alias, or the alias if no path was found.
   */
  public function pathAliasCallback($path) {
    return $this->aliases[$path] ?? $path;
  }

  /**
   * Return value callback for getAliasByPath() method on the alias manager.
   *
   * @param string $path
   *   The path.
   *
   * @return string
   *   An alias that represents the path, or path if no alias was found.
   */
  public function aliasByPathCallback($path) {
    $aliases = array_flip($this->aliases);
    return $aliases[$path] ?? $path;
  }

}
