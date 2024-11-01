<?php

namespace Drupal\Tests\copyright_footer\Functional;

use Drupal\block\Entity\Block;
use Drupal\copyright_footer\Plugin\Block\CopyrightFooter;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test case for a Copyright Footer block.
 *
 * @group Copyright Footer
 */
class CopyrightFooterTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'copyright_footer',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Permissions.
   *
   * @var array
   */
  protected array $perms = [
    'access content',
    'administer blocks',
  ];

  /**
   * A node.
   *
   * @var \Drupal\node\Entity\Node
   */
  public const ORGANIZATION_URL = 'https://git.drupalcode.org/project/copyright_footer';

  /**
   * A version URL.
   *
   * @var string
   */
  public const VERSION_URL = 'https://git.drupalcode.org/project/copyright_footer';

  /**
   * A year of the origin.
   *
   * @var string
   */
  public string $yearOrigin = '2018';

  /**
   * A year to date.
   *
   * @var string
   */
  public string $yearToDate = '2038';

  /**
   * A organization name.
   *
   * @var string
   */
  protected string $organizationName = 'Copyright Footer';

  /**
   * A year.
   *
   * @var string
   */
  protected string $year;

  /**
   * A node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * A URL of the node.
   *
   * @var string
   */
  protected string $nodeUrl = '';

  /**
   * Set up test.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  protected function setUp(): void {
    parent::setUp();

    $user = $this->drupalCreateUser($this->perms);
    $this->drupalLogin($user);

    // Create a dummy node.
    $this->createContentType(['type' => 'page']);
    $this->node = $this->drupalCreateNode();

    // Setup configuration dummy data.
    $this->organizationName = $this->randomString();
    $this->year = (new \DateTime())->format('Y');
    $this->yearOrigin = $this->year - random_int(1, 50);
    $this->yearToDate = (int) $this->year + random_int(1, 2038 - (int) $this->year);
    $this->nodeUrl = "/node/{$this->node->id()}";
  }

  /**
   * Test the copyright footer black.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testConfigurationAllBlank(): void {

    // Place a Copyright footer block - All blank.
    $block = $this->placeCopyrightFooterBlock();
    $this->drupalGet($this->nodeUrl);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("Copyright © {$this->year}");
    $block->delete();
  }

  /**
   * Place a Copyright footer block - Organization name only.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testOrganizationNameOnly(): void {

    // Place a Copyright footer block - Organization name only.
    $block = $this->placeCopyrightFooterBlock([
      'organization_name' => $this->organizationName,
    ]);
    $this->drupalGet($this->nodeUrl);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextContains("© {$this->year} {$this->organizationName}");
    $block->delete();
  }

  /**
   * Place a Copyright footer block - Organization w/ URL.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testOrganizationWithUrl(): void {
    $block = $this->placeCopyrightFooterBlock([
      'organization_name' => $this->organizationName,
      'organization_url' => self::ORGANIZATION_URL,
    ]);
    $this->drupalGet($this->nodeUrl);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextContains(strip_tags("© {$this->year} {$this->organizationName}"));

    $this->clickLink($this->organizationName);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('copyright_footer');
    $block->delete();
  }

  /**
   * Place a Copyright footer block - Year origin only.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testYearOriginOnly(): void {

    $block = $this->placeCopyrightFooterBlock([
      'year_origin' => $this->yearOrigin,
    ]);
    $this->drupalGet($this->nodeUrl);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextContains("Copyright © {$this->yearOrigin}-{$this->year}");
    $block->delete();
  }

  /**
   * Place a Copyright footer block - Year origin only (current year).
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCurrentYearAsOriginOnly(): void {

    $date = new \DateTime();
    $current_year_origin = $date->format('Y');
    $block = $this->placeCopyrightFooterBlock([
      'year_origin' => $current_year_origin,
    ]);
    $this->drupalGet($this->nodeUrl);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextContains("Copyright © {$current_year_origin}");
    $block->delete();
  }

  /**
   * Place a Copyright footer block - Year origin and year to date.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testYearOriginAndYearToDate(): void {
    $block = $this->placeCopyrightFooterBlock([
      'year_origin' => $this->yearOrigin,
      'year_to_date' => $this->yearToDate,
    ]);
    $this->drupalGet($this->nodeUrl);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextContains("Copyright © {$this->yearOrigin}-{$this->yearToDate}");
    $block->delete();
  }

  /**
   * Place a Copyright footer block - Version only.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testVersionOnly(): void {

    // Place a Copyright footer block - Version only.
    $block = $this->placeCopyrightFooterBlock([
      'version' => CopyrightFooter::VERSION,
    ]);
    $this->drupalGet($this->nodeUrl);
    $this->assertSession()->statusCodeEquals(200);
    $version = CopyrightFooter::VERSION;
    $this->assertSession()
      ->pageTextContains("Copyright © {$this->year} ver.$version");
    $block->delete();
  }

  /**
   * Place a Copyright footer block - Version w/ URL.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testVersionWithUrl(): void {

    $block = $this->placeCopyrightFooterBlock([
      'version' => CopyrightFooter::VERSION,
      'version_url' => self::VERSION_URL,
    ]);
    $this->drupalGet($this->nodeUrl);
    $this->assertSession()->statusCodeEquals(200);
    $version = CopyrightFooter::VERSION;
    $this->assertSession()
      ->pageTextContains("Copyright © {$this->year} ver.$version");
    // After clicking the link `CopyrightFooter::VERSION`, it is supposed to go
    // to https://git.drupalcode.org/project/copyright_footer.git
    $this->clickLink(CopyrightFooter::VERSION);
    $this->assertSession()->statusCodeEquals(200);
    // 'March 02, 2019' is the `Created on` date of drupal/copyright_footer at:
    // https://git.drupalcode.org/project/copyright_footer.git
    $this->assertSession()->pageTextContains('March 02, 2019');
    $block->delete();
  }

  /**
   * Place a Copyright footer block - Full parameters.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFullParameters(): void {

    $block = $this->placeCopyrightFooterBlock([
      'organization_name' => $this->organizationName,
      'organization_url' => self::ORGANIZATION_URL,
      'year_origin' => $this->yearOrigin,
      '@year_to_date' => $this->yearToDate,
      'version' => CopyrightFooter::VERSION,
      'version_url' => self::VERSION_URL,
    ]);
    $this->drupalGet($this->nodeUrl);
    $this->assertSession()->statusCodeEquals(200);
    $version = CopyrightFooter::VERSION;
    $this->assertSession()->pageTextContains("© {$this->yearOrigin}-{$this->year} {$this->organizationName} ver.$version");

    $this->clickLink($this->organizationName);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('copyright_footer');

    $this->drupalGet($this->nodeUrl);
    // After clicking the link `CopyrightFooter::VERSION`, it is supposed to go
    // to https://git.drupalcode.org/project/copyright_footer.git
    $this->clickLink(CopyrightFooter::VERSION);
    $this->assertSession()->statusCodeEquals(200);
    // 'March 02, 2019' is the `Created on` date of drupal/copyright_footer at:
    // https://git.drupalcode.org/project/copyright_footer.git
    $this->assertSession()->pageTextContains('March 02, 2019');
    $block->delete();
  }

  /**
   * Create a Copyright Footer block.
   *
   * @return \Drupal\block\Entity\Block
   *   The Copyright Footer block object.
   */
  private function placeCopyrightFooterBlock($settings = []): Block {
    return $this->drupalPlaceBlock('copyright_footer', [
      'organization_name' => $settings['organization_name'] ?? '',
      'organization_url' => $settings['organization_url'] ?? '',
      'year_origin' => $settings['year_origin'] ?? '',
      'year_to_date' => $settings['year_to_date'] ?? '',
      'version' => $settings['version'] ?? '',
      'version_url' => $settings['version_url'] ?? '',
    ]);
  }

}
