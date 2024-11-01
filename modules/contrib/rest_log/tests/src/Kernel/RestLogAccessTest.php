<?php

namespace Drupal\Tests\rest_log\Kernel;

use Symfony\Component\HttpFoundation\Request;

/**
 * Tests rest log access.
 *
 * @group rest_log
 */
class RestLogAccessTest extends RestLogKernelTestBase {

  /**
   * Test rest log list view page access.
   */
  public function testRestLogViewAccess() {
    // Test access for user with expected permission.
    $account = $this->createUser(['access rest log list']);
    $this->setCurrentUser($account);
    $request = Request::create('/admin/reports/rest_log');
    $response = $this->httpKernel->handle($request);
    $this->assertEquals(200, $response->getStatusCode(), 'User have access to rest log view.');

    // Test access for user with no expected permission.
    $another_account = $this->createUser();
    $this->setCurrentUser($another_account);
    $request = Request::create('/admin/reports/rest_log');
    $response = $this->httpKernel->handle($request);
    $this->assertTrue($response->isForbidden(), 'User have no access to rest log view.');
  }

  /**
   * Test single rest log details page.
   */
  public function testRestLogDetailsPageAccess() {
    // Send request to create a test log.
    $this->sendRestRequest('/rest-log-test');
    $rest_log = $this->getNewestResponseLog();
    $rest_log_url = $rest_log->toUrl();

    // Test access for user with expected permission.
    $account = $this->createUser(['access rest log list']);
    $this->setCurrentUser($account);
    $request = Request::create($rest_log_url->toString());
    $response = $this->httpKernel->handle($request);
    $this->assertEquals(200, $response->getStatusCode(), 'User have access to rest log details page.');

    // Test access for user with no expected permission.
    $another_account = $this->createUser();
    $this->setCurrentUser($another_account);
    $request = Request::create($rest_log_url->toString());
    $response = $this->httpKernel->handle($request);
    $this->assertTrue($response->isForbidden(), 'User have no access to rest log view.');
  }

}
