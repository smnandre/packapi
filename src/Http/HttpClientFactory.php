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

namespace PackApi\Http;

use PackApi\Http\Middleware\LoggingMiddleware;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * HTTP client factory that creates a decorated client with caching and logging middleware.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class HttpClientFactory implements HttpClientFactoryInterface
{
    /**
     * @param object|null $cacheStore Store implementation from symfony/http-kernel (StoreInterface)
     */
    public function __construct(
        private readonly ?LoggerInterface $logger = null,
        private readonly ?object $cacheStore = null,
    ) {
    }

    public function createClient(array $options = []): HttpClientInterface
    {
        $httpOptions = [];

        if (!empty($options['enable_quic'])) {
            $httpOptions['http_version'] = '3';
            unset($options['enable_quic']);
        }

        $httpOptions = array_merge($httpOptions, $options);

        $client = HttpClient::create($httpOptions);

        if ($this->cacheStore && class_exists(CachingHttpClient::class)) {
            $client = new CachingHttpClient($client, $this->cacheStore);
        }

        if ($this->logger) {
            $client = new LoggingMiddleware($client, $this->logger);
        }

        return $client;
    }
}
