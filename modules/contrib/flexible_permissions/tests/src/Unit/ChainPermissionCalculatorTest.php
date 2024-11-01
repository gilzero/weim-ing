<?php

namespace Drupal\Tests\flexible_permissions\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Cache\VariationCacheInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\flexible_permissions\CalculatedPermissions;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\flexible_permissions\CalculatedPermissionsScopeException;
use Drupal\flexible_permissions\ChainPermissionCalculator;
use Drupal\flexible_permissions\PermissionCalculatorBase;
use Drupal\flexible_permissions\RefinableCalculatedPermissions;
use Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the CalculatedPermissions value object.
 *
 * @coversDefaultClass \Drupal\flexible_permissions\ChainPermissionCalculator
 * @group flexible_permissions
 */
class ChainPermissionCalculatorTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $cache_context_manager = $this->prophesize(CacheContextsManager::class);
    $cache_context_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_contexts_manager')->willReturn($cache_context_manager->reveal());
    \Drupal::setContainer($container->reveal());
  }

  /**
   * Tests that calculators are properly added and returned.
   *
   * @covers ::addCalculator
   * @covers ::getCalculators
   */
  public function testAddCalculator() {
    $chain_calculator = $this->setUpChainCalculator();
    $calculators = [
      new FooScopeCalculator(),
      new BarScopeCalculator(),
      new BarAlterCalculator(),
    ];

    foreach ($calculators as $calculator) {
      $chain_calculator->addCalculator($calculator);
    }

    $this->assertEquals($calculators, $chain_calculator->getCalculators(), 'The added calculators match the returned ones.');
  }

  /**
   * Tests that the persistent cache contexts are returned properly.
   *
   * @covers ::getPersistentCacheContexts
   */
  public function testGetPersistentCacheContexts() {
    $chain_calculator = $this->setUpChainCalculator();

    foreach ([new FooScopeCalculator(), new BarScopeCalculator(), new BarAlterCalculator()] as $calculator) {
      $chain_calculator->addCalculator($calculator);
    }

    $this->assertEquals(['foo', 'bar', 'baz'], $chain_calculator->getPersistentCacheContexts('anything'), 'Cache contexts match!');
  }

  /**
   * Tests that calculators are properly processed.
   *
   * @covers ::calculatePermissions
   */
  public function testCalculatePermissions() {
    $account = $this->prophesize(AccountInterface::class)->reveal();
    $calculator = new BarScopeCalculator();

    $chain_calculator = $this->setUpChainCalculator();
    $chain_calculator->addCalculator($calculator);

    /** @var \Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface $calculator_permissions */
    $calculator_permissions = $calculator->calculatePermissions($account, 'bar');
    $calculator_permissions->addCacheTags(['flexible_permissions']);
    $this->assertEquals(new CalculatedPermissions($calculator_permissions), $chain_calculator->calculatePermissions($account, 'bar'));
  }

  /**
   * Tests that calculators can alter the final result.
   *
   * @covers ::calculatePermissions
   */
  public function testAlterPermissions() {
    $account = $this->prophesize(AccountInterface::class)->reveal();

    $chain_calculator = $this->setUpChainCalculator();
    $chain_calculator->addCalculator(new BarScopeCalculator());
    $chain_calculator->addCalculator(new BarAlterCalculator());

    $actual_permissions = $chain_calculator
      ->calculatePermissions($account, 'bar')
      ->getItem('bar', 1)
      ->getPermissions();

    $this->assertEquals(['foo', 'baz'], $actual_permissions);
  }

  /**
   * Tests that calculators which do nothing are properly processed.
   *
   * @covers ::calculatePermissions
   */
  public function testEmptyCalculator() {
    $account = $this->prophesize(AccountInterface::class)->reveal();
    $calculator = new EmptyCalculator();

    $chain_calculator = $this->setUpChainCalculator();
    $chain_calculator->addCalculator($calculator);

    /** @var \Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface $calculator_permissions */
    $calculator_permissions = $calculator->calculatePermissions($account, 'anything');
    $calculator_permissions->addCacheTags(['flexible_permissions']);
    $calculated_permissions = $chain_calculator->calculatePermissions($account, 'anything');
    $this->assertEquals(new CalculatedPermissions($calculator_permissions), $calculated_permissions);
  }

  /**
   * Tests that everything works if no calculators are present.
   *
   * @covers ::calculatePermissions
   */
  public function testNoCalculators() {
    $account = $this->prophesize(AccountInterface::class)->reveal();
    $chain_calculator = $this->setUpChainCalculator();

    $no_permissions = new RefinableCalculatedPermissions();
    $no_permissions->addCacheTags(['flexible_permissions']);
    $calculated_permissions = $chain_calculator->calculatePermissions($account, 'anything');
    $this->assertEquals(new CalculatedPermissions($no_permissions), $calculated_permissions);
  }

  /**
   * Tests the wrong scope exception.
   *
   * @covers ::calculatePermissions
   */
  public function testWrongScopeException() {
    $chain_calculator = $this->setUpChainCalculator();
    $chain_calculator->addCalculator(new FooScopeCalculator());

    $this->expectException(CalculatedPermissionsScopeException::class);
    $this->expectExceptionMessage(sprintf('The calculator "%s" returned permissions for scopes other than "%s".', FooScopeCalculator::class, 'bar'));
    $chain_calculator->calculatePermissions($this->prophesize(AccountInterface::class)->reveal(), 'bar');
  }

  /**
   * Tests the multiple scopes exception.
   *
   * @covers ::calculatePermissions
   */
  public function testMultipleScopeException() {
    $chain_calculator = $this->setUpChainCalculator();
    $chain_calculator->addCalculator(new FooScopeCalculator());
    $chain_calculator->addCalculator(new BarScopeCalculator());

    $this->expectException(CalculatedPermissionsScopeException::class);
    $this->expectExceptionMessage(sprintf('The calculator "%s" returned permissions for scopes other than "%s".', FooScopeCalculator::class, 'bar'));
    $chain_calculator->calculatePermissions($this->prophesize(AccountInterface::class)->reveal(), 'bar');
  }

  /**
   * Tests if the account switcher switches when user cache context is present.
   *
   * @covers ::calculatePermissions
   * @dataProvider accountSwitcherProvider
   */
  public function testAccountSwitcher($has_user_context) {
    $account = $this->prophesize(AccountInterface::class)->reveal();

    $account_switcher = $this->prophesize(AccountSwitcherInterface::class);
    if ($has_user_context) {
      $account_switcher->switchTo($account)->shouldBeCalledTimes(1);
      $account_switcher->switchBack()->shouldBeCalledTimes(1);
    }
    else {
      $account_switcher->switchTo($account)->shouldNotBeCalled();
      $account_switcher->switchBack()->shouldNotBeCalled();
    }

    $chain_calculator = $this->setUpChainCalculator(NULL, NULL, NULL, $account_switcher->reveal());
    $chain_calculator->addCalculator(new BarScopeCalculator());
    if ($has_user_context) {
      $chain_calculator->addCalculator(new UserContextCalculator());
    }
    $chain_calculator->calculatePermissions($account, 'bar');
  }

  /**
   * Data provider for testAccountSwitcher().
   *
   * @return array
   *   A list of testAccountSwitcher method arguments.
   */
  public function accountSwitcherProvider() {
    $cases['no-user-context'] = [FALSE];
    $cases['user-context'] = [TRUE];
    return $cases;
  }

  /**
   * Tests the caching of the calculated permissions.
   *
   * @covers ::calculatePermissions
   * @dataProvider cachingProvider
   */
  public function testCaching(bool $db_cache_hit, bool $static_cache_hit) {
    if ($static_cache_hit) {
      $this->assertFalse($db_cache_hit, 'DB cache should never be checked when there is a static hit.');
    }

    $account = $this->prophesize(AccountInterface::class)->reveal();
    $scope = 'bar';

    $bar_calculator = new BarScopeCalculator();
    /** @var \Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface $bar_permissions */
    $bar_permissions = $bar_calculator->calculatePermissions($account, $scope);
    $bar_permissions->addCacheTags(['flexible_permissions']);
    $bar_permissions->disableBuildMode();
    $none_refinable_bar_permissions = new CalculatedPermissions($bar_permissions);

    $cache_static = $this->prophesize(VariationCacheInterface::class);
    $cache_db = $this->prophesize(VariationCacheInterface::class);
    if (!$static_cache_hit) {
      if (!$db_cache_hit) {
        $cache_db->get(Argument::cetera())->willReturn(FALSE);
        $cache_db->set(Argument::any(), $bar_permissions, Argument::cetera())->shouldBeCalled();
      }
      else {
        $cache_item = new CacheItem($bar_permissions);
        $cache_db->get(Argument::cetera())->willReturn($cache_item);
        $cache_db->set()->shouldNotBeCalled();
      }
      $cache_static->get(Argument::cetera())->willReturn(FALSE);
      $cache_static->set(Argument::any(), $none_refinable_bar_permissions, Argument::cetera())->shouldBeCalled();
    }
    else {
      $cache_item = new CacheItem($none_refinable_bar_permissions);
      $cache_static->get(Argument::cetera())->willReturn($cache_item);
      $cache_static->set()->shouldNotBeCalled();
    }
    $cache_static = $cache_static->reveal();
    $cache_db = $cache_db->reveal();

    $chain_calculator = $this->setUpChainCalculator($cache_db, $cache_static);
    $chain_calculator->addCalculator($bar_calculator);
    $permissions = $chain_calculator->calculatePermissions($account, $scope);
    $this->assertEquals($none_refinable_bar_permissions, $permissions, 'Cached permission matches calculated.');
  }

  /**
   * Data provider for testCaching().
   *
   * @return array
   *   A list of testAccountSwitcher method arguments.
   */
  public function cachingProvider() {
    $cases = [
      'no-cache' => [FALSE, FALSE],
      'static-cache-hit' => [FALSE, TRUE],
      'db-cache-hit' => [TRUE, FALSE],
    ];
    return $cases;
  }

  /**
   * Sets up the chain calculator.
   *
   * @return \Drupal\flexible_permissions\ChainPermissionCalculatorInterface
   *   The chain calculator.
   */
  protected function setUpChainCalculator(
    VariationCacheInterface $variation_cache = NULL,
    VariationCacheInterface $variation_cache_static = NULL,
    CacheBackendInterface $cache_static = NULL,
    AccountSwitcherInterface $account_switcher = NULL,
  ) {
    if (!isset($variation_cache)) {
      $variation_cache = $this->prophesize(VariationCacheInterface::class);
      $variation_cache->get(Argument::cetera())->willReturn(FALSE);
      $variation_cache->set(Argument::cetera());
      $variation_cache = $variation_cache->reveal();
    }

    if (!isset($variation_cache_static)) {
      $variation_cache_static = $this->prophesize(VariationCacheInterface::class);
      $variation_cache_static->get(Argument::cetera())->willReturn(FALSE);
      $variation_cache_static->set(Argument::cetera());
      $variation_cache_static = $variation_cache_static->reveal();
    }

    if (!isset($cache_static)) {
      $cache_static = $this->prophesize(CacheBackendInterface::class);
      $cache_static->get(Argument::cetera())->willReturn(FALSE);
      $cache_static->set(Argument::cetera())->willReturn(NULL);
      $cache_static = $cache_static->reveal();
    }

    if (!isset($account_switcher)) {
      $account_switcher = $this->prophesize(AccountSwitcherInterface::class)->reveal();
    }

    return new ChainPermissionCalculator(
      $variation_cache,
      $variation_cache_static,
      $cache_static,
      $account_switcher
    );
  }

}

