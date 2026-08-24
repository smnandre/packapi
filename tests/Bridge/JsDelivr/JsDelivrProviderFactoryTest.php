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
use PackApi\Bridge\JsDelivr\JsDelivrContentProvider;
use PackApi\Bridge\JsDelivr\JsDelivrMetadataProvider;
use PackApi\Bridge\JsDelivr\JsDelivrProviderFactory;
use PackApi\Bridge\JsDelivr\JsDelivrStatsProvider;
use PackApi\Http\HttpClientFactoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(JsDelivrProviderFactory::class)]
final class JsDelivrProviderFactoryTest extends TestCase
{
    public function testFactoryCreatesProvidersAndApiClient(): void
    {
        $httpFactory = $this->createMock(HttpClientFactoryInterface::class);
        $scoped = $this->createStub(HttpClientInterface::class);
        $scoped->method('withOptions')->willReturnSelf();
        $httpFactory->expects($this->once())->method('createClient')->willReturn($scoped);

        $factory = new JsDelivrProviderFactory($httpFactory);

        $this->assertInstanceOf(JsDelivrMetadataProvider::class, $factory->createMetadataProvider());
        $this->assertInstanceOf(JsDelivrStatsProvider::class, $factory->createStatsProvider());
        $this->assertInstanceOf(JsDelivrContentProvider::class, $factory->createContentProvider());

        $ref = new \ReflectionProperty($factory, 'apiClient');
        $this->assertInstanceOf(JsDelivrApiClient::class, $ref->getValue($factory));
    }
}
