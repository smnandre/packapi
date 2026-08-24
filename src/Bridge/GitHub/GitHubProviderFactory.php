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

namespace PackApi\Bridge\GitHub;

use PackApi\Http\HttpClientFactoryInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class GitHubProviderFactory
{
    private readonly GitHubApiClient $apiClient;

    public function __construct(
        HttpClientFactoryInterface $httpClientFactory,
        #[\SensitiveParameter]
        ?string $token = null,
    ) {
        $mainClient = $httpClientFactory->createClient();
        $authHeader = $token ? "Bearer {$token}" : '';
        $scopedClient = $mainClient->withOptions([
            'base_uri' => 'https://api.github.com/',
            'headers' => [
                'Authorization' => $authHeader,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'PackApi/1.0',
            ],
            'timeout' => 30,
        ]);

        $this->apiClient = new GitHubApiClient($scopedClient);
    }

    public function createSearchProvider(): GitHubSearchProvider
    {
        return new GitHubSearchProvider($this->apiClient);
    }

    public function createMetadataProvider(): GitHubMetadataProvider
    {
        return new GitHubMetadataProvider($this->apiClient);
    }

    public function createContentProvider(): GitHubContentProvider
    {
        return new GitHubContentProvider($this->apiClient);
    }

    public function createActivityProvider(): GitHubActivityProvider
    {
        return new GitHubActivityProvider($this->apiClient);
    }

    public function createSecurityProvider(): GitHubSecurityProvider
    {
        return new GitHubSecurityProvider($this->apiClient);
    }

    public function createStatisticProvider(): GitHubStatisticProvider
    {
        return new GitHubStatisticProvider($this->apiClient);
    }
}
