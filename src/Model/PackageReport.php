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

use PackApi\Package\Package;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class PackageReport
{
    /**
     * @param list<SecurityAdvisory>|null $securityAdvisories
     */
    public function __construct(
        public readonly Package $package,
        public readonly ?Metadata $metadata,
        public readonly ?DownloadStats $downloads,
        public readonly ?ContentOverview $content,
        public readonly ?ActivitySummary $activity,
        public readonly ?array $securityAdvisories,
        public readonly ?QualityScore $quality,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getAvailableSections(): array
    {
        return array_keys(array_filter($this->sections(), static fn (mixed $value): bool => null !== $value));
    }

    /**
     * @return list<string>
     */
    public function getMissingSections(): array
    {
        return array_keys(array_filter($this->sections(), static fn (mixed $value): bool => null === $value));
    }

    public function isComplete(): bool
    {
        return [] === $this->getMissingSections();
    }

    public function hasSecurityAdvisories(): bool
    {
        return null !== $this->securityAdvisories && [] !== $this->securityAdvisories;
    }

    public function getSecurityAdvisoryCount(): int
    {
        return count($this->securityAdvisories ?? []);
    }

    public function getMonthlyDownloads(): ?int
    {
        return $this->downloads?->get('monthly')?->getCount();
    }

    public function getRepositoryUrl(): ?string
    {
        return $this->metadata->repository ?? $this->package->getRepositoryUrl();
    }

    /**
     * Return decision-ready values without flattening the detailed result objects.
     *
     * @return array{
     *     package: string,
     *     identifier: string,
     *     repository: string|null,
     *     complete: bool,
     *     available_sections: list<string>,
     *     missing_sections: list<string>,
     *     monthly_downloads: int|null,
     *     security_advisories: int,
     *     quality_score: int|null,
     *     quality_grade: string|null,
     *     last_commit: string|null
     * }
     */
    public function getSummary(): array
    {
        return [
            'package' => $this->package->getName(),
            'identifier' => $this->package->getIdentifier(),
            'repository' => $this->getRepositoryUrl(),
            'complete' => $this->isComplete(),
            'available_sections' => $this->getAvailableSections(),
            'missing_sections' => $this->getMissingSections(),
            'monthly_downloads' => $this->getMonthlyDownloads(),
            'security_advisories' => $this->getSecurityAdvisoryCount(),
            'quality_score' => $this->quality?->score,
            'quality_grade' => $this->quality?->grade,
            'last_commit' => $this->activity?->lastCommit?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array{
     *     metadata: Metadata|null,
     *     downloads: DownloadStats|null,
     *     content: ContentOverview|null,
     *     activity: ActivitySummary|null,
     *     security: list<SecurityAdvisory>|null,
     *     quality: QualityScore|null
     * }
     */
    private function sections(): array
    {
        return [
            'metadata' => $this->metadata,
            'downloads' => $this->downloads,
            'content' => $this->content,
            'activity' => $this->activity,
            'security' => $this->securityAdvisories,
            'quality' => $this->quality,
        ];
    }
}
