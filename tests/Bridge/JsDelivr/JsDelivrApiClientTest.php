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

namespace PackApi\Tests\Bridge\JsDelivr;

use PackApi\Bridge\JsDelivr\JsDelivrApiClient;
use PackApi\Exception\NetworkException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(JsDelivrApiClient::class)]
final class JsDelivrApiClientTest extends TestCase
{
    public function testFetchPackageMetaReturnsData(): void
    {
        $data = ['name' => 'package'];
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        $client = new JsDelivrApiClient(new MockHttpClient([
            new MockResponse($json, ['http_code' => 200]),
        ]));

        $this->assertSame($data, $client->fetchPackageMeta('npm/package'));
    }

    public function testFetchPackageMetaReturnsNullOn404(): void
    {
        $client = new JsDelivrApiClient(new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ]));

        $this->assertNull($client->fetchPackageMeta('npm/unknown'));
    }

    public function testFetchPackageMetaThrowsOnError(): void
    {
        $client = new JsDelivrApiClient(new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]));

        $this->expectException(NetworkException::class);
        $client->fetchPackageMeta('npm/package');
    }

    public function testFetchPackageMetaThrowsOnTransportException(): void
    {
        $transport = new class extends \RuntimeException implements TransportExceptionInterface {};
        $http = $this->createStub(HttpClientInterface::class);
        $http->method('request')->willThrowException($transport);

        $client = new JsDelivrApiClient($http);

        $this->expectException(NetworkException::class);
        $client->fetchPackageMeta('npm/package');
    }

    public function testFetchFileListReturnsData(): void
    {
        $data = ['files' => [['name' => 'index.js']]];
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        $client = new JsDelivrApiClient(new MockHttpClient([
            new MockResponse($json, ['http_code' => 200]),
        ]));

        $this->assertSame($data, $client->fetchFileList('npm/package'));
    }

    public function testFetchFileListWithVersionUsesCorrectPath(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->with(false)->willReturn('{}');

        $captured = null;
        $http = new MockHttpClient(function (string $method, string $url) use ($response, &$captured) {
            $captured = $url;

            return $response;
        });

        $client = new JsDelivrApiClient($http);
        $client->fetchFileList('npm/package', '1.0.0');

        $this->assertStringEndsWith('v1/package/npm/package@1.0.0/flat', $captured);
    }

    public function testFetchFileListReturnsNullOn404(): void
    {
        $client = new JsDelivrApiClient(new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ]));

        $this->assertNull($client->fetchFileList('npm/package'));
    }

    public function testFetchFileListThrowsOnError(): void
    {
        $client = new JsDelivrApiClient(new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]));

        $this->expectException(NetworkException::class);
        $client->fetchFileList('npm/package');
    }

    public function testFetchFileListThrowsOnTransportException(): void
    {
        $transport = new class extends \RuntimeException implements TransportExceptionInterface {};
        $http = $this->createStub(HttpClientInterface::class);
        $http->method('request')->willThrowException($transport);

        $client = new JsDelivrApiClient($http);

        $this->expectException(NetworkException::class);
        $client->fetchFileList('npm/package');
    }

    public function testFetchPackageStatsReturnsData(): void
    {
        $data = ['hits' => ['dates' => ['2026-07-10' => 42]]];
        $client = new JsDelivrApiClient(new MockHttpClient([
            new MockResponse(json_encode($data, JSON_THROW_ON_ERROR), ['http_code' => 200]),
        ]));

        $this->assertSame($data, $client->fetchPackageStats('npm', 'package'));
    }

    public function testFetchPackageStatsUsesStatsEndpoint(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->with(false)->willReturn('{}');

        $captured = null;
        $http = new MockHttpClient(function (string $method, string $url) use ($response, &$captured) {
            $captured = $url;

            return $response;
        });

        (new JsDelivrApiClient($http))->fetchPackageStats('gh', 'Alamofire/Alamofire');

        $this->assertStringEndsWith('v1/stats/packages/gh/Alamofire/Alamofire', $captured);
    }

    public function testFetchPackageStatsReturnsNullOn404(): void
    {
        $client = new JsDelivrApiClient(new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ]));

        $this->assertNull($client->fetchPackageStats('npm', 'unknown'));
    }

    public function testFetchPackageStatsThrowsOnError(): void
    {
        $client = new JsDelivrApiClient(new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]));

        $this->expectException(NetworkException::class);
        $client->fetchPackageStats('npm', 'package');
    }

    public function testFetchPackageStatsThrowsOnTransportException(): void
    {
        $transport = new class extends \RuntimeException implements TransportExceptionInterface {};
        $http = $this->createStub(HttpClientInterface::class);
        $http->method('request')->willThrowException($transport);

        $client = new JsDelivrApiClient($http);

        $this->expectException(NetworkException::class);
        $client->fetchPackageStats('npm', 'package');
    }
}
