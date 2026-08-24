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

namespace PackApi\Bridge\BundlePhobia;

use PackApi\Http\HttpClientFactoryInterface;
use PackApi\Provider\BundleSizeProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class BundlePhobiaProviderFactory
{
    private readonly HttpClientInterface $scopedClient;
    private readonly BundlePhobiaApiClient $apiClient;

    public function __construct(HttpClientFactoryInterface $httpClientFactory)
    {
        $mainClient = $httpClientFactory->createClient();

        $this->scopedClient = $mainClient->withOptions([
            'base_uri' => 'https://bundlephobia.com/',
            'headers' => [
                'User-Agent' => 'PackApi/1.0',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        $this->apiClient = new BundlePhobiaApiClient($this->scopedClient);
    }

    /**
     * @return list<class-string>
     */
    public function provides(): array
    {
        return [
            BundleSizeProviderInterface::class,
        ];
    }

    public function create(string $interface): object
    {
        return match ($interface) {
            BundleSizeProviderInterface::class => $this->createBundleSizeProvider(),
            default => throw new \LogicException("Unsupported interface $interface"),
        };
    }

    public function createBundleSizeProvider(): BundlePhobiaSizeProvider
    {
        return new BundlePhobiaSizeProvider($this->apiClient);
    }

    /**
     * Get the underlying BundlePhobia API client for advanced usage.
     */
    public function getApiClient(): BundlePhobiaApiClient
    {
        return $this->apiClient;
    }
}
