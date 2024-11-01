<?php

namespace Drupal\Tests\vertex_ai_search\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test search pages form.
 *
 * @group vertex_ai_search
 */
class VertexAiSearchPageMgmtTest extends BrowserTestBase {

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  protected static $modules = ['user', 'token', 'vertex_ai_search'];

  /**
   * Make sure search page form has google_json_api option.
   */
  public function testOptionOnSearchPagesForm() {

    $admin_user = $this->drupalCreateUser([
      'administer search',
    ]);

    $web_assert = $this->assertSession();

    // Login as our account.
    $this->drupalLogin($admin_user);

    // Get the search pages form path from the route.
    $searchPageFormPath = Url::fromRoute('entity.search_page.collection');

    // Navigate to the search pages form.
    $this->drupalGet($searchPageFormPath);

    // Assure we loaded search pages page with proper permissions.
    $web_assert->statusCodeEquals(200);

    $searchTypeOptions = $this->getOptions('search_type');

    $this->assertTrue(in_array('vertex_ai_search', array_flip($searchTypeOptions)), 'Vertex AI search page option available.');

    // Logout of the account.
    $this->drupalLogout($admin_user);

  }

  /**
   * Create search page using search page add form.
   */
  public function testCreateSearchPage() {

    $admin_user = $this->drupalCreateUser([
      'administer search',
    ]);

    $web_assert = $this->assertSession();

    // Login as our account.
    $this->drupalLogin($admin_user);

    // Get the search pages form path from the route.
    $searchPageFormPath = Url::fromRoute('search.add_type',
      ['search_plugin_id' => 'vertex_ai_search']
    );

    // Navigate to the search add page form.
    $this->drupalGet($searchPageFormPath);

    // Assure we loaded search page add page with proper permissions.
    $web_assert->statusCodeEquals(200);

    $this->submitForm([
      'label' => 'Sample Vertex AI Search Page',
      'id' => 'sample_vertex_ai_search_page',
      'path' => 'sample_vertex_ai_search',
      'service_account_credentials_file' => './testFile.txt',
      'google_cloud_project_id' => 'abcdefxyz',
      'google_cloud_location' => 'global',
      'vertex_ai_data_store_id' => 'data_store_id_sample',
      'vertex_ai_serving_config' => 'default_search',
      'resultsPerPage' => 20,
      'autocomplete_source' => 'vertex_autocomplete_simple',
    ], 'Save');

    $this->container->get('router.builder')->rebuild();

    $searchPages = Url::fromRoute('entity.search_page.collection');

    // Navigate to the new search page.
    $this->drupalGet($searchPages);

    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains("Sample Vertex AI Search Page");

    // Logout of the account.
    $this->drupalLogout($admin_user);

  }

}
