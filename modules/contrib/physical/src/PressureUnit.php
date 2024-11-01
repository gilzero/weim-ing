<?php

namespace Drupal\physical;

/**
 * Provides pressure units.
 */
final class PressureUnit implements UnitInterface {

  const PSI = 'psi';
  const BAR = 'bar';
  const PASCAL = 'pa';
  const TORR = 'torr';
  const ATMOSPHERE = 'atm';
  const TATMOSPHERE = 'at';
  const INCHESWATER = 'inh2o';
  const INCHESMERCURY = 'inhg';

  /**
   * {@inheritdoc}
   */
  public static function getLabels() {
    return [
      self::PSI => t('psi'),
      self::BAR => t('bar'),
      self::PASCAL => t('Pa'),
      self::TORR => t('torr'),
      self::ATMOSPHERE => t('atm'),
      self::TATMOSPHERE => t('at'),
      self::INCHESWATER => t('in H₂O'),
      self::INCHESMERCURY => t('in Hg'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getBaseFactor($unit) {
    self::assertExists($unit);
    $factors = [
      self::PSI => '1',
      self::BAR => '14.5037738007',
      self::PASCAL => '0.000145037738007',
      self::TORR => '0.0193367749706',
      self::ATMOSPHERE => '14.695950254',
      self::TATMOSPHERE => '14.2233433343',
      self::INCHESWATER => '0.0360912',
      self::INCHESMERCURY => '0.491154',
    ];

    return $factors[$unit];
  }

  /**
   * {@inheritdoc}
   */
  public static function getBaseUnit() {
    return self::PSI;
  }

  /**
   * {@inheritdoc}
   */
  public static function assertExists($unit) {
    $allowed_units = [self::PSI, self::BAR, self::PASCAL, self::TORR,
      self::ATMOSPHERE, self::TATMOSPHERE, self::INCHESWATER, self::INCHESMERCURY,
    ];
    if (!in_array($unit, $allowed_units)) {
      throw new \InvalidArgumentException(sprintf('Invalid pressure unit "%s" provided.', $unit));
    }
  }

}
