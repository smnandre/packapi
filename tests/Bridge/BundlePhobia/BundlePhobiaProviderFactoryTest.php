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

namespace PackApi\Tests\Bridge\BundlePhobia;

use PackApi\Bridge\BundlePhobia\BundlePhobiaApiClient;
use PackApi\Bridge\BundlePhobia\BundlePhobiaProviderFactory;
use PackApi\Bridge\BundlePhobia\BundlePhobiaSizeProvider;
use PackApi\Http\HttpClientFactoryInterface;
use PackApi\Provider\BundleSizeProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(BundlePhobiaProviderFactory::class)]
final class BundlePhobiaProviderFactoryTest extends TestCase
{
    private HttpClientFactoryInterface $httpClientFactory;
    private BundlePhobiaProviderFactory $factory;

    protected function setUp(): void
    {
        // Create a factory stub that returns a scoped HTTP client stub.
        $this->httpClientFactory = $this->createStub(HttpClientFactoryInterface::class);

        $scopedClient = $this->createStub(HttpClientInterface::class);
        $scopedClient->method('withOptions')->willReturnSelf();

        $this->httpClientFactory
            ->method('createClient')
            ->willReturn($scopedClient);

        $this->factory = new BundlePhobiaProviderFactory($this->httpClientFactory);
    }

    public function testProvidesReturnsCorrectInterfaces(): void
    {
        $provides = $this->factory->provides();

        $this->assertSame([BundleSizeProviderInterface::class], $provides);
    }

    public function testCreateBundleSizeProviderReturnsCorrectInstance(): void
    {
        $provider = $this->factory->createBundleSizeProvider();

        $this->assertInstanceOf(BundlePhobiaSizeProvider::class, $provider);
    }

    public function testCreateWithBundleSizeProviderInterfaceReturnsCorrectInstance(): void
    {
        $provider = $this->factory->create(BundleSizeProviderInterface::class);

        $this->assertInstanceOf(BundlePhobiaSizeProvider::class, $provider);
    }

    public function testCreateWithUnsupportedInterfaceThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported interface UnknownInterface');

        $this->factory->create('UnknownInterface');
    }

    public function testGetApiClientReturnsBundlePhobiaApiClient(): void
    {
        $apiClient = $this->factory->getApiClient();

        $this->assertInstanceOf(BundlePhobiaApiClient::class, $apiClient);
    }

    public function testFactoryCreatesWithHttpClientFactory(): void
    {
        // Test that factory can be created with HTTP client factory
        $this->assertInstanceOf(BundlePhobiaProviderFactory::class, $this->factory);
    }

    public function testFactoryCreatesCorrectApiClient(): void
    {
        $apiClient = $this->factory->getApiClient();

        // Verify the API client is properly created
        $this->assertInstanceOf(BundlePhobiaApiClient::class, $apiClient);
    }
}
