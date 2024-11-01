<?php

declare(strict_types=1);

namespace Drupal\Tests\statistics\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * This is a test of the logic used in statistics.php to filter invalid nids.
 *
 * It is not a test of that code per se, so needs to be kept in sync.
 *
 * @group statistics
 */
class StatisticsNidFilterTest extends UnitTestCase {

  const NORMAL = 25;

  const MAX_MYSQL_INT10 = 4294967295;

  const BIG = 9223372036854775807;

  const TOO_BIG = 9223372036854775808;

  /**
   * This provider is also used in StatisticsNidFilterTest for consistency.
   *
   * @return array
   *   - input
   *   - filter result
   *   - DB result
   */
  public static function providerTestNidFilter(): array {
    return [
      "string" => [
        "foo",
        FALSE,
        FALSE,
      ],
      "float" => [
        "1.5",
        FALSE,
        FALSE,
      ],
      "negative" => [
        -1,
        FALSE,
        FALSE,
      ],
      "zero" => [
        0,
        FALSE,
        FALSE,
      ],
      "normal" => [
        self::NORMAL,
        self::NORMAL,
        self::NORMAL,
      ],
      "max for mysql int(10)" => [
        self::MAX_MYSQL_INT10,
        self::MAX_MYSQL_INT10,
        self::MAX_MYSQL_INT10,
      ],
      "too big for mysql int(10)" => [
        self::MAX_MYSQL_INT10 + 1,
        self::MAX_MYSQL_INT10 + 1,
        FALSE,
      ],
      "max for int64" => [
        self::BIG,
        self::BIG,
        FALSE,
      ],
      "too big for int64" => [
        self::TOO_BIG,
        FALSE,
        FALSE,
      ],
    ];
  }

  /**
   * Test the logic in the statistics.php filter code.
   *
   * @dataProvider providerTestNidFilter
   */
  public function testNidFilter(mixed $input, mixed $expected) {
    $actual = filter_var($input, FILTER_VALIDATE_INT,
      ['options' => ['min_range' => 1]]
    );
    $this->assertSame($expected, $actual);
  }

}
