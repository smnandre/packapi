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

namespace PackApi\Tests\Bridge\GitHub;

use PackApi\Bridge\GitHub\GitHubApiClient;
use PackApi\Bridge\GitHub\GitHubStatisticProvider;
use PackApi\Model\DownloadPeriod;
use PackApi\Model\DownloadStats;
use PackApi\Package\ComposerPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(GitHubStatisticProvider::class)]
final class GitHubStatisticProviderTest extends TestCase
{
    private function createPackage(?string $repository): ComposerPackage
    {
        $package = new ComposerPackage('owner/repo');
        $package->setRepositoryUrl($repository);

        return $package;
    }

    public function testGetStatsReturnsDownloadStats(): void
    {
        $responses = [
            new MockResponse(json_encode(['stargazers_count' => 5])),
            new MockResponse(json_encode(['stargazers_count' => 5])),
            new MockResponse(json_encode([['commit' => ['committer' => ['date' => '2024-01-01T00:00:00Z']]]])),
            new MockResponse(json_encode([[]])),
            new MockResponse(json_encode([[]])),
        ];
        $client = new GitHubApiClient(new MockHttpClient($responses));

        $provider = new GitHubStatisticProvider($client);
        $pkg = new ComposerPackage('owner/repo');
        $pkg->setRepositoryUrl('https://github.com/owner/repo');

        $stats = $provider->getStats($pkg);

        $this->assertInstanceOf(DownloadStats::class, $stats);
        $this->assertTrue($stats->has('monthly'));
    }

    public function testSupportsOnlyGitHubRepositories(): void
    {
        $provider = new GitHubStatisticProvider(new GitHubApiClient(new MockHttpClient()));

        $this->assertFalse($provider->supports($this->createPackage(null)));
        $this->assertFalse($provider->supports($this->createPackage('https://gitlab.com/owner/repo')));
        $this->assertTrue($provider->supports($this->createPackage('https://github.com/owner/repo')));
    }

    public function testGetStatsForPeriodReturnsNullWithoutRepository(): void
    {
        $provider = new GitHubStatisticProvider(new GitHubApiClient(new MockHttpClient()));

        $this->assertNull($provider->getStatsForPeriod($this->createPackage(null), $this->createPeriod()));
    }

    public function testGetStatsForPeriodReturnsNullForInvalidRepositoryUrl(): void
    {
        $provider = new GitHubStatisticProvider(new GitHubApiClient(new MockHttpClient()));

        $this->assertNull($provider->getStatsForPeriod($this->createPackage('not-a-repository'), $this->createPeriod()));
    }

    public function testGetStatsForPeriodReturnsNullWhenRepositoryIsMissing(): void
    {
        $provider = new GitHubStatisticProvider(new GitHubApiClient(new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ])));

        $this->assertNull($provider->getStatsForPeriod($this->createPackage('https://github.com/owner/repo'), $this->createPeriod()));
    }

    public function testGetStatsForPeriodReturnsNullWhenActivityIsMissing(): void
    {
        $provider = new GitHubStatisticProvider(new GitHubApiClient(new MockHttpClient([
            new MockResponse('{"stargazers_count":5}'),
            new MockResponse('', ['http_code' => 404]),
        ])));

        $this->assertNull($provider->getStatsForPeriod($this->createPackage('https://github.com/owner/repo'), $this->createPeriod()));
    }

    public function testGetStatsForPeriodReturnsNullForMalformedGitHubRepository(): void
    {
        $provider = new GitHubStatisticProvider(new GitHubApiClient(new MockHttpClient()));

        $this->assertNull($provider->getStatsForPeriod($this->createPackage('https://github.com/owner!/repo'), $this->createPeriod()));
    }

    public function testCapabilities(): void
    {
        $package = $this->createPackage('https://github.com/owner/repo');
        $provider = new GitHubStatisticProvider(new GitHubApiClient(new MockHttpClient()));

        $this->assertSame(['total', 'monthly'], $provider->getAvailablePeriods($package));
        $this->assertFalse($provider->hasCdnStats($package));
    }

    private function createPeriod(): DownloadPeriod
    {
        return new DownloadPeriod(
            'custom',
            0,
            new \DateTimeImmutable('2026-08-01'),
            new \DateTimeImmutable('2026-08-02'),
        );
    }
}
