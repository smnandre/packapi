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
use PackApi\Bridge\Packagist\PackagistSearchProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

#[CoversClass(PackagistSearchProvider::class)]
final class PackagistSearchProviderTest extends TestCase
{
    private function getStubClient(array $responses = []): HttpClientInterface
    {
        return new class($responses) implements HttpClientInterface {
            public function __construct(private array $responses)
            {
            }

            public function request(string $method, string $url, array $options = []): ResponseInterface
            {
                $key = $method.' '.$url;
                [$status, $data] = $this->responses[$key] ?? [200, []];
                $content = json_encode($data);

                return new class($status, $content) implements ResponseInterface {
                    public function __construct(private int $status, private string $content)
                    {
                    }

                    public function getStatusCode(): int
                    {
                        return $this->status;
                    }

                    public function getHeaders(bool $throw = true): array
                    {
                        return [];
                    }

                    public function getContent(bool $throw = true): string
                    {
                        return $this->content;
                    }

                    public function toArray(bool $throw = true): array
                    {
                        return json_decode($this->content, true);
                    }

                    public function cancel(): void
                    {
                    }

                    public function getInfo(?string $type = null): mixed
                    {
                        return null;
                    }
                };
            }

            public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
            {
                throw new \LogicException('Not implemented');
            }

            public function withOptions(array $options): static
            {
                return $this;
            }
        };
    }

    public function testSearchReturnsNormalizedResults(): void
    {
        $data = [
            'results' => [
                [
                    'name' => 'vendor/package',
                    'description' => 'desc',
                    'repository' => 'https://example.com/vendor/package',
                ],
            ],
        ];

        $client = new PackagistApiClient($this->getStubClient([
            'GET search.json' => [200, $data],
        ]));
        $provider = new PackagistSearchProvider($client);

        $results = $provider->search('term', 2);

        $this->assertCount(1, $results);
        $this->assertSame('vendor/package', $results[0]['identifier']);
        $this->assertSame('desc', $results[0]['description']);
        $this->assertSame('https://example.com/vendor/package', $results[0]['repository']);
    }

    public function testSearchByKeywordDelegatesToSearch(): void
    {
        $client = new PackagistApiClient($this->getStubClient([
            'GET search.json' => [200, ['results' => [[
                'name' => 'vendor/package',
            ]]]],
        ]));
        $provider = new PackagistSearchProvider($client);

        $this->assertSame('vendor/package', $provider->searchByKeyword('vendor')[0]['identifier']);
    }

    public function testGetPopularReturnsEmptyArray(): void
    {
        $provider = new PackagistSearchProvider(new PackagistApiClient($this->getStubClient()));

        $this->assertSame([], $provider->getPopular());
    }
}
