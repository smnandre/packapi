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

namespace PackApi\Tests\Bridge\OSV;

use PackApi\Bridge\OSV\OSVApiClient;
use PackApi\Bridge\OSV\OSVProviderFactory;
use PackApi\Bridge\OSV\OSVSecurityProvider;
use PackApi\Http\HttpClientFactoryInterface;
use PackApi\Provider\SecurityProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(OSVProviderFactory::class)]
final class OSVProviderFactoryTest extends TestCase
{
    private HttpClientFactoryInterface $httpClientFactory;
    private OSVProviderFactory $factory;

    protected function setUp(): void
    {
        // Create a factory stub that returns a scoped HTTP client stub.
        $this->httpClientFactory = $this->createStub(HttpClientFactoryInterface::class);

        $scopedClient = $this->createStub(HttpClientInterface::class);
        $scopedClient->method('withOptions')->willReturnSelf();

        $this->httpClientFactory
            ->method('createClient')
            ->willReturn($scopedClient);

        $this->factory = new OSVProviderFactory($this->httpClientFactory);
    }

    public function testProvidesReturnsCorrectInterfaces(): void
    {
        $provides = $this->factory->provides();

        $this->assertSame([SecurityProviderInterface::class], $provides);
    }

    public function testCreateSecurityProviderReturnsCorrectInstance(): void
    {
        $provider = $this->factory->createSecurityProvider();

        $this->assertInstanceOf(OSVSecurityProvider::class, $provider);
    }

    public function testCreateWithSecurityProviderInterfaceReturnsCorrectInstance(): void
    {
        $provider = $this->factory->create(SecurityProviderInterface::class);

        $this->assertInstanceOf(OSVSecurityProvider::class, $provider);
    }

    public function testCreateWithUnsupportedInterfaceThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported interface UnknownInterface');

        $this->factory->create('UnknownInterface');
    }

    public function testGetApiClientReturnsOSVApiClient(): void
    {
        $apiClient = $this->factory->getApiClient();

        $this->assertInstanceOf(OSVApiClient::class, $apiClient);
    }
}
