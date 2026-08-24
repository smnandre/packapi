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

use PackApi\Http\Middleware\LoggingMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

#[CoversClass(LoggingMiddleware::class)]
final class LoggingMiddlewareTest extends TestCase
{
    private function getStubClient(ResponseInterface $response, ResponseStreamInterface $stream): HttpClientInterface
    {
        return new class($response, $stream) implements HttpClientInterface {
            public array $requestArgs = [];
            public array $streamArgs = [];
            public array $withOptionsArgs = [];
            public ?self $withOptionsReturn = null;

            public function __construct(private ResponseInterface $response, private ResponseStreamInterface $stream)
            {
            }

            public function request(string $method, string $url, array $options = []): ResponseInterface
            {
                $this->requestArgs = [$method, $url, $options];

                return $this->response;
            }

            public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
            {
                $this->streamArgs = [$responses, $timeout];

                return $this->stream;
            }

            public function withOptions(array $options): static
            {
                $this->withOptionsArgs = $options;
                $this->withOptionsReturn = new self($this->response, $this->stream);

                return $this->withOptionsReturn;
            }
        };
    }

    public function testRequestLogsMethodAndUrl(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $stream = $this->createStub(ResponseStreamInterface::class);
        $client = $this->getStubClient($response, $stream);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('HttpClient Request: "GET https://example.com/test"', ['options' => []]);

        $middleware = new LoggingMiddleware($client, $logger);
        $result = $middleware->request('GET', 'https://example.com/test');

        $refClient = new \ReflectionObject($client);
        $prop = $refClient->getProperty('requestArgs');
        $this->assertSame(['GET', 'https://example.com/test', []], $prop->getValue($client));
        $this->assertSame($response, $result);
    }

    public function testDelegatesRequestStreamAndWithOptions(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $stream = $this->createStub(ResponseStreamInterface::class);
        $client = $this->getStubClient($response, $stream);
        $logger = $this->createStub(LoggerInterface::class);

        $middleware = new LoggingMiddleware($client, $logger);

        // request delegation
        $result = $middleware->request('POST', '/foo', ['bar' => 1]);
        \assert(property_exists($client, 'requestArgs'));
        $this->assertSame(['POST', '/foo', ['bar' => 1]], $client->requestArgs);
        $this->assertSame($response, $result);

        // stream delegation
        $streamRes = $middleware->stream([$response], 5.0);
        $refClient = new \ReflectionObject($client);
        $prop = $refClient->getProperty('streamArgs');
        $this->assertSame([[$response], 5.0], $prop->getValue($client));
        $this->assertSame($stream, $streamRes);

        // withOptions delegation
        $new = $middleware->withOptions(['timeout' => 10]);
        $this->assertNotSame($middleware, $new);
        $refClient = new \ReflectionObject($client);
        $prop = $refClient->getProperty('withOptionsArgs');
        $this->assertSame(['timeout' => 10], $prop->getValue($client));

        // check that new middleware wraps the new client instance
        $ref = new \ReflectionObject($new);
        $prop = $ref->getProperty('client');
        $refClient = new \ReflectionObject($client);
        $withRetProp = $refClient->getProperty('withOptionsReturn');
        $this->assertSame($withRetProp->getValue($client), $prop->getValue($new));
    }
}