/**
 * Test class that only calculates.
 */
class FooScopeCalculator extends PermissionCalculatorBase {

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, $scope) {
    /** @var \Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface $calculated_permissions */
    $calculated_permissions = parent::calculatePermissions($account, $scope);
    return $calculated_permissions->addItem(new CalculatedPermissionsItem('foo', 1, ['foo', 'bar'], TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts($scope) {
    return ['foo'];
  }

}

/**
 * Test class that only calculates.
 */
class BarScopeCalculator extends PermissionCalculatorBase {

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, $scope) {
    /** @var \Drupal\flexible_permissions\RefinableCalculatedPermissionsInterface $calculated_permissions */
    $calculated_permissions = parent::calculatePermissions($account, $scope);
    return $calculated_permissions->addItem(new CalculatedPermissionsItem('bar', 1, ['foo', 'bar']));
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts($scope) {
    return ['bar'];
  }

}

/**
 * Test class that only alters.
 */
class BarAlterCalculator extends PermissionCalculatorBase {

  /**
   * {@inheritdoc}
   */
  public function alterPermissions(RefinableCalculatedPermissionsInterface $calculated_permissions) {
    parent::alterPermissions($calculated_permissions);

    foreach ($calculated_permissions->getItemsByScope('bar') as $item) {
      $permissions = $item->getPermissions();

      if (($key = array_search('bar', $permissions, TRUE)) !== FALSE) {
        $permissions[$key] = 'baz';

        $new_item = new CalculatedPermissionsItem(
          $item->getScope(),
          $item->getIdentifier(),
          $permissions
        );

        $calculated_permissions->addItem($new_item, TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts($scope) {
    return ['baz'];
  }

}

/**
 * Test class that does nothing.
 */
class EmptyCalculator extends PermissionCalculatorBase {

}

/**
 * Test class that only adds a cache context.
 */
class UserContextCalculator extends PermissionCalculatorBase {

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts($scope) {
    return ['user'];
  }

}

/**
 * Test class representing a cache entry.
 */
class CacheItem {

  /**
   * The cache item's data.
   *
   * @var mixed
   */
  public $data;

  /**
   * Constructs a new CacheItem object.
   *
   * @param mixed $data
   *   Any data to assign to the fake cache item.
   */
  public function __construct($data) {
    $this->data = $data;
  }

}
