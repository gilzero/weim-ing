<?php

declare(strict_types=1);

namespace Drupal\Tests\flexible_permissions\Unit;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\CalculatedPermissionsItem as CoreCalculatedPermissionsItem;
use Drupal\Core\Session\RefinableCalculatedPermissions as CoreRefinableCalculatedPermissions;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\flexible_permissions\RefinableCalculatedPermissions;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the RefinableCalculatedPermissions class.
 *
 * @coversDefaultClass \Drupal\flexible_permissions\RefinableCalculatedPermissions
 * @group flexible_permissions
 */
class RefinableCalculatedPermissionsTest extends UnitTestCase {

  /**
   * Tests the addition of a calculated permissions item.
   *
   * @covers ::addItem
   * @covers ::getItem
   */
  public function testAddItem(): void {
    $calculated_permissions = new RefinableCalculatedPermissions();
    $scope = 'some_scope';

    $item = new CalculatedPermissionsItem($scope, 'foo', ['bar']);
    $calculated_permissions->addItem($item);
    $this->assertSame($item, $calculated_permissions->getItem($scope, 'foo'), 'Managed to retrieve the calculated permissions item.');

    $item = new CalculatedPermissionsItem($scope, 'foo', ['baz']);
    $calculated_permissions->addItem($item);
    $this->assertEquals(['bar', 'baz'], $calculated_permissions->getItem($scope, 'foo')->getPermissions(), 'Adding a calculated permissions item that was already in the list merges them.');

    $item = new CalculatedPermissionsItem($scope, 'foo', ['cat'], TRUE);
    $calculated_permissions->addItem($item);
    $this->assertEquals([], $calculated_permissions->getItem($scope, 'foo')->getPermissions(), 'Merging in a calculated permissions item with admin rights empties the permissions.');
    $this->assertTrue($calculated_permissions->getItem($scope, 'foo')->isAdmin(), 'Merging in a calculated permissions item with admin rights flags the result as having admin rights.');
  }

  /**
   * Tests the overwriting of a calculated permissions item.
   *
   * @covers ::addItem
   * @covers ::getItem
   * @depends testAddItem
   */
  public function testAddItemOverwrite(): void {
    $calculated_permissions = new RefinableCalculatedPermissions();
    $scope = 'some_scope';

    $item = new CalculatedPermissionsItem($scope, 'foo', ['bar']);
    $calculated_permissions->addItem($item);

    $item = new CalculatedPermissionsItem($scope, 'foo', ['baz']);
    $calculated_permissions->addItem($item, TRUE);
    $this->assertEquals(['bar', 'baz'], $calculated_permissions->getItem($scope, 'foo')->getPermissions(), 'Could not overwrite item in build mode.');

    $calculated_permissions->disableBuildMode();
    $calculated_permissions->addItem($item, TRUE);
    $this->assertEquals(['baz'], $calculated_permissions->getItem($scope, 'foo')->getPermissions(), 'Successfully overwrote an item that was already in the list.');
  }

  /**
   * Tests the removal of a calculated permissions item.
   *
   * @covers ::removeItem
   * @depends testAddItem
   */
  public function testRemoveItem(): void {
    $scope = 'some_scope';
    $item = new CalculatedPermissionsItem($scope, 'foo', ['bar']);

    $calculated_permissions = new RefinableCalculatedPermissions();
    $calculated_permissions->addItem($item);

    $calculated_permissions->removeItem($scope, 'foo');
    $this->assertNotFalse($calculated_permissions->getItem($scope, 'foo'), 'Could not remove item in build mode.');

    $calculated_permissions->disableBuildMode();
    $calculated_permissions->removeItem($scope, 'foo');
    $this->assertFalse($calculated_permissions->getItem($scope, 'foo'), 'Could not retrieve a removed item.');
  }

  /**
   * Tests the removal of all calculated permissions items.
   *
   * @covers ::removeItems
   * @depends testAddItem
   */
  public function testRemoveItems(): void {
    $scope = 'some_scope';
    $item = new CalculatedPermissionsItem($scope, 'foo', ['bar']);

    $calculated_permissions = new RefinableCalculatedPermissions();
    $calculated_permissions->addItem($item);

    $calculated_permissions->removeItems();
    $this->assertNotFalse($calculated_permissions->getItem($scope, 'foo'), 'Could not remove items in build mode.');

    $calculated_permissions->disableBuildMode();
    $calculated_permissions->removeItems();
    $this->assertFalse($calculated_permissions->getItem($scope, 'foo'), 'Could not retrieve a removed item.');
  }

  /**
   * Tests the removal of calculated permissions items by scope.
   *
   * @covers ::removeItemsByScope
   * @depends testAddItem
   */
  public function testRemoveItemsByScope(): void {
    $scope_a = 'cat';
    $scope_b = 'dog';

    $item_a = new CalculatedPermissionsItem($scope_a, 'foo', ['bar']);
    $item_b = new CalculatedPermissionsItem($scope_b, 1, ['baz']);

    $calculated_permissions = new RefinableCalculatedPermissions();
    $calculated_permissions
      ->addItem($item_a)
      ->addItem($item_b)
      ->removeItemsByScope($scope_a);
    $this->assertNotFalse($calculated_permissions->getItem($scope_a, 'foo'), 'Could not remove items in build mode.');

    $calculated_permissions->disableBuildMode();
    $calculated_permissions->removeItemsByScope($scope_a);
    $this->assertFalse($calculated_permissions->getItem($scope_a, 'foo'), 'Could not retrieve a removed item.');
    $this->assertNotFalse($calculated_permissions->getItem($scope_b, 1), 'Untouched scope item was found.');
  }

