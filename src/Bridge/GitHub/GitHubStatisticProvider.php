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

namespace PackApi\Bridge\GitHub;

use PackApi\Exception\ValidationException;
use PackApi\Model\DownloadPeriod;
use PackApi\Model\DownloadStats;
use PackApi\Package\Package;
use PackApi\Provider\DownloadStatsProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class GitHubStatisticProvider implements DownloadStatsProviderInterface
{
    public function __construct(
        private readonly GitHubApiClient $client,
    ) {
    }

    public function supports(Package $package): bool
    {
        $repository = $package->getRepositoryUrl();
        if (!$repository) {
            return false;
        }

        return str_contains($repository, 'github.com');
    }

    public function getStats(Package $package): ?DownloadStats
    {
        $end = new \DateTimeImmutable();
        $start = $end->modify('-1 month');

        return $this->getStatsForPeriod(
            $package,
            new DownloadPeriod('monthly', 0, $start, $end)
        );
    }

    public function getStatsForPeriod(Package $package, DownloadPeriod $period): ?DownloadStats
    {
        $repository = $package->getRepositoryUrl();
        if (!$repository) {
            return null;
        }

        try {
            $repoName = $this->client->extractRepoName($repository);
            if (!$repoName) {
                return null;
            }

            $repoData = $this->client->fetchRepoMetadata($repoName);
            if (!$repoData) {
                return null;
            }

            // GitHub doesn't provide download stats like NPM or Packagist
            // But we can provide repository activity stats as a proxy
            $activityData = $this->client->fetchRepoActivity($repoName);
            if (!$activityData) {
                return null;
            }

            $count = (int) ($repoData['stargazers_count'] ?? 0);
            $computedPeriod = new DownloadPeriod(
                $period->getType(),
                $count,
                $period->getStart(),
                $period->getEnd()
            );

            return new DownloadStats([
                $period->getType() => $computedPeriod,
            ]);
        } catch (ValidationException) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    public function getAvailablePeriods(Package $package): array
    {
        return ['total', 'monthly']; // GitHub provides limited time-based stats
    }

    public function hasCdnStats(Package $package): bool
    {
        return false; // GitHub doesn't provide CDN stats
    }
}
