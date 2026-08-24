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

use PackApi\Bridge\Packagist\PackagistActivityProvider;
use PackApi\Bridge\Packagist\PackagistApiClient;
use PackApi\Model\ActivitySummary;
use PackApi\Package\ComposerPackage;
use PackApi\Package\NpmPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

#[CoversClass(PackagistActivityProvider::class)]
final class PackagistActivityProviderTest extends TestCase
{
    private PackagistApiClient $apiClient;
    private PackagistActivityProvider $provider;

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
        $this->provider = new PackagistActivityProvider($this->apiClient);
    }

    public function testSupportsComposerPackage(): void
    {
        $package = new ComposerPackage('vendor/package');

        $this->assertTrue($this->provider->supports($package));
    }

    public function testDoesNotSupportNonComposerPackage(): void
    {
        $package = new NpmPackage('foo');

        $this->assertFalse($this->provider->supports($package));
    }

    public function testGetActivitySummaryReturnsLatestReleaseInfo(): void
    {
        $package = new ComposerPackage('vendor/package');
        $data = [
            'package' => [
                'time' => '2024-01-01 00:00:00',
                'versions' => [
                    '1.0.0' => ['version' => '1.0.0', 'time' => '2024-05-01 00:00:00'],
                    '2.0.0' => ['version' => '2.0.0', 'time' => '2024-06-01 00:00:00'],
                ],
            ],
        ];

        $client = new PackagistApiClient($this->getStubClient([
            'GET packages/vendor/package.json' => [200, $data],
        ]));
        $provider = new PackagistActivityProvider($client);

        $summary = $provider->getActivitySummary($package);

        $this->assertInstanceOf(ActivitySummary::class, $summary);
        $this->assertEquals('2.0.0', $summary->getLastRelease());
        $this->assertEquals(new \DateTimeImmutable('2024-06-01 00:00:00'), $summary->getLastCommit());
        $this->assertSame(0, $summary->getContributors());
        $this->assertSame(0, $summary->getOpenIssues());
    }

    public function testGetActivitySummaryReturnsNullWithoutPackageData(): void
    {
        $this->assertNull($this->provider->getActivitySummary(new ComposerPackage('vendor/package')));
    }
}
