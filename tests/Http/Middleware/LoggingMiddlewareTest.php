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

namespace PackApi\Tests\Http\Middleware;

use PackApi\Http\Middleware\LoggingMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(LoggingMiddleware::class)]
final class LoggingMiddlewareTest extends TestCase
{
    public function testRequestHandlesExceptionAndStillLogsA(): void
    {
        $calls = [];
        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('info')->willReturnCallback(function ($message, $context) use (&$calls) {
            $calls[] = [$message, $context];
        });

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->will($this->throwException(new \Exception('fail')));

        $middleware = new LoggingMiddleware($client, $logger);

        $this->expectException(\Exception::class);
        $middleware->request('GET', 'https://example.com');

        $this->assertCount(2, $calls);
        $this->assertStringContainsString('HttpClient Request', $calls[0][0]);
        $this->assertArrayHasKey('options', $calls[0][1]);
        $this->assertStringContainsString('HttpClient Request failed', $calls[1][0]);
        $this->assertArrayHasKey('exception', $calls[1][1]);
    }
}
