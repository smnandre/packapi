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

namespace PackApi\Tests\Http;

use PackApi\Http\HttpClientFactory;
use PackApi\Http\Middleware\LoggingMiddleware;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(HttpClientFactory::class)]
final class HttpClientFactoryTest extends TestCase
{
    public function testCreateClientWithoutQuic(): void
    {
        $factory = new HttpClientFactory();
        $client = $factory->createClient();
        $this->assertInstanceOf(HttpClientInterface::class, $client);

        $ref = new \ReflectionObject($client);
        $prop = $ref->getProperty('defaultOptions');

        $options = $prop->getValue($client);
        $this->assertArrayHasKey('http_version', $options);
        $this->assertNull($options['http_version']);
    }

    public function testCreateClientWithQuic(): void
    {
        $factory = new HttpClientFactory();
        $client = $factory->createClient(['enable_quic' => true]);
        $this->assertInstanceOf(HttpClientInterface::class, $client);

        $ref = new \ReflectionObject($client);
        $prop = $ref->getProperty('defaultOptions');

        $options = $prop->getValue($client);
        $this->assertArrayHasKey('http_version', $options);
        $this->assertSame('3', $options['http_version']);
    }

    public function testCreateClientAddsCachingDecorator(): void
    {
        $factory = new HttpClientFactory(cacheStore: $this->createStub(TagAwareCacheInterface::class));

        $this->assertInstanceOf(CachingHttpClient::class, $factory->createClient());
    }

    public function testCreateClientAddsLoggingDecorator(): void
    {
        $factory = new HttpClientFactory(logger: $this->createStub(LoggerInterface::class));

        $this->assertInstanceOf(LoggingMiddleware::class, $factory->createClient());
    }
}
