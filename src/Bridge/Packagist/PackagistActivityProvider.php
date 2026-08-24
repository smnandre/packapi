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

namespace PackApi\Bridge\Packagist;

use PackApi\Model\ActivitySummary;
use PackApi\Package\Package;
use PackApi\Provider\ActivityProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class PackagistActivityProvider implements ActivityProviderInterface
{
    public function __construct(private PackagistApiClient $client)
    {
    }

    public function supports(Package $package): bool
    {
        return $package->getIdentifier() && str_contains($package->getIdentifier(), '/');
    }

    public function getActivitySummary(Package $package): ?ActivitySummary
    {
        $data = $this->client->fetchPackage($package->getIdentifier());
        if (empty($data['package'])) {
            return null;
        }
        $info = $data['package'];
        $versions = $info['versions'] ?? [];
        $lastRelease = null;
        $lastReleaseTime = null;
        if ($versions) {
            $latest = null;
            foreach ($versions as $ver) {
                if (isset($ver['time']) && (null === $latest || $ver['time'] > $latest['time'])) {
                    $latest = $ver;
                }
            }
            if ($latest) {
                $lastRelease = $latest['version'] ?? null;
                $lastReleaseTime = isset($latest['time']) ? new \DateTimeImmutable($latest['time']) : null;
            }
        }
        $lastCommit = $lastReleaseTime ?? (isset($info['time']) ? new \DateTimeImmutable($info['time']) : null);

        return new ActivitySummary(
            lastCommit: $lastCommit,
            contributors: 0,
            openIssues: 0,
            lastRelease: $lastRelease,
        );
    }
}