  /**
   * Tests merging in another CalculatedPermissions object.
   *
   * @covers ::merge
   * @depends testAddItem
   */
  public function testMerge(): void {
    $scope = 'some_scope';

    $cache_context_manager = $this->prophesize(CacheContextsManager::class);
    $cache_context_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_contexts_manager')->willReturn($cache_context_manager->reveal());
    \Drupal::setContainer($container->reveal());

    $item_a = new CalculatedPermissionsItem($scope, 'foo', ['baz']);
    $item_b = new CalculatedPermissionsItem($scope, 'foo', ['bob', 'charlie']);
    $item_c = new CalculatedPermissionsItem($scope, 'bar', []);
    $item_d = new CalculatedPermissionsItem($scope, 'baz', []);

    $calculated_permissions = new RefinableCalculatedPermissions();
    $calculated_permissions
      ->addItem($item_a)
      ->addItem($item_c)
      ->addCacheContexts(['foo'])
      ->addCacheTags(['foo']);

    $other = new RefinableCalculatedPermissions();
    $other
      ->addItem($item_b)
      ->addItem($item_d)
      ->addCacheContexts(['bar'])
      ->addCacheTags(['bar']);

    $calculated_permissions->merge($other);
    $this->assertNotFalse($calculated_permissions->getItem($scope, 'bar'), 'Original item that did not conflict was kept.');
    $this->assertNotFalse($calculated_permissions->getItem($scope, 'baz'), 'Incoming item that did not conflict was added.');
    $this->assertSame(['baz', 'bob', 'charlie'], $calculated_permissions->getItem($scope, 'foo')->getPermissions(), 'Permissions were merged properly.');
    $this->assertEqualsCanonicalizing(['bar', 'foo'], $calculated_permissions->getCacheContexts(), 'Cache contexts were merged properly');
    $this->assertEqualsCanonicalizing(['bar', 'foo'], $calculated_permissions->getCacheTags(), 'Cache tags were merged properly');
  }

  /**
   * Tests the conversion to Access Policy API.
   *
   * @covers ::toCore
   */
  public function testToCore(): void {
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager->reveal());
    \Drupal::setContainer($container);

    $item_old = new CalculatedPermissionsItem('scope', 'foo', ['baz']);
    $calculated_permissions = (new RefinableCalculatedPermissions())
      ->addItem($item_old)
      ->addCacheTags(['24'])
      ->addCacheContexts(['Oct'])
      ->mergeCacheMaxAge(1986);

    $converted = $calculated_permissions->toCore();
    $this->assertSame($calculated_permissions->getCacheTags(), $converted->getCacheTags());
    $this->assertSame($calculated_permissions->getCacheContexts(), $converted->getCacheContexts());
    $this->assertSame($calculated_permissions->getCacheMaxAge(), $converted->getCacheMaxAge());

    $item_new = $converted->getItem('scope', 'foo');
    $this->assertInstanceOf(CoreCalculatedPermissionsItem::class, $item_new);
    $this->assertSame($item_old->getScope(), $item_new->getScope());
    $this->assertSame($item_old->getIdentifier(), $item_new->getIdentifier());
    $this->assertSame($item_old->getPermissions(), $item_new->getPermissions());
    $this->assertSame($item_old->isAdmin(), $item_new->isAdmin());
  }

  /**
   * Tests the conversion from the Access Policy API.
   *
   * @covers ::fromCore
   */
  public function testFromCore(): void {
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager->reveal());
    \Drupal::setContainer($container);

    $item_old = new CoreCalculatedPermissionsItem(['baz'], FALSE, 'scope', 'foo',);
    $calculated_permissions = (new CoreRefinableCalculatedPermissions())
      ->addItem($item_old)
      ->addCacheTags(['24'])
      ->addCacheContexts(['Oct'])
      ->mergeCacheMaxAge(1986);

    $converted = RefinableCalculatedPermissions::fromCore($calculated_permissions);
    $this->assertSame($calculated_permissions->getCacheTags(), $converted->getCacheTags());
    $this->assertSame($calculated_permissions->getCacheContexts(), $converted->getCacheContexts());
    $this->assertSame($calculated_permissions->getCacheMaxAge(), $converted->getCacheMaxAge());

    $item_new = $converted->getItem('scope', 'foo');
    $this->assertInstanceOf(CalculatedPermissionsItem::class, $item_new);
    $this->assertSame($item_old->getScope(), $item_new->getScope());
    $this->assertSame($item_old->getIdentifier(), $item_new->getIdentifier());
    $this->assertSame($item_old->getPermissions(), $item_new->getPermissions());
    $this->assertSame($item_old->isAdmin(), $item_new->isAdmin());
  }

}
