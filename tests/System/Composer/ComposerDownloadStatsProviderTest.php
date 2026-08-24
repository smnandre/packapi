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

namespace PackApi\Tests\System\Composer;

use PackApi\Bridge\Packagist\PackagistApiClient;
use PackApi\Model\DownloadPeriod;
use PackApi\Package\ComposerPackage;
use PackApi\Package\NpmPackage;
use PackApi\System\Composer\ComposerDownloadStatsProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ComposerDownloadStatsProviderTest extends TestCase
{
    public function testSupportsOnlyComposerPackages(): void
    {
        $provider = new ComposerDownloadStatsProvider(new PackagistApiClient(new MockHttpClient()));

        $this->assertTrue($provider->supports(new ComposerPackage('foo/bar')));
        $this->assertFalse($provider->supports(new NpmPackage('foo')));
    }

    public function testGetStatsReturnsPeriods(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn(json_encode([
            'package' => [
                'downloads' => [
                    'total' => 1000,
                    'monthly' => 100,
                    'daily' => 10,
                ],
            ],
        ]));

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $client = new PackagistApiClient($httpClient);
        $provider = new ComposerDownloadStatsProvider($client);
        $package = new ComposerPackage('foo/bar');
        $stats = $provider->getStats($package);
        $this->assertNotNull($stats);
        $this->assertInstanceOf(\PackApi\Model\DownloadStats::class, $stats);
        $this->assertInstanceOf(DownloadPeriod::class, $stats->get('total'));
        $this->assertInstanceOf(DownloadPeriod::class, $stats->get('monthly'));
        $this->assertInstanceOf(DownloadPeriod::class, $stats->get('daily'));
        $this->assertSame(1000, $stats->get('total')->getCount());
        $this->assertSame(100, $stats->get('monthly')->getCount());
        $this->assertSame(10, $stats->get('daily')->getCount());
    }

    public function testGetStatsReturnsNullIfNoDownloads(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn(json_encode(['package' => []]));

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $client = new PackagistApiClient($httpClient);
        $provider = new ComposerDownloadStatsProvider($client);
        $package = new ComposerPackage('foo/bar');

        $this->assertNull($provider->getStats($package));
    }

    public function testGetStatsReturnsNullWithoutSupportedPeriods(): void
    {
        $client = new PackagistApiClient(new MockHttpClient([
            new MockResponse('{"package":{"downloads":{"weekly":10}}}'),
        ]));
        $provider = new ComposerDownloadStatsProvider($client);

        $this->assertNull($provider->getStats(new ComposerPackage('foo/bar')));
    }

    public function testGetStatsForPeriodAggregatesDailyDownloads(): void
    {
        $client = new PackagistApiClient(new MockHttpClient([
            new MockResponse('{"downloads":[{"date":"2026-08-01","download":4},{"date":"2026-08-02","download":6}]}'),
        ]));
        $provider = new ComposerDownloadStatsProvider($client);
        $period = new DownloadPeriod('custom', 0, new \DateTimeImmutable('2026-08-01'), new \DateTimeImmutable('2026-08-02'));

        $stats = $provider->getStatsForPeriod(new ComposerPackage('foo/bar'), $period);

        $this->assertSame(10, $stats?->get('custom')?->getCount());
        $this->assertSame($period->getStart(), $stats?->get('custom')?->getStart());
        $this->assertSame($period->getEnd(), $stats?->get('custom')?->getEnd());
    }

    public function testGetStatsForPeriodReturnsNullWithoutDailyDownloads(): void
    {
        $client = new PackagistApiClient(new MockHttpClient([
            new MockResponse('{"downloads":"unavailable"}'),
        ]));
        $provider = new ComposerDownloadStatsProvider($client);
        $period = new DownloadPeriod('custom', 0, new \DateTimeImmutable('2026-08-01'), new \DateTimeImmutable('2026-08-02'));

        $this->assertNull($provider->getStatsForPeriod(new ComposerPackage('foo/bar'), $period));
    }

    public function testGetAvailablePeriodsReturnsDownloadKeys(): void
    {
        $client = new PackagistApiClient(new MockHttpClient([
            new MockResponse('{"package":{"downloads":{"total":100,"monthly":10}}}'),
        ]));
        $provider = new ComposerDownloadStatsProvider($client);

        $this->assertSame(['total', 'monthly'], $provider->getAvailablePeriods(new ComposerPackage('foo/bar')));
    }

    public function testGetAvailablePeriodsReturnsEmptyArrayWithoutDownloads(): void
    {
        $client = new PackagistApiClient(new MockHttpClient([
            new MockResponse('{"package":{}}'),
        ]));
        $provider = new ComposerDownloadStatsProvider($client);

        $this->assertSame([], $provider->getAvailablePeriods(new ComposerPackage('foo/bar')));
    }

    public function testHasCdnStats(): void
    {
        $client = new PackagistApiClient($this->createStub(HttpClientInterface::class));
        $provider = new ComposerDownloadStatsProvider($client);
        $package = new ComposerPackage('foo/bar');

        $this->assertFalse($provider->hasCdnStats($package));
    }
}
