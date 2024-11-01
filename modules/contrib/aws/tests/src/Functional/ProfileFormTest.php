<?php

namespace Drupal\Tests\aws\Functional;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the AWS overview page.
 *
 * @group aws
 */
class ProfileFormTest extends FunctionalTestBase {

  /**
   * Tests that only users with the correct permission can access the form.
   */
  public function testProfileFormAccess() {
    $url = Url::fromRoute('entity.aws_profile.add_form');

    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    $this->drupalLogout();
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

  /**
   * Tests the functionality of the form.
   */
  public function testProfileForm() {
    $this->drupalGet(Url::fromRoute('entity.aws_profile.add_form'));

    $name = $this->randomMachineName();
    $this->getSession()->getPage()->fillField('name', $name);
    $this->getSession()->getPage()->fillField('id', strtolower($name));

    $this->getSession()->getPage()->fillField('aws_access_key_id', $this->randomString());
    $this->getSession()->getPage()->fillField('aws_secret_access_key', $this->randomString());

    $this->getSession()->getPage()->pressButton($this->t('Save'));
    $this->assertSession()->pageTextContains("The $name profile has been created.");
  }

  /**
   * Tests the functionality of the form for role based auth.
   */
  public function testProfileFormWithRole() {
    $this->drupalGet(Url::fromRoute('entity.aws_profile.add_form'));

    $name = $this->randomMachineName();
    $this->getSession()->getPage()->fillField('name', $name);
    $this->getSession()->getPage()->fillField('id', strtolower($name));

    $this->getSession()->getPage()->fillField('aws_role_arn', $this->randomString());
    $this->getSession()->getPage()->fillField('aws_role_session_name', $this->randomString());

    $this->getSession()->getPage()->pressButton($this->t('Save'));
    $this->assertSession()->pageTextContains("The $name profile has been created.");
  }

  /**
   * Tests the required fields.
   */
  public function testRequiredFields() {
    $this->drupalGet(Url::fromRoute('entity.aws_profile.add_form'));
    $this->getSession()->getPage()->pressButton($this->t('Save'));

    $this->assertSession()->pageTextContains('Profile name field is required.');
    $this->assertSession()->pageTextContains('Machine-readable name field is required.');
  }

}
