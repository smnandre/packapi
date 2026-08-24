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

namespace PackApi\Bridge\JsDelivr;

use PackApi\Model\DownloadPeriod;
use PackApi\Model\DownloadStats;
use PackApi\Package\NpmPackage;
use PackApi\Package\Package;
use PackApi\Package\SwiftPackage;
use PackApi\Provider\DownloadStatsProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class JsDelivrStatsProvider implements DownloadStatsProviderInterface
{
    public function __construct(private readonly JsDelivrApiClient $client)
    {
    }

    public function supports(Package $package): bool
    {
        return $package instanceof NpmPackage || $package instanceof SwiftPackage;
    }

    public function getStats(Package $package): ?DownloadStats
    {
        $stats = $this->fetchStats($package);
        if (null === $stats) {
            return null;
        }

        $dates = $this->getDailyHits($stats);
        if ([] === $dates) {
            return null;
        }

        $dateKeys = array_keys($dates);
        $start = new \DateTimeImmutable($dateKeys[0]);
        $end = new \DateTimeImmutable($dateKeys[array_key_last($dateKeys)]);
        $period = new DownloadPeriod('monthly', array_sum($dates), $start, $end);

        return $this->createDownloadStats($stats, ['monthly' => $period], $end);
    }

    public function getStatsForPeriod(Package $package, DownloadPeriod $period): ?DownloadStats
    {
        $stats = $this->fetchStats($package);
        if (null === $stats) {
            return null;
        }

        $hits = array_filter(
            $this->getDailyHits($stats),
            static fn (int $count, string $date): bool => $date >= $period->getStart()->format('Y-m-d') && $date <= $period->getEnd()->format('Y-m-d'),
            ARRAY_FILTER_USE_BOTH
        );

        if ([] === $hits) {
            return null;
        }

        return $this->createDownloadStats(
            $stats,
            [$period->getType() => new DownloadPeriod($period->getType(), array_sum($hits), $period->getStart(), $period->getEnd())],
            new \DateTimeImmutable(array_key_last($hits))
        );
    }

    /**
     * @return list<string>
     */
    public function getAvailablePeriods(Package $package): array
    {
        return ['monthly', 'daily'];
    }

    public function hasCdnStats(Package $package): bool
    {
        return $this->supports($package);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchStats(Package $package): ?array
    {
        return match (true) {
            $package instanceof NpmPackage => $this->client->fetchPackageStats('npm', $package->getName()),
            $package instanceof SwiftPackage => $this->client->fetchPackageStats('gh', "{$package->getOwner()}/{$package->getRepository()}"),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $stats
     *
     * @return array<string, int>
     */
    private function getDailyHits(array $stats): array
    {
        $dates = $stats['hits']['dates'] ?? null;
        if (!is_array($dates)) {
            return [];
        }

        $hits = [];
        foreach ($dates as $date => $count) {
            if (is_string($date) && is_numeric($count)) {
                $hits[$date] = (int) $count;
            }
        }
        ksort($hits);

        return $hits;
    }

    /**
     * @param array<string, mixed>          $stats
     * @param array<string, DownloadPeriod> $periods
     */
    private function createDownloadStats(array $stats, array $periods, \DateTimeImmutable $lastUpdated): DownloadStats
    {
        return new DownloadStats(
            periods: $periods,
            cdnRequests: isset($stats['hits']['total']) ? (int) $stats['hits']['total'] : null,
            cdnBandwidth: isset($stats['bandwidth']['total']) ? (int) $stats['bandwidth']['total'] : null,
            lastUpdated: $lastUpdated,
        );
    }
}
