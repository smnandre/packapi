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

use PackApi\Exception\ApiException;
use PackApi\Exception\NetworkException;
use PackApi\Exception\ValidationException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class GitHubApiClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function extractRepoName(string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $patterns = [
            '/github\.com[\/:]([^\/]+)\/([^\/\.]+)(?:\.git)?(?:\/.*)?$/i',
            '/^([^\/]+)\/([^\/]+)$/', // owner/repo format
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1].'/'.$matches[2];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchRepoMetadata(string $repoName): ?array
    {
        if (!$this->validateRepoName($repoName)) {
            throw new ValidationException("Invalid repository name format: {$repoName}");
        }

        try {
            $response = $this->makeRequest('GET', "/repos/{$repoName}");

            return $response;
        } catch (ApiException $e) {
            if (404 === $e->httpCode) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchRepoActivity(string $repoName): ?array
    {
        if (!$this->validateRepoName($repoName)) {
            throw new ValidationException("Invalid repository name format: {$repoName}");
        }

        try {
            $repoData = $this->fetchRepoMetadata($repoName);
            if (!$repoData) {
                return null;
            }

            /** @var list<array<string, mixed>> $commits */
            $commits = $this->makeRequest('GET', "/repos/{$repoName}/commits", [
                'per_page' => 100,
                'since' => date('c', strtotime('-1 year')),
            ]);

            /** @var list<array<string, mixed>> $contributors */
            $contributors = $this->makeRequest('GET', "/repos/{$repoName}/contributors", [
                'per_page' => 50,
            ]);

            /** @var list<array<string, mixed>> $releases */
            $releases = $this->makeRequest('GET', "/repos/{$repoName}/releases", [
                'per_page' => 10,
            ]);

            $lastCommitDate = null;
            if (count($commits) > 0) {
                $firstCommit = $commits[0];
                $lastCommitDate = $firstCommit['commit']['committer']['date'] ?? null;
            }

            $lastReleaseDate = null;
            if (count($releases) > 0) {
                $firstRelease = $releases[0];
                $lastReleaseDate = $firstRelease['published_at'] ?? null;
            }

            return [
                'repository' => $repoData,
                'commits' => $commits,
                'contributors' => $contributors,
                'releases' => $releases,
                'activity_stats' => [
                    'commit_count_last_year' => count($commits),
                    'contributor_count' => count($contributors),
                    'release_count' => count($releases),
                    'last_commit_date' => $lastCommitDate,
                    'last_release_date' => $lastReleaseDate,
                ],
            ];
        } catch (ApiException $e) {
            if (404 === $e->httpCode) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchSecurityAdvisories(string $repoName): ?array
    {
        if (!$this->validateRepoName($repoName)) {
            throw new ValidationException("Invalid repository name format: {$repoName}");
        }

        try {
            $advisories = $this->makeRequest('GET', "/repos/{$repoName}/security-advisories", [
                'state' => 'published',
                'per_page' => 100,
            ]);

            $vulnerabilityAlerts = null;
            try {
                $vulnerabilityAlerts = $this->makeRequest('GET', "/repos/{$repoName}/vulnerability-alerts");
            } catch (ApiException $e) {
                // Ignore if we don't have permissions to access vulnerability alerts
                if (403 !== $e->httpCode && 404 !== $e->httpCode) {
                    throw $e;
                }
            }

            return [
                'security_advisories' => $advisories,
                'vulnerability_alerts' => $vulnerabilityAlerts,
                'advisory_count' => count($advisories),
                'has_security_policy' => $this->hasSecurityPolicy($repoName),
            ];
        } catch (ApiException $e) {
            if (404 === $e->httpCode) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchRepoFiles(string $repoName): ?array
    {
        if (!$this->validateRepoName($repoName)) {
            throw new ValidationException("Invalid repository name format: {$repoName}");
        }

        try {
            $repoData = $this->fetchRepoMetadata($repoName);
            if (!$repoData) {
                return null;
            }

            $defaultBranch = $repoData['default_branch'] ?? 'main';

            $contents = $this->makeRequest('GET', "/repos/{$repoName}/contents", [
                'ref' => $defaultBranch,
            ]);

            $importantFiles = [];
            $filesToCheck = ['README.md', 'LICENSE', 'SECURITY.md', 'composer.json', 'package.json'];

            foreach ($filesToCheck as $fileName) {
                try {
                    $fileData = $this->makeRequest('GET', "/repos/{$repoName}/contents/{$fileName}", [
                        'ref' => $defaultBranch,
                    ]);
                    $importantFiles[$fileName] = $fileData;
                } catch (ApiException $e) {
                    if (404 !== $e->httpCode) {
                        throw $e;
                    }
                }
            }

            return [
                'contents' => $contents,
                'important_files' => $importantFiles,
                'default_branch' => $defaultBranch,
                'file_count' => count($contents),
                'has_readme' => isset($importantFiles['README.md']),
                'has_license' => isset($importantFiles['LICENSE']),
                'has_security_policy' => isset($importantFiles['SECURITY.md']),
            ];
        } catch (ApiException $e) {
            if (404 === $e->httpCode) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchRepoContents(string $repoName, string $path = ''): ?array
    {
        if (!$this->validateRepoName($repoName)) {
            throw new ValidationException("Invalid repository name format: {$repoName}");
        }

        try {
            $endpoint = "/repos/{$repoName}/contents/{$path}";

            return $this->makeRequest('GET', $endpoint);
        } catch (ApiException $e) {
            if (404 === $e->httpCode) {
                return null;
            }
            throw $e;
        }
    }

    public function fetchFileContent(string $repoName, string $path): ?string
    {
        $fileData = $this->fetchRepoContents($repoName, $path);

        if (null === $fileData || !isset($fileData['content'])) {
            return null;
        }

        return base64_decode($fileData['content'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function searchRepositories(string $query, int $limit = 30, ?string $sort = null, ?string $order = null): array
    {
        $params = [
            'q' => $query,
            'per_page' => $limit,
        ];

        if ($sort) {
            $params['sort'] = $sort;
        }
        if ($order) {
            $params['order'] = $order;
        }

        try {
            return $this->makeRequest('GET', '/search/repositories', $params);
        } catch (ApiException $e) {
            throw $e;
        }
    }

    private function hasSecurityPolicy(string $repoName): bool
    {
        try {
            $this->makeRequest('GET', "/repos/{$repoName}/contents/SECURITY.md");

            return true;
        } catch (ApiException $e) {
            if (404 === $e->httpCode) {
                return false;
            }
            throw $e;
        }
    }

    private function validateRepoName(string $repoName): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9._-]+\/[a-zA-Z0-9._-]+$/', $repoName);
    }

    /**
     * @param array<string, scalar> $params
     *
     * @return array<string, mixed>
     */
    private function makeRequest(string $method, string $endpoint, array $params = []): array
    {
        $options = [];

        if ('GET' === $method && !empty($params)) {
            $options['query'] = $params;
        }

        try {
            $response = $this->httpClient->request($method, $endpoint, $options);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                $content = $response->getContent(false);
                $data = json_decode($content, true);

                throw new ApiException('GitHub API error: '.($data['message'] ?? 'Unknown error'), 0, null, $statusCode, $data);
            }

            $content = $response->getContent();
            $data = json_decode($content, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new ApiException('Invalid JSON response from GitHub API');
            }

            return $data;
        } catch (TransportExceptionInterface $e) {
            throw new NetworkException('Network error while calling GitHub API: '.$e->getMessage(), 0, $e);
        } catch (ClientExceptionInterface|ServerExceptionInterface $e) {
            throw new ApiException('HTTP error while calling GitHub API: '.$e->getMessage(), 0, $e);
        }
    }
}
