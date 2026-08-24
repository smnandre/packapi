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

use PackApi\Inspector\DownloadStatsInspector;
use PackApi\Model\DownloadPeriod;
use PackApi\Model\DownloadStats;
use PackApi\Package\Package;
use PackApi\Provider\DownloadStatsProviderInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \PackApi\Inspector\DownloadStatsInspector
 */
final class DownloadStatsInspectorTest extends TestCase
{
    public function testGetStatsReturnsNullWhenNoProviderSupports(): void
    {
        $inspector = new DownloadStatsInspector([]);
        $this->assertNull($inspector->getStats(new class('test-name', 'test-identifier') extends Package {}));
    }

    public function testGetStatsDelegatesToFirstSupportingProvider(): void
    {
        $package = $this->createStub(Package::class);
        // Corrected DownloadStats instantiation with correct DownloadPeriod arguments
        $expected = new DownloadStats(['daily' => new DownloadPeriod('daily', 10, new \DateTimeImmutable('2023-01-01'), new \DateTimeImmutable('2023-01-01'))]);

        $provider = $this->createMock(DownloadStatsProviderInterface::class);
        $provider->expects($this->once())->method('supports')->with($package)->willReturn(true);
        $provider->expects($this->once())->method('getStats')->with($package)->willReturn($expected);

        $inspector = new DownloadStatsInspector([$provider]);
        $this->assertSame($expected, $inspector->getStats($package));
    }

    public function testGetStatsForPeriodDelegatesToFirstSupportingProvider(): void
    {
        $package = $this->createStub(Package::class);
        // Corrected DownloadPeriod instantiation with correct arguments
        $period = new DownloadPeriod('daily', 10, new \DateTimeImmutable('2023-01-01'), new \DateTimeImmutable('2023-01-05'));
        $expected = new DownloadStats(['daily' => $period]);

        $provider = $this->createMock(DownloadStatsProviderInterface::class);
        $provider->expects($this->once())->method('supports')->with($package)->willReturn(true);
        $provider->expects($this->once())->method('getStatsForPeriod')->with($package, $period)->willReturn($expected);

        $inspector = new DownloadStatsInspector([$provider]);
        $this->assertSame($expected, $inspector->getStatsForPeriod($package, $period));
    }

    public function testGetStatsForPeriodReturnsNullWhenNoProviderSupports(): void
    {
        $package = $this->createStub(Package::class);
        $period = new DownloadPeriod('daily', 0, new \DateTimeImmutable('2023-01-01'), new \DateTimeImmutable('2023-01-02'));

        $this->assertNull((new DownloadStatsInspector([]))->getStatsForPeriod($package, $period));
    }
}
