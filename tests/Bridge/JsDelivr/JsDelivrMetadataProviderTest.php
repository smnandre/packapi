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

namespace PackApi\Tests\Bridge\JsDelivr;

use PackApi\Bridge\JsDelivr\JsDelivrApiClient;
use PackApi\Bridge\JsDelivr\JsDelivrMetadataProvider;
use PackApi\Model\Metadata;
use PackApi\Package\ComposerPackage;
use PackApi\Package\NpmPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(JsDelivrMetadataProvider::class)]
final class JsDelivrMetadataProviderTest extends TestCase
{
    public function testSupportsNpmPackages(): void
    {
        $provider = new JsDelivrMetadataProvider(new JsDelivrApiClient(new MockHttpClient()));

        $this->assertTrue($provider->supports(new NpmPackage('pkg')));
        $this->assertFalse($provider->supports(new ComposerPackage('vendor/package')));
    }

    public function testGetMetadataReturnsNullWhenApiReturnsNull(): void
    {
        $http = new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ]);
        $provider = new JsDelivrMetadataProvider(new JsDelivrApiClient($http));
        $package = new NpmPackage('pkg');

        $this->assertNull($provider->getMetadata($package));
    }

    public function testGetMetadataReturnsNullForUnsupportedPackage(): void
    {
        $provider = new JsDelivrMetadataProvider(new JsDelivrApiClient(new MockHttpClient()));

        $this->assertNull($provider->getMetadata(new ComposerPackage('vendor/package')));
    }

    public function testGetMetadataBuildsModel(): void
    {
        $data = [
            'name' => 'pkg',
            'description' => 'desc',
            'license' => 'MIT',
            'repository' => 'https://repo',
        ];
        $http = new MockHttpClient([
            new MockResponse(json_encode($data, JSON_THROW_ON_ERROR), ['http_code' => 200]),
        ]);
        $provider = new JsDelivrMetadataProvider(new JsDelivrApiClient($http));
        $package = new NpmPackage('pkg');

        $meta = $provider->getMetadata($package);

        $this->assertInstanceOf(Metadata::class, $meta);
        $this->assertSame('pkg', $meta->getName());
        $this->assertSame('desc', $meta->getDescription());
        $this->assertSame('MIT', $meta->getLicense());
        $this->assertSame('https://repo', $meta->getRepository());
    }

    public function testGetMetadataUsesPackageIdentifierWhenNameMissing(): void
    {
        $data = ['description' => 'only desc'];
        $http = new MockHttpClient([
            new MockResponse(json_encode($data, JSON_THROW_ON_ERROR), ['http_code' => 200]),
        ]);
        $provider = new JsDelivrMetadataProvider(new JsDelivrApiClient($http));
        $package = new NpmPackage('pkg');

        $meta = $provider->getMetadata($package);

        $this->assertInstanceOf(Metadata::class, $meta);
        $this->assertSame('pkg', $meta->getName());
        $this->assertSame('only desc', $meta->getDescription());
    }
}
