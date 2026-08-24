<?php

declare(strict_types=1);

/*
 * This file is part of the smnandre/packapi package.
 *
 * (c) Simon Andre <smn.andre@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PackApi\Tests\Inspector;

use PackApi\Inspector\ActivityInspector;
use PackApi\Model\ActivitySummary;
use PackApi\Package\Package;
use PackApi\Provider\ActivityProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ActivityInspector::class)]
final class ActivityInspectorTest extends TestCase
{
    public function testGetActivitySummaryReturnsNullWhenNoProviderSupports(): void
    {
        $package = $this->createStub(Package::class);

        $provider = new class implements ActivityProviderInterface {
            public function supports(Package $package): bool
            {
                return false;
            }

            public function getActivitySummary(Package $package): ?ActivitySummary
            {
                throw new \LogicException('Should not be called');
            }
        };

        $inspector = new ActivityInspector([$provider]);

        $this->assertNull($inspector->getActivitySummary($package));
    }

    public function testGetActivitySummaryReturnsSummaryWhenProviderSupports(): void
    {
        $package = $this->createStub(Package::class);
        $expected = new ActivitySummary();

        $provider = new class($expected) implements ActivityProviderInterface {
            public function __construct(private ActivitySummary $summary)
            {
            }

            public function supports(Package $package): bool
            {
                return true;
            }

            public function getActivitySummary(Package $package): ActivitySummary
            {
                return $this->summary;
            }
        };

        $inspector = new ActivityInspector([$provider]);

        $this->assertSame($expected, $inspector->getActivitySummary($package));
    }

    public function testGetActivitySummaryFallsBackWhenSupportingProviderReturnsNull(): void
    {
        $package = $this->createStub(Package::class);
        $expected = new ActivitySummary();
        $empty = $this->createStub(ActivityProviderInterface::class);
        $empty->method('supports')->willReturn(true);
        $empty->method('getActivitySummary')->willReturn(null);
        $fallback = $this->createStub(ActivityProviderInterface::class);
        $fallback->method('supports')->willReturn(true);
        $fallback->method('getActivitySummary')->willReturn($expected);

        $this->assertSame($expected, (new ActivityInspector([$empty, $fallback]))->getActivitySummary($package));
    }
}
