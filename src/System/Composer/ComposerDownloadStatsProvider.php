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

namespace PackApi\System\Composer;

use PackApi\Bridge\Packagist\PackagistApiClient;
use PackApi\Model\DownloadPeriod;
use PackApi\Model\DownloadStats as ModelDownloadStats;
use PackApi\Package\ComposerPackage;
use PackApi\Package\Package;
use PackApi\Provider\DownloadStatsProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class ComposerDownloadStatsProvider implements DownloadStatsProviderInterface
{
    public function __construct(private readonly PackagistApiClient $client)
    {
    }

    public function supports(Package $package): bool
    {
        return $package instanceof ComposerPackage;
    }

    public function getStats(Package $package): ?ModelDownloadStats
    {
        $data = $this->client->fetchPackage($package->getIdentifier());
        if (empty($data['package']['downloads'])) {
            return null;
        }
        $downloads = $data['package']['downloads'];
        $now = new \DateTimeImmutable();
        $periods = [];
        if (isset($downloads['total'])) {
            $periods['total'] = new DownloadPeriod(
                'total',
                (int) $downloads['total'],
                new \DateTimeImmutable('2000-01-01'),
                $now
            );
        }
        if (isset($downloads['monthly'])) {
            $periods['monthly'] = new DownloadPeriod(
                'monthly',
                (int) $downloads['monthly'],
                $now->modify('-1 month'),
                $now
            );
        }
        if (isset($downloads['daily'])) {
            $periods['daily'] = new DownloadPeriod(
                'daily',
                (int) $downloads['daily'],
                $now->modify('-1 day'),
                $now
            );
        }
        if ([] === $periods) {
            return null;
        }

        return new ModelDownloadStats($periods);
    }

    public function getStatsForPeriod(Package $package, DownloadPeriod $period): ?ModelDownloadStats
    {
        $data = $this->client->fetchDownloadsRange(
            $package->getIdentifier(),
            $period->start,
            $period->end,
        );
        if (!is_array($data['downloads'] ?? null)) {
            return null;
        }
        $count = 0;
        foreach ($data['downloads'] as $item) {
            $count += (int) $item['download'];
        }

        return new ModelDownloadStats([
            $period->type => new DownloadPeriod(
                $period->type,
                $count,
                $period->start,
                $period->end
            ),
        ]);
    }

    public function getAvailablePeriods(Package $package): array
    {
        $data = $this->client->fetchPackage($package->getIdentifier());
        if (empty($data['package']['downloads'])) {
            return [];
        }

        return array_keys($data['package']['downloads']);
    }

    public function hasCdnStats(Package $package): bool
    {
        // Packagist does not provide CDN stats
        return false;
    }
}
