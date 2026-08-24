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

namespace PackApi\Model;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class DownloadStats
{
    /**
     * @param array<string, DownloadPeriod> $periods
     */
    public function __construct(
        public readonly array $periods = [],
        public readonly ?int $cdnRequests = null,
        public readonly ?int $cdnBandwidth = null,
        public readonly ?\DateTimeImmutable $lastUpdated = null,
    ) {
    }

    public function get(string $key): ?DownloadPeriod
    {
        return $this->periods[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->periods[$key]);
    }

    /**
     * @return array<string, DownloadPeriod>
     */
    public function getPeriods(): array
    {
        return $this->periods;
    }

    public function getCdnRequests(): ?int
    {
        return $this->cdnRequests;
    }

    public function getCdnBandwidth(): ?int
    {
        return $this->cdnBandwidth;
    }

    public function getLastUpdated(): ?\DateTimeImmutable
    {
        return $this->lastUpdated;
    }
}
