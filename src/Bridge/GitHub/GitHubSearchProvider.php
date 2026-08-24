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

use PackApi\Provider\PackageSearchInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class GitHubSearchProvider implements PackageSearchInterface
{
    public function __construct(private readonly GitHubApiClient $client)
    {
    }

    /**
     * @return array<int, array{identifier: string, name: string, description?: string|null, repository?: string|null}>
     */
    public function search(string $query, int $limit = 20): array
    {
        $data = $this->client->searchRepositories($query, $limit);

        $results = [];
        foreach ($data['items'] ?? [] as $item) {
            $results[] = [
                'identifier' => $item['full_name'],
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'repository' => $item['html_url'] ?? null,
            ];
        }

        return $results;
    }

    /**
     * @return array<int, array{identifier: string, name: string, description?: string|null, repository?: string|null}>
     */
    public function searchByKeyword(string $keyword): array
    {
        return $this->search($keyword);
    }

    /**
     * @return array<int, array{identifier: string, name: string, description?: string|null, repository?: string|null}>
     */
    public function getPopular(int $limit = 50): array
    {
        // GitHub API does not have a direct 'popular' endpoint.
        // We can simulate by fetching repositories sorted by stars.
        $data = $this->client->searchRepositories('stars:>1', $limit, 'stars', 'desc');

        $results = [];
        foreach ($data['items'] ?? [] as $item) {
            $results[] = [
                'identifier' => $item['full_name'],
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'repository' => $item['html_url'] ?? null,
            ];
        }

        return $results;
    }
}
