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

namespace PackApi\Tests\Bridge\BundlePhobia;

use PackApi\Bridge\BundlePhobia\BundlePhobiaApiClient;
use PackApi\Exception\NetworkException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(BundlePhobiaApiClient::class)]
final class BundlePhobiaApiClientTest extends TestCase
{
    private MockHttpClient $httpClient;
    private BundlePhobiaApiClient $client;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->client = new BundlePhobiaApiClient($this->httpClient);
    }

    public function testGetBundleSizeReturnsDataOnSuccess(): void
    {
        $responseData = [
            'name' => 'react',
            'version' => '18.2.0',
            'description' => 'React is a JavaScript library for building user interfaces.',
            'size' => 42000,
            'gzip' => 13000,
            'dependencyCount' => 0,
            'dependencySizes' => 0,
            'hasJSModule' => false,
            'hasSideEffects' => true,
            'scoped' => false,
            'repository' => [
                'url' => 'https://github.com/facebook/react.git',
            ],
        ];

        $this->httpClient = new MockHttpClient([
            new MockResponse(json_encode($responseData), ['http_code' => 200]),
        ]);
        $this->client = new BundlePhobiaApiClient($this->httpClient);

        $result = $this->client->getBundleSize('react');

        $this->assertSame($responseData, $result);
    }

    public function testGetBundleSizeReturnsNullOn404(): void
    {
        $this->httpClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ]);
        $this->client = new BundlePhobiaApiClient($this->httpClient);

        $result = $this->client->getBundleSize('nonexistent-package');

        $this->assertNull($result);
    }

    public function testGetBundleSizeThrowsNetworkExceptionOnError(): void
    {
        $this->httpClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]);
        $this->client = new BundlePhobiaApiClient($this->httpClient);

        $this->expectException(NetworkException::class);
        $this->client->getBundleSize('test-package');
    }

    public function testGetBundleSizeWithVersion(): void
    {
        $responseData = [
            'name' => 'react',
            'version' => '17.0.2',
            'size' => 39000,
            'gzip' => 12000,
        ];

        $this->httpClient = new MockHttpClient([
            new MockResponse(json_encode($responseData), ['http_code' => 200]),
        ]);
        $this->client = new BundlePhobiaApiClient($this->httpClient);

        $result = $this->client->getBundleSize('react', '17.0.2');

        $this->assertSame($responseData, $result);
    }

    public function testGetBundleSizeWrapsTransportErrors(): void
    {
        $transport = new class extends \RuntimeException implements TransportExceptionInterface {};
        $http = $this->createStub(HttpClientInterface::class);
        $http->method('request')->willThrowException($transport);
        $client = new BundlePhobiaApiClient($http);

        $this->expectException(NetworkException::class);
        $client->getBundleSize('react');
    }

    public function testGetPackageHistoryReturnsData(): void
    {
        $responseData = [
            'name' => 'react',
            'versions' => [
                ['version' => '18.2.0', 'size' => 42000, 'gzip' => 13000],
                ['version' => '18.1.0', 'size' => 41500, 'gzip' => 12800],
                ['version' => '18.0.0', 'size' => 41000, 'gzip' => 12500],
            ],
        ];

        $this->httpClient = new MockHttpClient([
            new MockResponse(json_encode($responseData), ['http_code' => 200]),
        ]);
        $this->client = new BundlePhobiaApiClient($this->httpClient);

        $result = $this->client->getPackageHistory('react');

        $this->assertSame($responseData, $result);
    }

    public function testGetPackageHistoryReturnsNullOn404(): void
    {
        $this->httpClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ]);
        $this->client = new BundlePhobiaApiClient($this->httpClient);

        $result = $this->client->getPackageHistory('nonexistent-package');

        $this->assertNull($result);
    }

    public function testGetPackageHistoryThrowsNetworkExceptionOnError(): void
    {
        $client = new BundlePhobiaApiClient(new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]));

        $this->expectException(NetworkException::class);
        $client->getPackageHistory('react');
    }

    public function testGetPackageHistoryWrapsTransportErrors(): void
    {
        $transport = new class extends \RuntimeException implements TransportExceptionInterface {};
        $http = $this->createStub(HttpClientInterface::class);
        $http->method('request')->willThrowException($transport);
        $client = new BundlePhobiaApiClient($http);

        $this->expectException(NetworkException::class);
        $client->getPackageHistory('react');
    }
}
