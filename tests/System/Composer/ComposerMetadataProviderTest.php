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

namespace PackApi\Tests\System\Composer;

use PackApi\Bridge\Packagist\PackagistApiClient;
use PackApi\Model\Metadata;
use PackApi\Package\ComposerPackage;
use PackApi\Package\NpmPackage;
use PackApi\System\Composer\ComposerMetadataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(ComposerMetadataProvider::class)]
final class ComposerMetadataProviderTest extends TestCase
{
    private HttpClientInterface|MockObject $httpClient;
    private PackagistApiClient $apiClient;
    private ComposerMetadataProvider $provider;

    protected function setUp(): void
    {
        $this->httpClient = $this->createStub(HttpClientInterface::class);
        $this->apiClient = new PackagistApiClient($this->httpClient);
        $this->provider = new ComposerMetadataProvider($this->apiClient);
    }

    private function useMockHttpClient(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->apiClient = new PackagistApiClient($this->httpClient);
        $this->provider = new ComposerMetadataProvider($this->apiClient);
    }

    public function testSupportsReturnsTrueForComposerPackage(): void
    {
        $package = new ComposerPackage('vendor/package');

        $this->assertTrue($this->provider->supports($package));
    }

    public function testSupportsReturnsFalseForNonComposerPackage(): void
    {
        $package = new NpmPackage('foo');

        $this->assertFalse($this->provider->supports($package));
    }

    public function testGetMetadataReturnsNullWhenApiReturnsEmpty(): void
    {
        $package = new ComposerPackage('vendor/package');
        $this->useMockHttpClient();

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn(json_encode([]));

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'packages/vendor/package.json')
            ->willReturn($response);

        $this->assertNull($this->provider->getMetadata($package));
    }

    public function testGetMetadataReturnsMetadata(): void
    {
        $package = new ComposerPackage('vendor/package');
        $this->useMockHttpClient();
        $apiData = [
            'package' => [
                'name' => 'vendor/package',
                'description' => 'desc',
                'license' => ['MIT'],
                'repository' => 'https://example.com/vendor/package',
            ],
        ];

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn(json_encode($apiData));

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'packages/vendor/package.json')
            ->willReturn($response);

        $metadata = $this->provider->getMetadata($package);

        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertSame('vendor/package', $metadata->name);
        $this->assertSame('desc', $metadata->description);
        $this->assertSame('MIT', $metadata->license);
        $this->assertSame('https://example.com/vendor/package', $metadata->repository);
    }

    public function testGetMetadataAcceptsStringLicense(): void
    {
        $package = new ComposerPackage('vendor/package');
        $this->useMockHttpClient();
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn(json_encode([
            'package' => [
                'name' => 'vendor/package',
                'license' => 'MIT',
            ],
        ]));

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->assertSame('MIT', $this->provider->getMetadata($package)?->getLicense());
    }
}
