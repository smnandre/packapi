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

namespace PackApi\Tests\Bridge\GitHub;

use PackApi\Bridge\GitHub\GitHubActivityProvider;
use PackApi\Bridge\GitHub\GitHubApiClient;
use PackApi\Model\ActivitySummary;
use PackApi\Package\ComposerPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

#[CoversClass(GitHubActivityProvider::class)]
final class GitHubActivityProviderTest extends TestCase
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

    public function testSupportsIdentifiesGithubRepositories(): void
    {
        $client = new GitHubApiClient($this->getStubClient());
        $provider = new GitHubActivityProvider($client);

        $pkg = new ComposerPackage('symfony/symfony');
        $pkg->setRepositoryUrl('https://github.com/symfony/symfony');
        $this->assertTrue($provider->supports($pkg));

        $pkg2 = new ComposerPackage('symfony/symfony');
        $this->assertTrue($provider->supports($pkg2));

        $pkg3 = new ComposerPackage('acme/foo');
        $pkg3->setRepositoryUrl('https://gitlab.com/acme/foo');
        $this->assertFalse($provider->supports($pkg3));

        $this->assertFalse($provider->supports(new ComposerPackage('invalid')));
    }

    public function testGetActivitySummaryReturnsData(): void
    {
        $responses = [
            'GET /repos/owner/repo' => [200, ['open_issues_count' => 5]],
            'GET /repos/owner/repo/commits' => [200, [['commit' => ['committer' => ['date' => '2024-06-01T12:00:00Z']]]]],
            'GET /repos/owner/repo/contributors' => [200, [[], []]],
            'GET /repos/owner/repo/releases' => [200, [['published_at' => '2024-06-02T00:00:00Z']]],
        ];
        $client = new GitHubApiClient($this->getStubClient($responses));
        $provider = new GitHubActivityProvider($client);
        $pkg = new ComposerPackage('owner/repo');
        $pkg->setRepositoryUrl('https://github.com/owner/repo');

        $summary = $provider->getActivitySummary($pkg);

        $this->assertInstanceOf(ActivitySummary::class, $summary);
        $this->assertEquals(new \DateTimeImmutable('2024-06-01T12:00:00Z'), $summary->getLastCommit());
        $this->assertSame(2, $summary->getContributors());
        $this->assertSame(5, $summary->getOpenIssues());
        $this->assertSame('2024-06-02T00:00:00Z', $summary->getLastRelease());
    }

    public function testGetActivitySummaryReturnsNullWithoutRepository(): void
    {
        $provider = new GitHubActivityProvider(new GitHubApiClient($this->getStubClient()));

        $this->assertNull($provider->getActivitySummary(new ComposerPackage('owner/repo')));
    }

    public function testGetActivitySummaryReturnsNullForInvalidRepositoryUrl(): void
    {
        $provider = new GitHubActivityProvider(new GitHubApiClient($this->getStubClient()));
        $package = new ComposerPackage('owner/repo');
        $package->setRepositoryUrl('not-a-repository');

        $this->assertNull($provider->getActivitySummary($package));
    }

    public function testGetActivitySummaryReturnsNullWhenActivityIsUnavailable(): void
    {
        $provider = new GitHubActivityProvider(new GitHubApiClient($this->getStubClient([
            'GET /repos/owner/repo' => [404, ['message' => 'Not Found']],
        ])));
        $package = new ComposerPackage('owner/repo');
        $package->setRepositoryUrl('https://github.com/owner/repo');

        $this->assertNull($provider->getActivitySummary($package));
    }

    public function testGetActivitySummaryReturnsNullOnApiFailure(): void
    {
        $provider = new GitHubActivityProvider(new GitHubApiClient($this->getStubClient([
            'GET /repos/owner/repo' => [500, ['message' => 'Unavailable']],
        ])));
        $package = new ComposerPackage('owner/repo');
        $package->setRepositoryUrl('https://github.com/owner/repo');

        $this->assertNull($provider->getActivitySummary($package));
    }
}
