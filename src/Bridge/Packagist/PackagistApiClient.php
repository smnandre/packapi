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

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class PackagistApiClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchPackage(string $packageName): array
    {
        $response = $this->httpClient->request('GET', "packages/{$packageName}.json");
        $content = $response->getContent(false);

        return $content ? json_decode($content, true, 512, JSON_THROW_ON_ERROR) : [];
    }

    /**
     * Fetch download data for a date range: returns daily downloads.
     *
     * @return array{downloads?: list<array{date: string, download: int}>}
     */
    public function fetchDownloadsRange(string $packageName, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');

        $response = $this->httpClient->request('GET', "downloads/range/{$startDate}/{$endDate}/{$packageName}.json");
        $content = $response->getContent(false);

        return $content ? json_decode($content, true, 512, JSON_THROW_ON_ERROR) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchPackages(string $query, int $limit = 20): array
    {
        $response = $this->httpClient->request('GET', 'search.json', [
            'query' => [
                'q' => $query,
                'per_page' => $limit,
            ],
        ]);
        $content = $response->getContent(false);

        return $content ? json_decode($content, true, 512, JSON_THROW_ON_ERROR) : [];
    }

    /**
     * Fetch historical monthly download data for a package.
     *
     * @return array{labels: string[], values: array<string, int[]>}
     */
    public function fetchHistoricalMonthlyDownloads(string $packageName, \DateTimeImmutable $from): array
    {
        $fromDate = $from->format('Y-m-d');

        $response = $this->httpClient->request('GET', "packages/{$packageName}/stats/major/all.json?average=monthly&from={$fromDate}");
        $content = $response->getContent(false);

        return $content ? json_decode($content, true, 512, JSON_THROW_ON_ERROR) : [];
    }
}
