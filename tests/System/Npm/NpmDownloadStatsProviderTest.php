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

namespace PackApi\Tests\System\Npm;

use PackApi\Bridge\Npm\NpmApiClient;
use PackApi\Exception\NetworkException;
use PackApi\Model\DownloadPeriod;
use PackApi\Model\DownloadStats;
use PackApi\Package\NpmPackage;
use PackApi\Package\ComposerPackage;
use PackApi\System\Npm\NpmDownloadStatsProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(NpmDownloadStatsProvider::class)]
final class NpmDownloadStatsProviderTest extends TestCase
{
    public function testSupportsOnlyNpmPackages(): void
    {
        $provider = new NpmDownloadStatsProvider(new NpmApiClient(new MockHttpClient(), new MockHttpClient()));

        $this->assertTrue($provider->supports(new NpmPackage('package')));
        $this->assertFalse($provider->supports(new ComposerPackage('vendor/package')));
    }

    public function testGetStatsParsesMonthlyDownloads(): void
    {
        $statsClient = new MockHttpClient([
            new MockResponse(json_encode(['downloads' => 55]), ['http_code' => 200]),
        ]);
        $apiClient = new NpmApiClient(new MockHttpClient(), $statsClient);
        $provider = new NpmDownloadStatsProvider($apiClient);
        $package = new NpmPackage('test-package');

        $stats = $provider->getStats($package);

        $this->assertInstanceOf(DownloadStats::class, $stats);
        $period = $stats->get('monthly');
        $this->assertInstanceOf(DownloadPeriod::class, $period);
        $this->assertSame(55, $period->getCount());
        $this->assertSame('monthly', $period->getType());
    }

    public function testGetStatsReturnsNullOn404(): void
    {
        $statsClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ]);
        $apiClient = new NpmApiClient(new MockHttpClient(), $statsClient);
        $provider = new NpmDownloadStatsProvider($apiClient);
        $package = new NpmPackage('test-package');

        $this->assertNull($provider->getStats($package));
    }

    public function testGetStatsThrowsNetworkExceptionOnError(): void
    {
        $statsClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]);
        $apiClient = new NpmApiClient(new MockHttpClient(), $statsClient);
        $provider = new NpmDownloadStatsProvider($apiClient);
        $package = new NpmPackage('test-package');

        $this->expectException(NetworkException::class);
        $provider->getStats($package);
    }

    public function testGetStatsForSpecificPeriod(): void
    {
        $statsClient = new MockHttpClient([
            new MockResponse(json_encode(['downloads' => 21]), ['http_code' => 200]),
        ]);
        $apiClient = new NpmApiClient(new MockHttpClient(), $statsClient);
        $provider = new NpmDownloadStatsProvider($apiClient);
        $package = new NpmPackage('test-package');
        $period = new DownloadPeriod('weekly', 0, new \DateTimeImmutable('-1 week'), new \DateTimeImmutable());

        $stats = $provider->getStatsForPeriod($package, $period);

        $this->assertInstanceOf(DownloadStats::class, $stats);
        $downloadPeriod = $stats->get('weekly');
        $this->assertInstanceOf(DownloadPeriod::class, $downloadPeriod);
        $this->assertSame(21, $downloadPeriod->getCount());
        $this->assertSame('weekly', $downloadPeriod->getType());
    }

    public function testCapabilities(): void
    {
        $provider = new NpmDownloadStatsProvider(new NpmApiClient(new MockHttpClient(), new MockHttpClient()));
        $package = new NpmPackage('package');

        $this->assertSame(['total', 'monthly', 'weekly', 'daily'], $provider->getAvailablePeriods($package));
        $this->assertFalse($provider->hasCdnStats($package));
    }
}
