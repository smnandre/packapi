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

namespace PackApi\Tests\Bridge\Npm;

use PackApi\Bridge\Npm\NpmProviderFactory;
use PackApi\Http\HttpClientFactoryInterface;
use PackApi\Provider\ContentProviderInterface;
use PackApi\Provider\DownloadStatsProviderInterface;
use PackApi\Provider\MetadataProviderInterface;
use PackApi\System\Npm\NpmContentProvider;
use PackApi\System\Npm\NpmDownloadStatsProvider;
use PackApi\System\Npm\NpmMetadataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NpmProviderFactory::class)]
final class NpmProviderFactoryTest extends TestCase
{
    public function testProvidesReturnsExpectedInterfaces(): void
    {
        $httpClientFactory = $this->createStub(HttpClientFactoryInterface::class);
        $factory = new NpmProviderFactory($httpClientFactory);

        $result = $factory->provides();

        $this->assertSame([
            MetadataProviderInterface::class,
            DownloadStatsProviderInterface::class,
            ContentProviderInterface::class,
        ], $result);
    }

    public function testCreateThrowsLogicExceptionForUnsupportedInterface(): void
    {
        $httpClientFactory = $this->createStub(HttpClientFactoryInterface::class);
        $factory = new NpmProviderFactory($httpClientFactory);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported interface InvalidInterface');

        $factory->create('InvalidInterface');
    }

    public function testCreateMetadataProviderReturnsInstance(): void
    {
        $httpClientFactory = $this->createStub(HttpClientFactoryInterface::class);
        $factory = new NpmProviderFactory($httpClientFactory);

        $result = $factory->create(MetadataProviderInterface::class);

        $this->assertInstanceOf(NpmMetadataProvider::class, $result);
    }

    public function testCreateDownloadStatsProviderReturnsInstance(): void
    {
        $httpClientFactory = $this->createStub(HttpClientFactoryInterface::class);
        $factory = new NpmProviderFactory($httpClientFactory);

        $result = $factory->create(DownloadStatsProviderInterface::class);

        $this->assertInstanceOf(NpmDownloadStatsProvider::class, $result);
    }

    public function testCreateContentProviderReturnsInstance(): void
    {
        $httpClientFactory = $this->createStub(HttpClientFactoryInterface::class);
        $factory = new NpmProviderFactory($httpClientFactory);

        $result = $factory->create(ContentProviderInterface::class);

        $this->assertInstanceOf(NpmContentProvider::class, $result);
    }
}
