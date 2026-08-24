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
use PackApi\Inspector\PackageInspectorBuilder;
use PackApi\Inspector\PackageInspectorFacade;
use PackApi\Model\Metadata;
use PackApi\Package\Package;
use PackApi\Provider\MetadataProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(PackageInspectorBuilder::class)]
final class PackageInspectorBuilderTest extends TestCase
{
    public function testBuildsDefaultFacade(): void
    {
        $builder = PackageInspectorBuilder::defaults($this->createHttpClientFactory());

        $this->assertInstanceOf(PackageInspectorFacade::class, $builder->build());
    }

    public function testCustomProvidersHavePriorityWithoutMutatingBuilder(): void
    {
        $metadata = new Metadata('custom/package');
        $provider = $this->createStub(MetadataProviderInterface::class);
        $provider->method('supports')->willReturn(true);
        $provider->method('getMetadata')->willReturn($metadata);

        $builder = PackageInspectorBuilder::defaults($this->createHttpClientFactory());
        $customized = $builder
            ->withGitHubToken('token')
            ->withMetadataProvider($provider);

        $package = new class('custom-package', 'custom-package') extends Package {};
        $report = $customized->build()->inspect($package);

        $this->assertSame($metadata, $report->metadata);
        $this->assertNotSame($builder, $customized);
    }

    public function testAcceptsAllCustomInspectorInputs(): void
    {
        $builder = PackageInspectorBuilder::defaults($this->createHttpClientFactory())
            ->withDownloadProvider($this->createStub(\PackApi\Provider\DownloadStatsProviderInterface::class))
            ->withContentProvider($this->createStub(\PackApi\Provider\ContentProviderInterface::class))
            ->withActivityProvider($this->createStub(\PackApi\Provider\ActivityProviderInterface::class))
            ->withSecurityProvider($this->createStub(\PackApi\Provider\SecurityProviderInterface::class))
            ->withQualityInspector($this->createStub(\PackApi\Inspector\QualityInspectorInterface::class));

        $this->assertInstanceOf(PackageInspectorFacade::class, $builder->build());
    }

    private function createHttpClientFactory(): HttpClientFactoryInterface
    {
        $client = $this->createStub(HttpClientInterface::class);
        $client->method('withOptions')->willReturnSelf();

        $factory = $this->createStub(HttpClientFactoryInterface::class);
        $factory->method('createClient')->willReturn($client);

        return $factory;
    }
}
