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

use PackApi\Exception\NetworkException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class BundlePhobiaApiClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Get bundle size information for a package.
     *
     * @return array<string, mixed>|null
     */
    public function getBundleSize(string $packageName, ?string $version = null): ?array
    {
        $packageParam = $version ? $packageName.'@'.$version : $packageName;

        try {
            $response = $this->httpClient->request('GET', 'api/size', [
                'query' => ['package' => $packageParam],
            ]);

            $statusCode = $response->getStatusCode();

            if (404 === $statusCode) {
                return null;
            }

            if (200 !== $statusCode) {
                throw new NetworkException(sprintf('BundlePhobia API returned status %d', $statusCode));
            }

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new NetworkException('Network error while calling BundlePhobia API', 0, $e);
        }
    }

    /**
     * Get detailed history information for a package.
     *
     * @return array<string, mixed>|null
     */
    public function getPackageHistory(string $packageName): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'api/package-history', [
                'query' => ['package' => $packageName],
            ]);

            $statusCode = $response->getStatusCode();

            if (404 === $statusCode) {
                return null;
            }

            if (200 !== $statusCode) {
                throw new NetworkException(sprintf('BundlePhobia API returned status %d', $statusCode));
            }

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new NetworkException('Network error while calling BundlePhobia API', 0, $e);
        }
    }
}
