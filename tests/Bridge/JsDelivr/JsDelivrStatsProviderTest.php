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

namespace PackApi\Tests\Bridge\JsDelivr;

use PackApi\Bridge\JsDelivr\JsDelivrApiClient;
use PackApi\Bridge\JsDelivr\JsDelivrStatsProvider;
use PackApi\Model\DownloadStats;
use PackApi\Model\DownloadPeriod;
use PackApi\Package\ComposerPackage;
use PackApi\Package\NpmPackage;
use PackApi\Package\SwiftPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(JsDelivrStatsProvider::class)]
final class JsDelivrStatsProviderTest extends TestCase
{
    public function testSupportsNpmAndSwiftPackages(): void
    {
        $client = new JsDelivrApiClient(new MockHttpClient());
        $provider = new JsDelivrStatsProvider($client);

        $this->assertTrue($provider->supports(new NpmPackage('pkg')));
        $this->assertTrue($provider->supports(new SwiftPackage('Alamofire', 'Alamofire')));
        $this->assertFalse($provider->supports(new ComposerPackage('vendor/package')));
    }

    public function testGetStatsReturnsNullWhenNoHits(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{}', ['http_code' => 200]),
        ]);
        $provider = new JsDelivrStatsProvider(new JsDelivrApiClient($http));
        $package = new NpmPackage('pkg');

        $this->assertNull($provider->getStats($package));
    }

    public function testGetStatsReturnsNullWhenApiReturnsNull(): void
    {
        $provider = new JsDelivrStatsProvider(new JsDelivrApiClient(new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ])));

        $this->assertNull($provider->getStats(new NpmPackage('unknown')));
    }

    public function testGetStatsReturnsNullForUnsupportedPackage(): void
    {
        $provider = new JsDelivrStatsProvider(new JsDelivrApiClient(new MockHttpClient()));

        $this->assertNull($provider->getStats(new ComposerPackage('vendor/package')));
    }

    public function testGetStatsUsesGitHubPathForSwiftPackage(): void
    {
        $capturedUrl = null;
        $http = new MockHttpClient(function (string $method, string $url) use (&$capturedUrl): MockResponse {
            $capturedUrl = $url;

            return new MockResponse('{"hits":{"dates":{"2026-07-10":42}}}');
        });
        $provider = new JsDelivrStatsProvider(new JsDelivrApiClient($http));

        $stats = $provider->getStats(new SwiftPackage('Alamofire', 'Alamofire'));

        $this->assertSame(42, $stats?->get('monthly')?->getCount());
        $this->assertStringEndsWith('v1/stats/packages/gh/Alamofire/Alamofire', $capturedUrl);
    }

    public function testGetStatsBuildsDownloadStats(): void
    {
        $http = new MockHttpClient([
            new MockResponse('{"hits":{"total":42,"dates":{"2026-07-09":17,"2026-07-10":25}},"bandwidth":{"total":512}}', ['http_code' => 200]),
        ]);
        $provider = new JsDelivrStatsProvider(new JsDelivrApiClient($http));
        $package = new NpmPackage('pkg');

        $stats = $provider->getStats($package);

        $this->assertInstanceOf(DownloadStats::class, $stats);
        $period = $stats->get('monthly');
        $this->assertNotNull($period);
        $this->assertSame('monthly', $period->getType());
        $this->assertSame(42, $period->getCount());
        $this->assertLessThan($period->getEnd(), $period->getStart());
        $this->assertSame(42, $stats->getCdnRequests());
        $this->assertSame(512, $stats->getCdnBandwidth());
        $this->assertSame('2026-07-10', $stats->getLastUpdated()?->format('Y-m-d'));
    }

    public function testAvailablePeriods(): void
    {
        $provider = new JsDelivrStatsProvider(new JsDelivrApiClient(new MockHttpClient()));
        $this->assertSame(['monthly', 'daily'], $provider->getAvailablePeriods(new NpmPackage('pkg')));
    }

    public function testGetStatsForPeriodAggregatesAvailableDailyStats(): void
    {
        $provider = new JsDelivrStatsProvider(new JsDelivrApiClient(new MockHttpClient([
            new MockResponse('{"hits":{"total":42,"dates":{"2026-07-08":4,"2026-07-09":17,"2026-07-10":25}}}', ['http_code' => 200]),
        ])));
        $period = new DownloadPeriod('weekly', 0, new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-10'));

        $stats = $provider->getStatsForPeriod(new NpmPackage('pkg'), $period);

        $this->assertSame(42, $stats?->get('weekly')?->getCount());
    }

    public function testGetStatsForPeriodReturnsNullWhenApiReturnsNull(): void
    {
        $provider = new JsDelivrStatsProvider(new JsDelivrApiClient(new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ])));
        $period = new DownloadPeriod('weekly', 0, new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-10'));

        $this->assertNull($provider->getStatsForPeriod(new NpmPackage('unknown'), $period));
    }

    public function testGetStatsForPeriodReturnsNullWithoutHitsInPeriod(): void
    {
        $provider = new JsDelivrStatsProvider(new JsDelivrApiClient(new MockHttpClient([
            new MockResponse('{"hits":{"dates":{"2026-07-08":4}}}', ['http_code' => 200]),
        ])));
        $period = new DownloadPeriod('weekly', 0, new \DateTimeImmutable('2026-07-09'), new \DateTimeImmutable('2026-07-10'));

        $this->assertNull($provider->getStatsForPeriod(new NpmPackage('pkg'), $period));
    }

    public function testHasCdnStats(): void
    {
        $provider = new JsDelivrStatsProvider(new JsDelivrApiClient(new MockHttpClient()));
        $this->assertTrue($provider->hasCdnStats(new NpmPackage('pkg')));
        $this->assertTrue($provider->hasCdnStats(new SwiftPackage('Alamofire', 'Alamofire')));
        $this->assertFalse($provider->hasCdnStats(new ComposerPackage('vendor/package')));
    }
}
