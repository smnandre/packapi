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

use PackApi\Exception\NetworkException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class NpmApiClient
{
    public function __construct(
        private readonly HttpClientInterface $registryClient, // Scoped to registry.npmjs.org
        private readonly HttpClientInterface $statsClient,     // Scoped to api.npmjs.org
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchPackageInfo(string $name): ?array
    {
        try {
            // The base URI is already set by the scoped client (https://registry.npmjs.org/)
            $response = $this->registryClient->request('GET', $name);

            $statusCode = $response->getStatusCode();

            if (404 === $statusCode) {
                return null;
            }

            if (200 !== $statusCode) {
                throw new NetworkException(sprintf('NPM Registry API returned status %d', $statusCode));
            }

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new NetworkException('Network error while calling NPM Registry API', 0, $e);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchDownloadStats(string $name, string $period = 'last-month'): ?array
    {
        try {
            // The base URI is already set by the scoped client (https://api.npmjs.org/)
            $response = $this->statsClient->request('GET', 'downloads/point/'.$period.'/'.$name);

            $statusCode = $response->getStatusCode();

            if (404 === $statusCode) {
                return null;
            }

            if (200 !== $statusCode) {
                throw new NetworkException(sprintf('NPM Stats API returned status %d', $statusCode));
            }

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new NetworkException('Network error while calling NPM Stats API', 0, $e);
        }
    }
}
