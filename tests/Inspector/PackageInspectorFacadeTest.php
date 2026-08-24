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

namespace PackApi\Tests\Inspector;

use PackApi\Http\HttpClientFactoryInterface;
use PackApi\Inspector\ActivityInspectorInterface;
use PackApi\Inspector\ContentInspectorInterface;
use PackApi\Inspector\DownloadStatsInspectorInterface;
use PackApi\Inspector\MetadataInspectorInterface;
use PackApi\Inspector\PackageInspectorBuilder;
use PackApi\Inspector\PackageInspectorFacade;
use PackApi\Inspector\QualityInspector;
use PackApi\Inspector\QualityInspectorInterface;
use PackApi\Inspector\SecurityInspectorInterface;
use PackApi\Model\ActivitySummary;
use PackApi\Model\ContentOverview;
use PackApi\Model\DownloadStats;
use PackApi\Model\Metadata;
use PackApi\Model\PackageReport;
use PackApi\Model\QualityScore;
use PackApi\Model\SecurityAdvisory;
use PackApi\Package\ComposerPackage;
use PackApi\Package\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(PackageInspectorFacade::class)]
final class PackageInspectorFacadeTest extends TestCase
{
    public function testCreatesDefaultFacadeAndBuilder(): void
    {
        $client = $this->createStub(HttpClientInterface::class);
        $client->method('withOptions')->willReturnSelf();
        $httpClientFactory = $this->createStub(HttpClientFactoryInterface::class);
        $httpClientFactory->method('createClient')->willReturn($client);

        $this->assertInstanceOf(PackageInspectorBuilder::class, PackageInspectorFacade::builder($httpClientFactory));
        $this->assertInstanceOf(PackageInspectorFacade::class, PackageInspectorFacade::defaults($httpClientFactory, 'token'));
    }

    public function testAnalyzeCallsAllInspectors(): void
    {
        $package = $this->createStub(Package::class);

        $metadata = new Metadata('test');
        $stats = new DownloadStats();
        $content = new ContentOverview(0, 0);
        $activity = new ActivitySummary();
        $advisories = [new SecurityAdvisory('1', 'title', 'low', 'link')];
        $quality = new QualityScore(80);

        $metadataInspector = $this->createMock(MetadataInspectorInterface::class);
        $metadataInspector->expects($this->once())->method('getMetadata')->with($package)->willReturn($metadata);

        $statsInspector = $this->createMock(DownloadStatsInspectorInterface::class);
        $statsInspector->expects($this->once())->method('getStats')->with($package)->willReturn($stats);

        $contentInspector = $this->createMock(ContentInspectorInterface::class);
        $contentInspector->expects($this->once())->method('getContentOverview')->with($package)->willReturn($content);

        $activityInspector = $this->createMock(ActivityInspectorInterface::class);
        $activityInspector->expects($this->once())->method('getActivitySummary')->with($package)->willReturn($activity);

        $securityInspector = $this->createMock(SecurityInspectorInterface::class);
        $securityInspector->expects($this->once())->method('getSecurityAdvisories')->with($package)->willReturn($advisories);

        $qualityInspector = $this->createMock(QualityInspectorInterface::class);
        $qualityInspector->expects($this->once())->method('getQualityScore')->with($package)->willReturn($quality);

        $facade = new PackageInspectorFacade(
            $metadataInspector,
            $statsInspector,
            $contentInspector,
            $activityInspector,
            $securityInspector,
            $qualityInspector,
        );

        $result = $facade->analyze($package);

        $this->assertSame($metadata, $result['metadata']);
        $this->assertSame($stats, $result['downloads']);
        $this->assertSame($content, $result['content']);
        $this->assertSame($activity, $result['activity']);
        $this->assertSame($advisories, $result['security']);
        $this->assertSame($quality, $result['quality']);
        $this->assertSame($content, $result['best_practices']);
    }

    public function testInspectReturnsTypedReportAndPropagatesRepository(): void
    {
        $package = new ComposerPackage('vendor/package');
        $metadata = new Metadata('vendor/package', repository: 'https://github.com/vendor/package');

        $metadataInspector = $this->createStub(MetadataInspectorInterface::class);
        $metadataInspector->method('getMetadata')->willReturn($metadata);
        $statsInspector = $this->createStub(DownloadStatsInspectorInterface::class);
        $contentInspector = $this->createStub(ContentInspectorInterface::class);
        $activityInspector = $this->createStub(ActivityInspectorInterface::class);
        $securityInspector = $this->createStub(SecurityInspectorInterface::class);
        $qualityInspector = $this->createStub(QualityInspectorInterface::class);

        $report = (new PackageInspectorFacade(
            $metadataInspector,
            $statsInspector,
            $contentInspector,
            $activityInspector,
            $securityInspector,
            $qualityInspector,
        ))->inspect($package);

        $this->assertInstanceOf(PackageReport::class, $report);
        $this->assertSame($metadata, $report->metadata);
        $this->assertSame('https://github.com/vendor/package', $package->getRepositoryUrl());
    }

    public function testInspectReusesFetchedDataForDefaultQualityScoring(): void
    {
        $package = new ComposerPackage('vendor/package');
        $metadata = new Metadata('vendor/package', 'Description', 'MIT', 'https://github.com/vendor/package');
        $content = new ContentOverview(10, 100, true, true, true);

        $metadataInspector = $this->createMock(MetadataInspectorInterface::class);
        $metadataInspector->expects($this->once())->method('getMetadata')->willReturn($metadata);
        $contentInspector = $this->createMock(ContentInspectorInterface::class);
        $contentInspector->expects($this->once())->method('getContentOverview')->willReturn($content);

        $report = (new PackageInspectorFacade(
            $metadataInspector,
            $this->createStub(DownloadStatsInspectorInterface::class),
            $contentInspector,
            $this->createStub(ActivityInspectorInterface::class),
            $this->createStub(SecurityInspectorInterface::class),
            new QualityInspector($contentInspector, $metadataInspector),
        ))->inspect($package);

        $this->assertSame(75, $report->quality?->score);
    }
}
