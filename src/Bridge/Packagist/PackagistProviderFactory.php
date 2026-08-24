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

namespace PackApi\Bridge\Packagist;

use PackApi\Http\HttpClientFactoryInterface;
use PackApi\Security\SecureFileHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class PackagistProviderFactory
{
    private readonly HttpClientInterface $scopedClient;
    private readonly PackagistApiClient $apiClient;
    private readonly HttpClientFactoryInterface $httpClientFactory;

    public function __construct(HttpClientFactoryInterface $httpClientFactory)
    {
        $this->httpClientFactory = $httpClientFactory;
        $mainClient = $httpClientFactory->createClient();

        $this->scopedClient = $mainClient->withOptions([
            'base_uri' => 'https://packagist.org/',
            'headers' => [
                'User-Agent' => 'PackApi/1.0',
            ],
            'timeout' => 30,
        ]);

        $this->apiClient = new PackagistApiClient($this->scopedClient);
    }

    public function createMetadataProvider(): PackagistMetadataProvider
    {
        return new PackagistMetadataProvider($this->apiClient);
    }

    public function createContentProvider(): PackagistContentProvider
    {
        $fileHandler = new SecureFileHandler($this->httpClientFactory->createClient());

        return new PackagistContentProvider($this->apiClient, $fileHandler);
    }

    public function createStatsProvider(): \PackApi\System\Composer\ComposerDownloadStatsProvider
    {
        return new \PackApi\System\Composer\ComposerDownloadStatsProvider($this->apiClient);
    }

    public function createSecurityProvider(): PackagistSecurityProvider
    {
        $authManager = new \PackApi\Auth\EnvAuthenticationManager('GITHUB_TOKEN');
        $token = $authManager->getGitHubToken();
        $githubApiClient = new \PackApi\Bridge\GitHub\GitHubApiClient(
            $this->httpClientFactory->createClient()->withOptions([
                'base_uri' => 'https://api.github.com/',
                'headers' => [
                    'Authorization' => $token ? 'Bearer '.$token : '',
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'PackApi/1.0',
                ],
                'timeout' => 30,
            ])
        );

        return new PackagistSecurityProvider($githubApiClient);
    }

    public function createActivityProvider(): PackagistActivityProvider
    {
        return new PackagistActivityProvider($this->apiClient);
    }

    public function createSearchProvider(): PackagistSearchProvider
    {
        return new PackagistSearchProvider($this->apiClient);
    }
}
