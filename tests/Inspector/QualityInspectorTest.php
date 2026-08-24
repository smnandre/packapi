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

use PackApi\Inspector\ContentInspectorInterface;
use PackApi\Inspector\MetadataInspectorInterface;
use PackApi\Inspector\QualityInspector;
use PackApi\Model\ContentOverview;
use PackApi\Model\Metadata;
use PackApi\Package\Package;
use PackApi\Provider\ContentProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QualityInspector::class)]
final class QualityInspectorTest extends TestCase
{
    public function testReturnsNullWhenContentMissing(): void
    {
        $package = $this->createStub(Package::class);

        $contentInspector = $this->createMock(ContentInspectorInterface::class);
        $contentInspector->expects($this->once())
            ->method('getContentOverview')
            ->with($package)
            ->willReturn(null);

        $metadataInspector = $this->createMock(MetadataInspectorInterface::class);
        $metadataInspector->expects($this->once())
            ->method('getMetadata')
            ->with($package)
            ->willReturn(new Metadata('pkg'));

        $inspector = new QualityInspector($contentInspector, $metadataInspector);

        $this->assertNull($inspector->getQualityScore($package));
    }

    public function testReturnsNullWhenMetadataMissing(): void
    {
        $package = $this->createStub(Package::class);

        $content = new ContentOverview(1, 100);
        $contentInspector = $this->createMock(ContentInspectorInterface::class);
        $contentInspector->expects($this->once())
            ->method('getContentOverview')
            ->with($package)
            ->willReturn($content);

        $metadataInspector = $this->createMock(MetadataInspectorInterface::class);
        $metadataInspector->expects($this->once())
            ->method('getMetadata')
            ->with($package)
            ->willReturn(null);

        $inspector = new QualityInspector($contentInspector, $metadataInspector);

        $this->assertNull($inspector->getQualityScore($package));
    }

    public function testCalculatesScoreWithoutBestPractices(): void
    {
        $package = $this->createStub(Package::class);

        $content = new ContentOverview(
            10,
            1000,
            true,
            true,
            true,
            ['vendor']
        );
        $contentInspector = $this->createMock(ContentInspectorInterface::class);
        $contentInspector->method('getContentOverview')->with($package)->willReturn($content);

        $metadata = new Metadata('pkg', 'Some description', 'MIT', 'https://example.com/repo');
        $metadataInspector = $this->createMock(MetadataInspectorInterface::class);
        $metadataInspector->method('getMetadata')->with($package)->willReturn($metadata);

        $inspector = new QualityInspector($contentInspector, $metadataInspector);

        $score = $inspector->getQualityScore($package);
        $this->assertNotNull($score);
        $this->assertSame(73, $score->score);
        $this->assertSame('C', $score->grade);
        $this->assertSame('Average quality, some best practices missing.', $score->comment);
        $this->assertSame([
            'hasReadme' => true,
            'hasLicense' => true,
            'hasTests' => true,
            'hasDescription' => true,
            'hasRepository' => true,
            'ignoredFiles' => 1,
        ], $score->criteria);
    }

    public function testCalculatesScoreWithBestPracticeProvider(): void
    {
        $package = $this->createStub(Package::class);

        $content = new ContentOverview(5, 500, true, true, true);
        $contentInspector = $this->createMock(ContentInspectorInterface::class);
        $contentInspector->method('getContentOverview')->with($package)->willReturn($content);

        $metadata = new Metadata('pkg', 'desc', 'MIT', 'repo');
        $metadataInspector = $this->createMock(MetadataInspectorInterface::class);
        $metadataInspector->method('getMetadata')->with($package)->willReturn($metadata);

        $bestPracticeContent = new ContentOverview(2, 20, false, false, false, ['.gitattributes', '.gitignore']);
        $bestPracticeProvider = $this->createMock(ContentProviderInterface::class);
        $bestPracticeProvider->method('getContentOverview')->with($package)->willReturn($bestPracticeContent);
        $bestPracticeProvider->method('supports')->willReturn(true);

        $inspector = new QualityInspector($contentInspector, $metadataInspector, $bestPracticeProvider);

        $score = $inspector->getQualityScore($package);
        $this->assertNotNull($score);
        $this->assertSame(100, $score->score);
        $this->assertSame('A', $score->grade);
        $this->assertSame('Excellent package hygiene and best practices.', $score->comment);
        $this->assertSame([
            'hasReadme' => true,
            'hasLicense' => true,
            'hasTests' => true,
            'hasDescription' => true,
            'hasRepository' => true,
            'ignoredFiles' => 0,
            'hasGitattributes' => true,
            'hasGitignore' => true,
        ], $score->criteria);
    }

    public function testAssignsBGrade(): void
    {
        $score = $this->calculateScore(
            new ContentOverview(3, 100, true, true, true),
            new Metadata('pkg', 'Description', 'MIT', 'https://example.com/repo'),
        );

        $this->assertSame(75, $score->score);
        $this->assertSame('B', $score->grade);
        $this->assertSame('Good, but could be improved (see best practices).', $score->comment);
    }

    public function testAssignsDGrade(): void
    {
        $score = $this->calculateScore(
            new ContentOverview(3, 100, true, true, true),
            new Metadata('pkg'),
        );

        $this->assertSame(45, $score->score);
        $this->assertSame('D', $score->grade);
        $this->assertSame('Below average, missing key best practices.', $score->comment);
    }

    public function testAssignsFGrade(): void
    {
        $score = $this->calculateScore(
            new ContentOverview(0, 0),
            new Metadata('pkg'),
        );

        $this->assertSame(0, $score->score);
        $this->assertSame('F', $score->grade);
        $this->assertSame('Poor quality, needs significant improvement.', $score->comment);
    }

    private function calculateScore(ContentOverview $content, Metadata $metadata): \PackApi\Model\QualityScore
    {
        $package = $this->createStub(Package::class);
        $contentInspector = $this->createStub(ContentInspectorInterface::class);
        $contentInspector->method('getContentOverview')->willReturn($content);
        $metadataInspector = $this->createStub(MetadataInspectorInterface::class);
        $metadataInspector->method('getMetadata')->willReturn($metadata);

        $score = (new QualityInspector($contentInspector, $metadataInspector))->getQualityScore($package);
        $this->assertNotNull($score);

        return $score;
    }
}
