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

namespace PackApi\Bridge\Npm;

use PackApi\Http\HttpClientFactoryInterface;
use PackApi\Provider\ContentProviderInterface;
use PackApi\Provider\DownloadStatsProviderInterface;
use PackApi\Provider\MetadataProviderInterface;
use PackApi\System\Npm\NpmContentProvider;
use PackApi\System\Npm\NpmDownloadStatsProvider;
use PackApi\System\Npm\NpmMetadataProvider;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class NpmProviderFactory
{
    private readonly HttpClientInterface $registryClient;
    private readonly HttpClientInterface $statsClient;
    private readonly NpmApiClient $apiClient;

    public function __construct(HttpClientFactoryInterface $httpClientFactory)
    {
        $mainClient = $httpClientFactory->createClient();

        $this->registryClient = $mainClient->withOptions([
            'base_uri' => 'https://registry.npmjs.org/',
            'headers' => [
                'User-Agent' => 'PackApi/1.0',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        $this->statsClient = $mainClient->withOptions([
            'base_uri' => 'https://api.npmjs.org/',
            'headers' => [
                'User-Agent' => 'PackApi/1.0',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        $this->apiClient = new NpmApiClient($this->registryClient, $this->statsClient);
    }

    /**
     * @return list<class-string>
     */
    public function provides(): array
    {
        return [
            MetadataProviderInterface::class,
            DownloadStatsProviderInterface::class,
            ContentProviderInterface::class,
        ];
    }

    public function create(string $interface): object
    {
        return match ($interface) {
            MetadataProviderInterface::class => $this->createMetadataProvider(),
            DownloadStatsProviderInterface::class => $this->createDownloadStatsProvider(),
            ContentProviderInterface::class => $this->createContentProvider(),
            default => throw new \LogicException("Unsupported interface $interface"),
        };
    }

    public function createMetadataProvider(): NpmMetadataProvider
    {
        return new NpmMetadataProvider($this->apiClient);
    }

    public function createDownloadStatsProvider(): NpmDownloadStatsProvider
    {
        return new NpmDownloadStatsProvider($this->apiClient);
    }

    public function createContentProvider(): NpmContentProvider
    {
        return new NpmContentProvider($this->apiClient);
    }
}
