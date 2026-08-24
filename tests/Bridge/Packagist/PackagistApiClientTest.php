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

namespace PackApi\Tests\Bridge\Packagist;

use PackApi\Bridge\Packagist\PackagistApiClient;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class PackagistApiClientTest extends TestCase
{
    public function testFetchPackageReturnsDecodedJson(): void
    {
        $data = ['package' => ['downloads' => ['monthly' => 5]]];
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn($json);

        $http = $this->createStub(HttpClientInterface::class);
        $http->method('request')->willReturn($response);

        $client = new PackagistApiClient($http);
        $result = $client->fetchPackage('vendor/name');

        $this->assertSame($data, $result);
    }

    public function testFetchDownloadsRangeReturnsDownloadsArray(): void
    {
        $downloads = [
            ['date' => '2023-01-01', 'download' => 10],
            ['date' => '2023-01-02', 'download' => 20],
        ];
        $data = ['downloads' => $downloads];
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn($json);

        $http = $this->createStub(HttpClientInterface::class);
        $http->method('request')->willReturn($response);

        $client = new PackagistApiClient($http);
        $start = new \DateTimeImmutable('2023-01-01');
        $end = new \DateTimeImmutable('2023-01-02');
        $result = $client->fetchDownloadsRange('vendor/name', $start, $end);

        $this->assertSame($data, $result);
    }

    public function testSearchPackagesDecodesJsonAndUsesQueryParameters(): void
    {
        $data = ['results' => [['name' => 'vendor/name']]];
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getContent')->with(false)->willReturn($json);

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('request')
            ->with('GET', 'search.json', [
                'query' => [
                    'q' => 'term',
                    'per_page' => 2,
                ],
            ])
            ->willReturn($response);

        $client = new PackagistApiClient($http);
        $result = $client->searchPackages('term', 2);

        $this->assertSame($data, $result);
    }

    public function testFetchHistoricalMonthlyDownloadsReturnsDecodedJson(): void
    {
        $data = ['labels' => ['2023-01'], 'values' => ['2023' => [1]]];
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getContent')->with(false)->willReturn($json);

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('request')
            ->with('GET', 'packages/vendor/name/stats/major/all.json?average=monthly&from=2023-01-01')
            ->willReturn($response);

        $client = new PackagistApiClient($http);
        $result = $client->fetchHistoricalMonthlyDownloads('vendor/name', new \DateTimeImmutable('2023-01-01'));

        $this->assertSame($data, $result);
    }
}
