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

namespace PackApi\Inspector;

use PackApi\Model\DownloadPeriod;
use PackApi\Model\DownloadStats;
use PackApi\Package\Package;
use PackApi\Provider\DownloadStatsProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class DownloadStatsInspector implements DownloadStatsInspectorInterface
{
    /**
     * @param iterable<DownloadStatsProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    public function getStats(Package $package): ?DownloadStats
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($package)) {
                return $provider->getStats($package);
            }
        }

        return null;
    }

    /**
     * Retrieve download statistics for a custom period.
     */
    public function getStatsForPeriod(Package $package, DownloadPeriod $period): ?DownloadStats
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($package)) {
                return $provider->getStatsForPeriod($package, $period);
            }
        }

        return null;
    }
}
