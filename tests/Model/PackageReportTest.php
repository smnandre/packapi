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

namespace PackApi\Tests\Model;

use PackApi\Model\ActivitySummary;
use PackApi\Model\ContentOverview;
use PackApi\Model\DownloadPeriod;
use PackApi\Model\DownloadStats;
use PackApi\Model\Metadata;
use PackApi\Model\PackageReport;
use PackApi\Model\QualityScore;
use PackApi\Model\SecurityAdvisory;
use PackApi\Package\ComposerPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PackageReport::class)]
final class PackageReportTest extends TestCase
{
    public function testExposesCompleteDecisionReadySummary(): void
    {
        $package = new ComposerPackage('vendor/package');
        $downloads = new DownloadStats([
            'monthly' => new DownloadPeriod(
                'monthly',
                1200,
                new \DateTimeImmutable('2026-07-01'),
                new \DateTimeImmutable('2026-07-31'),
            ),
        ]);
        $report = new PackageReport(
            $package,
            new Metadata('vendor/package', 'Description', 'MIT', 'https://github.com/vendor/package'),
            $downloads,
            new ContentOverview(42, 1024, true, true, true),
            new ActivitySummary(new \DateTimeImmutable('2026-08-20T12:00:00+00:00'), 12, 3, 'v1.2.0'),
            [new SecurityAdvisory('CVE-1', 'Issue', 'high', 'https://example.com/CVE-1')],
            new QualityScore(95, [], 'A'),
        );

        $this->assertTrue($report->isComplete());
        $this->assertSame([], $report->getMissingSections());
        $this->assertSame(['metadata', 'downloads', 'content', 'activity', 'security', 'quality'], $report->getAvailableSections());
        $this->assertTrue($report->hasSecurityAdvisories());
        $this->assertSame(1, $report->getSecurityAdvisoryCount());
        $this->assertSame(1200, $report->getMonthlyDownloads());
        $this->assertSame('https://github.com/vendor/package', $report->getRepositoryUrl());
        $this->assertSame([
            'package' => 'vendor/package',
            'identifier' => 'vendor/package',
            'repository' => 'https://github.com/vendor/package',
            'complete' => true,
            'available_sections' => ['metadata', 'downloads', 'content', 'activity', 'security', 'quality'],
            'missing_sections' => [],
            'monthly_downloads' => 1200,
            'security_advisories' => 1,
            'quality_score' => 95,
            'quality_grade' => 'A',
            'last_commit' => '2026-08-20T12:00:00+00:00',
        ], $report->getSummary());
    }

    public function testDistinguishesMissingSectionsFromEmptySecurityResults(): void
    {
        $package = new ComposerPackage('vendor/package');
        $package->setRepositoryUrl('https://github.com/vendor/package');
        $report = new PackageReport($package, null, null, null, null, [], null);

        $this->assertFalse($report->isComplete());
        $this->assertSame(['security'], $report->getAvailableSections());
        $this->assertSame(['metadata', 'downloads', 'content', 'activity', 'quality'], $report->getMissingSections());
        $this->assertFalse($report->hasSecurityAdvisories());
        $this->assertSame(0, $report->getSecurityAdvisoryCount());
        $this->assertNull($report->getMonthlyDownloads());
        $this->assertSame('https://github.com/vendor/package', $report->getRepositoryUrl());
    }
}
