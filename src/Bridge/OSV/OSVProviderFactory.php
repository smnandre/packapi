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

namespace PackApi\Bridge\OSV;

use PackApi\Http\HttpClientFactoryInterface;
use PackApi\Provider\SecurityProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class OSVProviderFactory
{
    private readonly HttpClientInterface $scopedClient;
    private readonly OSVApiClient $apiClient;

    public function __construct(HttpClientFactoryInterface $httpClientFactory)
    {
        $mainClient = $httpClientFactory->createClient();

        $this->scopedClient = $mainClient->withOptions([
            'base_uri' => 'https://api.osv.dev/',
            'headers' => [
                'User-Agent' => 'PackApi/1.0',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        $this->apiClient = new OSVApiClient($this->scopedClient);
    }

    /**
     * @return list<class-string>
     */
    public function provides(): array
    {
        return [
            SecurityProviderInterface::class,
        ];
    }

    public function create(string $interface): object
    {
        return match ($interface) {
            SecurityProviderInterface::class => $this->createSecurityProvider(),
            default => throw new \LogicException("Unsupported interface $interface"),
        };
    }

    public function createSecurityProvider(): OSVSecurityProvider
    {
        return new OSVSecurityProvider($this->apiClient);
    }

    /**
     * Get the underlying OSV API client for advanced usage.
     */
    public function getApiClient(): OSVApiClient
    {
        return $this->apiClient;
    }
}
