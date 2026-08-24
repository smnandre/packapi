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

use PackApi\Exception\NetworkException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class OSVApiClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Query vulnerabilities for a package.
     *
     * @param string      $ecosystem The package ecosystem (e.g., 'npm', 'Packagist')
     * @param string      $name      The package name
     * @param string|null $version   Optional specific version to check
     */
    /**
     * @return array<string, mixed>|null
     */
    public function queryVulnerabilities(string $ecosystem, string $name, ?string $version = null): ?array
    {
        $requestBody = [
            'package' => [
                'ecosystem' => $ecosystem,
                'name' => $name,
            ],
        ];

        if (null !== $version) {
            $requestBody['version'] = $version;
        }

        try {
            $response = $this->httpClient->request('POST', 'v1/query', [
                'json' => $requestBody,
            ]);

            $statusCode = $response->getStatusCode();

            if (404 === $statusCode) {
                return null;
            }

            if (200 !== $statusCode) {
                throw new NetworkException(sprintf('OSV API returned status %d', $statusCode));
            }

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new NetworkException('Network error while calling OSV API', 0, $e);
        }
    }

    /**
     * Get vulnerability by ID.
     *
     * @param string $id The OSV vulnerability ID
     */
    /**
     * @return array<string, mixed>|null
     */
    public function getVulnerabilityById(string $id): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'v1/vulns/'.urlencode($id));

            $statusCode = $response->getStatusCode();

            if (404 === $statusCode) {
                return null;
            }

            if (200 !== $statusCode) {
                throw new NetworkException(sprintf('OSV API returned status %d', $statusCode));
            }

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new NetworkException('Network error while calling OSV API', 0, $e);
        }
    }

    /**
     * Batch query vulnerabilities for multiple packages.
     *
     * @param array $packages Array of ['ecosystem' => string, 'name' => string, 'version' => ?string]
     */
    /**
     * @param array<int, array{ecosystem: string, name: string, version?: string}> $packages
     *
     * @return array<string, mixed>
     */
    public function batchQueryVulnerabilities(array $packages): array
    {
        $queries = [];
        foreach ($packages as $package) {
            $query = [
                'package' => [
                    'ecosystem' => $package['ecosystem'],
                    'name' => $package['name'],
                ],
            ];

            if (isset($package['version'])) {
                $query['version'] = $package['version'];
            }

            $queries[] = $query;
        }

        try {
            $response = $this->httpClient->request('POST', 'v1/querybatch', [
                'json' => ['queries' => $queries],
            ]);

            $statusCode = $response->getStatusCode();

            if (200 !== $statusCode) {
                throw new NetworkException(sprintf('OSV API returned status %d', $statusCode));
            }

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new NetworkException('Network error while calling OSV API', 0, $e);
        }
    }
}
