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

use PackApi\Http\HttpClientFactoryInterface;
use PackApi\Model\PackageReport;
use PackApi\Package\Package;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class PackageInspectorFacade
{
    public static function defaults(
        ?HttpClientFactoryInterface $httpClientFactory = null,
        #[\SensitiveParameter]
        ?string $githubToken = null,
    ): self {
        $builder = self::builder($httpClientFactory);
        if (null !== $githubToken) {
            $builder = $builder->withGitHubToken($githubToken);
        }

        return $builder->build();
    }

    public static function builder(?HttpClientFactoryInterface $httpClientFactory = null): PackageInspectorBuilder
    {
        return PackageInspectorBuilder::defaults($httpClientFactory);
    }

    public function __construct(
        public readonly MetadataInspectorInterface $metadataInspector,
        public readonly DownloadStatsInspectorInterface $downloadStatsInspector,
        public readonly ContentInspectorInterface $contentInspector,
        public readonly ActivityInspectorInterface $activityInspector,
        public readonly SecurityInspectorInterface $securityInspector,
        public readonly QualityInspectorInterface $qualityInspector,
    ) {
    }

    public function inspect(Package $package): PackageReport
    {
        $metadata = $this->metadataInspector->getMetadata($package);
        if (null === $package->getRepositoryUrl() && null !== $metadata?->repository) {
            $package->setRepositoryUrl($metadata->repository);
        }

        $downloads = $this->downloadStatsInspector->getStats($package);
        $content = $this->contentInspector->getContentOverview($package);
        $activity = $this->activityInspector->getActivitySummary($package);
        $security = $this->securityInspector->getSecurityAdvisories($package);
        $quality = $this->qualityInspector instanceof QualityInspector && null !== $content && null !== $metadata
            ? $this->qualityInspector->score($package, $content, $metadata)
            : $this->qualityInspector->getQualityScore($package);

        return new PackageReport(
            $package,
            $metadata,
            $downloads,
            $content,
            $activity,
            $security,
            $quality,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function analyze(Package $package): array
    {
        $report = $this->inspect($package);

        return [
            'metadata' => $report->metadata,
            'downloads' => $report->downloads,
            'content' => $report->content,
            'activity' => $report->activity,
            'security' => $report->securityAdvisories,
            'quality' => $report->quality,
            'best_practices' => $report->content,
        ];
    }
}
