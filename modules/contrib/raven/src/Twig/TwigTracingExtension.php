<?php

declare(strict_types=1);

namespace Drupal\raven\Twig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanContext;
use Twig\Extension\AbstractExtension;
use Twig\Profiler\NodeVisitor\ProfilerNodeVisitor;
use Twig\Profiler\Profile;

/**
 * Provides Twig performance tracing.
 */
class TwigTracingExtension extends AbstractExtension {

  /**
   * The currently active spans.
   *
   * @var \SplObjectStorage<\Twig\Profiler\Profile, \Sentry\Tracing\Span>
   */
  private \SplObjectStorage $spans;

  /**
   * The currently active parents.
   *
   * @var \SplObjectStorage<\Twig\Profiler\Profile, \Sentry\Tracing\Span>
   */
  private \SplObjectStorage $parents;

  /**
   * Extension constructor.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory) {
    $this->spans = new \SplObjectStorage();
    $this->parents = new \SplObjectStorage();
  }

  /**
   * This method is called before execution.
   *
   * @param \Twig\Profiler\Profile $profile
   *   The profiling data.
   */
  public function enter(Profile $profile): void {
    if (!$this->configFactory->get('raven.settings')->get('twig_tracing')) {
      return;
    }

    $parent = SentrySdk::getCurrentHub()->getSpan();
    if (!$parent || !$parent->getSampled()) {
      return;
    }

    $spanContext = SpanContext::make()
      ->setOrigin('auto.template')
      ->setOp('template.render')
      ->setDescription($this->getSpanDescription($profile));

    $this->spans[$profile] = $parent->startChild($spanContext);
    $this->parents[$profile] = $parent;
    SentrySdk::getCurrentHub()->setSpan($this->spans[$profile]);
  }

  /**
   * This method is called when execution is finished.
   *
   * @param \Twig\Profiler\Profile $profile
   *   The profiling data.
   */
  public function leave(Profile $profile): void {
    if (!isset($this->spans[$profile])) {
      return;
    }

    $this->spans[$profile]->finish();
    SentrySdk::getCurrentHub()->setSpan($this->parents[$profile]);

    unset($this->spans[$profile], $this->parents[$profile]);
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors(): array {
    return [
      new ProfilerNodeVisitor(self::class),
    ];
  }

  /**
   * Gets a short description for the span.
   *
   * @param \Twig\Profiler\Profile $profile
   *   The profiling data.
   */
  private function getSpanDescription(Profile $profile): string {
    switch (TRUE) {
      case $profile->isRoot():
        return $profile->getName();

      case $profile->isTemplate():
        return $profile->getTemplate();

      default:
        return sprintf('%s::%s(%s)', $profile->getTemplate(), $profile->getType(), $profile->getName());
    }
  }

}
