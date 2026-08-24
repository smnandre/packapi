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
use PackApi\Bridge\Packagist\PackagistMetadataProvider;
use PackApi\Model\Metadata;
use PackApi\Package\ComposerPackage;
use PackApi\Package\NpmPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

#[CoversClass(PackagistMetadataProvider::class)]
final class PackagistMetadataProviderTest extends TestCase
{
    private PackagistApiClient $apiClient;
    private PackagistMetadataProvider $provider;

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

    protected function setUp(): void
    {
        $this->apiClient = new PackagistApiClient($this->getStubClient());
        $this->provider = new PackagistMetadataProvider($this->apiClient);
    }

    public function testSupportsComposerPackageOnly(): void
    {
        $composer = new ComposerPackage('vendor/package');
        $npm = new NpmPackage('foo');

        $this->assertTrue($this->provider->supports($composer));
        $this->assertFalse($this->provider->supports($npm));
    }

    public function testGetMetadataReturnsModel(): void
    {
        $package = new ComposerPackage('vendor/package');
        $apiData = [
            'package' => [
                'name' => 'vendor/package',
                'description' => 'desc',
                'license' => ['MIT'],
                'repository' => 'https://example.com/vendor/package',
            ],
        ];

        $client = new PackagistApiClient($this->getStubClient([
            'GET packages/vendor/package.json' => [200, $apiData],
        ]));
        $provider = new PackagistMetadataProvider($client);

        $metadata = $provider->getMetadata($package);

        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertSame('vendor/package', $metadata->name);
        $this->assertSame('desc', $metadata->description);
        $this->assertSame('MIT', $metadata->license);
        $this->assertSame('https://example.com/vendor/package', $metadata->repository);
    }

    public function testGetMetadataReturnsNullWithoutPackageData(): void
    {
        $this->assertNull($this->provider->getMetadata(new ComposerPackage('vendor/package')));
    }
}
