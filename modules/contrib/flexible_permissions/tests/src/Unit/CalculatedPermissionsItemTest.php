<?php

declare(strict_types=1);

namespace Drupal\Tests\flexible_permissions\Unit;

use Drupal\Core\Session\CalculatedPermissionsItem as CoreCalculatedPermissionsItem;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CalculatedPermissionsItem value object.
 *
 * @coversDefaultClass \Drupal\flexible_permissions\CalculatedPermissionsItem
 * @group flexible_permissions
 */
class CalculatedPermissionsItemTest extends UnitTestCase {

  /**
   * Tests that the object values were set in the constructor.
   *
   * @covers ::__construct
   * @covers ::getIdentifier
   * @covers ::getScope
   * @covers ::getPermissions
   * @covers ::isAdmin
   */
  public function testConstructor(): void {
    $scope = 'some_scope';

    $item = new CalculatedPermissionsItem($scope, 'foo', ['bar', 'baz', 'bar'], FALSE);
    $this->assertEquals($scope, $item->getScope(), 'Scope name was set correctly.');
    $this->assertEquals('foo', $item->getIdentifier(), 'Scope identifier was set correctly.');
    $this->assertEquals(['bar', 'baz'], $item->getPermissions(), 'Permissions were made unique and set correctly.');
    $this->assertFalse($item->isAdmin(), 'Admin flag was set correctly');

    $item = new CalculatedPermissionsItem($scope, 'foo', ['bar', 'baz', 'bar'], TRUE);
    $this->assertEquals([], $item->getPermissions(), 'Permissions were emptied out for an admin item.');
    $this->assertTrue($item->isAdmin(), 'Admin flag was set correctly');
  }

  /**
   * Tests the permission check when the admin flag is not set.
   *
   * @covers ::hasPermission
   * @depends testConstructor
   */
  public function testHasPermission(): void {
    $item = new CalculatedPermissionsItem('some_scope', 'foo', ['bar'], FALSE);
    $this->assertFalse($item->hasPermission('baz'), 'Missing permission was not found.');
    $this->assertTrue($item->hasPermission('bar'), 'Existing permission was found.');
  }

  /**
   * Tests the permission check when the admin flag is set.
   *
   * @covers ::hasPermission
   * @depends testConstructor
   */
  public function testHasPermissionWithAdminFlag(): void {
    $item = new CalculatedPermissionsItem('some_scope', 'foo', ['bar'], TRUE);
    $this->assertTrue($item->hasPermission('baz'), 'Missing permission was found.');
    $this->assertTrue($item->hasPermission('bar'), 'Existing permission was found.');
  }

  /**
   * Tests the conversion to Access Policy API.
   *
   * @covers ::toCore
   * @depends testConstructor
   */
  public function testToCore(): void {
    $item = new CalculatedPermissionsItem('some_scope', 'foo', ['bar'], TRUE);
    $converted = $item->toCore();
    $this->assertSame($item->getScope(), $converted->getScope());
    $this->assertSame($item->getIdentifier(), $converted->getIdentifier());
    $this->assertSame($item->getPermissions(), $converted->getPermissions());
    $this->assertSame($item->isAdmin(), $converted->isAdmin());
  }

  /**
   * Tests the conversion from the Access Policy API.
   *
   * @covers ::fromCore
   */
  public function testFromCore(): void {
    $item = new CoreCalculatedPermissionsItem(['bar'], TRUE, 'some_scope', 'foo');
    $converted = CalculatedPermissionsItem::fromCore($item);
    $this->assertSame($item->getScope(), $converted->getScope());
    $this->assertSame($item->getIdentifier(), $converted->getIdentifier());
    $this->assertSame($item->getPermissions(), $converted->getPermissions());
    $this->assertSame($item->isAdmin(), $converted->isAdmin());
  }

}
