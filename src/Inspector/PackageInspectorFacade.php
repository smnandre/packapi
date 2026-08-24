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

use PackApi\Package\Package;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class PackageInspectorFacade
{
    public function __construct(
        public readonly MetadataInspectorInterface $metadataInspector,
        public readonly DownloadStatsInspectorInterface $downloadStatsInspector,
        public readonly ContentInspectorInterface $contentInspector,
        public readonly ActivityInspectorInterface $activityInspector,
        public readonly SecurityInspectorInterface $securityInspector,
        public readonly QualityInspectorInterface $qualityInspector,
    ) {
    }

    // Unified API
    /**
     * @return array<string, mixed>
     */
    public function analyze(Package $package): array
    {
        $contentOverview = $this->contentInspector->getContentOverview($package);

        return [
            'metadata' => $this->metadataInspector->getMetadata($package),
            'downloads' => $this->downloadStatsInspector->getStats($package),
            'content' => $contentOverview,
            'activity' => $this->activityInspector->getActivitySummary($package),
            'security' => $this->securityInspector->getSecurityAdvisories($package),
            'quality' => $this->qualityInspector->getQualityScore($package),
            'best_practices' => $contentOverview,
        ];
    }
}
