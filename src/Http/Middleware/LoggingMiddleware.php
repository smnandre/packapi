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

namespace PackApi\Http\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class LoggingMiddleware implements HttpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $this->logger->info(
            sprintf('HttpClient Request: "%s %s"', $method, $url),
            ['options' => $options]
        );

        try {
            return $this->client->request($method, $url, $options);
        } catch (\Throwable $e) {
            $this->logger->info(
                sprintf('HttpClient Request failed: "%s %s"', $method, $url),
                ['exception' => $e]
            );
            throw $e;
        }
    }

    public function stream($responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): static
    {
        return new self($this->client->withOptions($options), $this->logger);
    }
}
