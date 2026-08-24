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

namespace PackApi\System\Npm;

use PackApi\Bridge\Npm\NpmApiClient;
use PackApi\Model\DownloadPeriod;
use PackApi\Model\DownloadStats;
use PackApi\Package\NpmPackage;
use PackApi\Package\Package;
use PackApi\Provider\DownloadStatsProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class NpmDownloadStatsProvider implements DownloadStatsProviderInterface
{
    public function __construct(private readonly NpmApiClient $client)
    {
    }

    public function supports(Package $package): bool
    {
        return $package instanceof NpmPackage;
    }

    public function getStats(Package $package): ?DownloadStats
    {
        $period = new DownloadPeriod(
            'monthly',
            0,
            new \DateTimeImmutable('-1 month'),
            new \DateTimeImmutable()
        );

        return $this->getStatsForPeriod($package, $period);
    }

    public function getStatsForPeriod(Package $package, DownloadPeriod $period): ?DownloadStats
    {
        $stats = $this->client->fetchDownloadStats($package->getName(), $period->getType());

        if (null === $stats) {
            return null;
        }

        $actualPeriod = new DownloadPeriod(
            $period->getType(),
            $stats['downloads'] ?? 0,
            $period->getStart(),
            $period->getEnd()
        );

        return new DownloadStats([$period->getType() => $actualPeriod]);
    }

    /**
     * @return list<string>
     */
    public function getAvailablePeriods(Package $package): array
    {
        // NPM registry typically supports these periods
        return ['total', 'monthly', 'weekly', 'daily'];
    }

    public function hasCdnStats(Package $package): bool
    {
        // NPM doesn't provide CDN stats through their public API
        return false;
    }
}
