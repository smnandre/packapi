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

namespace PackApi\Tests\Bridge\OSV;

use PackApi\Bridge\OSV\OSVApiClient;
use PackApi\Exception\NetworkException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversClass(OSVApiClient::class)]
final class OSVApiClientTest extends TestCase
{
    private MockHttpClient $httpClient;
    private OSVApiClient $client;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->client = new OSVApiClient($this->httpClient);
    }

    public function testQueryVulnerabilitiesReturnsDataOnSuccess(): void
    {
        $responseData = [
            'vulns' => [
                [
                    'id' => 'OSV-2023-1234',
                    'summary' => 'Test vulnerability',
                    'severity' => [
                        ['score' => 7.5],
                    ],
                ],
            ],
        ];

        $this->httpClient = new MockHttpClient([
            new MockResponse(json_encode($responseData), ['http_code' => 200]),
        ]);
        $this->client = new OSVApiClient($this->httpClient);

        $result = $this->client->queryVulnerabilities('npm', 'test-package');

        $this->assertSame($responseData, $result);
    }

    public function testQueryVulnerabilitiesReturnsNullOn404(): void
    {
        $this->httpClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ]);
        $this->client = new OSVApiClient($this->httpClient);

        $result = $this->client->queryVulnerabilities('npm', 'test-package');

        $this->assertNull($result);
    }

    public function testQueryVulnerabilitiesThrowsNetworkExceptionOnError(): void
    {
        $this->httpClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]);
        $this->client = new OSVApiClient($this->httpClient);

        $this->expectException(NetworkException::class);
        $this->client->queryVulnerabilities('npm', 'test-package');
    }

    public function testQueryVulnerabilitiesWithVersion(): void
    {
        $responseData = ['vulns' => []];

        $this->httpClient = new MockHttpClient([
            new MockResponse(json_encode($responseData), ['http_code' => 200]),
        ]);
        $this->client = new OSVApiClient($this->httpClient);

        $result = $this->client->queryVulnerabilities('npm', 'test-package', '1.0.0');

        $this->assertSame($responseData, $result);
    }

    public function testQueryVulnerabilitiesWrapsTransportErrors(): void
    {
        $transport = new class extends \RuntimeException implements TransportExceptionInterface {};
        $http = $this->createStub(HttpClientInterface::class);
        $http->method('request')->willThrowException($transport);
        $client = new OSVApiClient($http);

        $this->expectException(NetworkException::class);
        $client->queryVulnerabilities('npm', 'test-package');
    }

    public function testGetVulnerabilityByIdReturnsData(): void
    {
        $responseData = [
            'id' => 'OSV-2023-1234',
            'summary' => 'Test vulnerability',
            'details' => 'Detailed description',
        ];

        $this->httpClient = new MockHttpClient([
            new MockResponse(json_encode($responseData), ['http_code' => 200]),
        ]);
        $this->client = new OSVApiClient($this->httpClient);

        $result = $this->client->getVulnerabilityById('OSV-2023-1234');

        $this->assertSame($responseData, $result);
    }

    public function testGetVulnerabilityByIdReturnsNullOn404(): void
    {
        $this->httpClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ]);
        $this->client = new OSVApiClient($this->httpClient);

        $result = $this->client->getVulnerabilityById('OSV-2023-1234');

        $this->assertNull($result);
    }

    public function testGetVulnerabilityByIdThrowsNetworkExceptionOnError(): void
    {
        $client = new OSVApiClient(new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]));

        $this->expectException(NetworkException::class);
        $client->getVulnerabilityById('OSV-2023-1234');
    }

    public function testGetVulnerabilityByIdWrapsTransportErrors(): void
    {
        $transport = new class extends \RuntimeException implements TransportExceptionInterface {};
        $http = $this->createStub(HttpClientInterface::class);
        $http->method('request')->willThrowException($transport);
        $client = new OSVApiClient($http);

        $this->expectException(NetworkException::class);
        $client->getVulnerabilityById('OSV-2023-1234');
    }

    public function testBatchQueryVulnerabilities(): void
    {
        $responseData = [
            'results' => [
                ['vulns' => []],
                ['vulns' => []],
            ],
        ];

        $this->httpClient = new MockHttpClient([
            new MockResponse(json_encode($responseData), ['http_code' => 200]),
        ]);
        $this->client = new OSVApiClient($this->httpClient);

        $packages = [
            ['ecosystem' => 'npm', 'name' => 'package1'],
            ['ecosystem' => 'npm', 'name' => 'package2', 'version' => '1.0.0'],
        ];

        $result = $this->client->batchQueryVulnerabilities($packages);

        $this->assertSame($responseData, $result);
    }

    public function testBatchQueryVulnerabilitiesThrowsNetworkExceptionOnError(): void
    {
        $client = new OSVApiClient(new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]));

        $this->expectException(NetworkException::class);
        $client->batchQueryVulnerabilities([]);
    }

    public function testBatchQueryVulnerabilitiesWrapsTransportErrors(): void
    {
        $transport = new class extends \RuntimeException implements TransportExceptionInterface {};
        $http = $this->createStub(HttpClientInterface::class);
        $http->method('request')->willThrowException($transport);
        $client = new OSVApiClient($http);

        $this->expectException(NetworkException::class);
        $client->batchQueryVulnerabilities([]);
    }
}
