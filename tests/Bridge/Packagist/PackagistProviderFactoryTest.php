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

namespace PackApi\Tests\Bridge\Packagist;

use PackApi\Bridge\Packagist\PackagistActivityProvider;
use PackApi\Bridge\Packagist\PackagistContentProvider;
use PackApi\Bridge\Packagist\PackagistMetadataProvider;
use PackApi\Bridge\Packagist\PackagistProviderFactory;
use PackApi\Bridge\Packagist\PackagistSearchProvider;
use PackApi\Bridge\Packagist\PackagistSecurityProvider;
use PackApi\Http\HttpClientFactoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(PackagistProviderFactory::class)]
final class PackagistProviderFactoryTest extends TestCase
{
    public function testConstructorScopesHttpClient(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->once())
            ->method('withOptions')
            ->with($this->arrayHasKey('base_uri'))
            ->willReturnSelf();

        $factoryMock = $this->createMock(HttpClientFactoryInterface::class);
        $factoryMock->expects($this->atLeastOnce())
            ->method('createClient')
            ->willReturn($client);

        new PackagistProviderFactory($factoryMock);
    }

    public function testCreateMethodsReturnCorrectInstances(): void
    {
        $client = $this->createStub(HttpClientInterface::class);
        $client->method('withOptions')->willReturnSelf();

        $factoryMock = $this->createStub(HttpClientFactoryInterface::class);
        $factoryMock->method('createClient')->willReturn($client);

        $factory = new PackagistProviderFactory($factoryMock);

        $this->assertInstanceOf(PackagistMetadataProvider::class, $factory->createMetadataProvider());
        $this->assertInstanceOf(PackagistContentProvider::class, $factory->createContentProvider());
        $this->assertInstanceOf(\PackApi\System\Composer\ComposerDownloadStatsProvider::class, $factory->createStatsProvider());
        $this->assertInstanceOf(PackagistSecurityProvider::class, $factory->createSecurityProvider());
        $this->assertInstanceOf(PackagistActivityProvider::class, $factory->createActivityProvider());
        $this->assertInstanceOf(PackagistSearchProvider::class, $factory->createSearchProvider());
    }
}
