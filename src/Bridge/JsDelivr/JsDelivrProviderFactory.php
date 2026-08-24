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

namespace PackApi\Bridge\JsDelivr;

use PackApi\Http\HttpClientFactoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class JsDelivrProviderFactory
{
    private readonly HttpClientInterface $scopedClient;
    private readonly JsDelivrApiClient $apiClient;

    public function __construct(HttpClientFactoryInterface $httpClientFactory)
    {
        $mainClient = $httpClientFactory->createClient();

        $this->scopedClient = $mainClient->withOptions([
            'base_uri' => 'https://data.jsdelivr.com/',
            'headers' => [
                'User-Agent' => 'PackApi/1.0',
            ],
            'timeout' => 30,
        ]);

        $this->apiClient = new JsDelivrApiClient($this->scopedClient);
    }

    public function createMetadataProvider(): JsDelivrMetadataProvider
    {
        return new JsDelivrMetadataProvider($this->apiClient);
    }

    public function createStatsProvider(): JsDelivrStatsProvider
    {
        return new JsDelivrStatsProvider($this->apiClient);
    }

    public function createContentProvider(): JsDelivrContentProvider
    {
        return new JsDelivrContentProvider($this->apiClient);
    }
}
