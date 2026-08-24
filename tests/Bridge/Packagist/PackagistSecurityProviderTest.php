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

use PackApi\Bridge\GitHub\GitHubApiClient;
use PackApi\Bridge\Packagist\PackagistSecurityProvider;
use PackApi\Model\SecurityAdvisory;
use PackApi\Package\ComposerPackage;
use PackApi\Package\NpmPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

#[CoversClass(PackagistSecurityProvider::class)]
final class PackagistSecurityProviderTest extends TestCase
{
    private GitHubApiClient $github;
    private PackagistSecurityProvider $provider;

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
        $this->github = new GitHubApiClient($this->getStubClient());
        $this->provider = new PackagistSecurityProvider($this->github);
    }

    public function testSupportsComposerPackageOnly(): void
    {
        $composer = new ComposerPackage('vendor/package');
        $npm = new NpmPackage('foo');

        $this->assertTrue($this->provider->supports($composer));
        $this->assertFalse($this->provider->supports($npm));
    }

    public function testGetSecurityAdvisoriesReturnsModels(): void
    {
        $package = new ComposerPackage('vendor/package');
        $files = [
            [
                'type' => 'file',
                'name' => 'CVE-1234.yaml',
                'path' => 'vendor/package/CVE-1234.yaml',
                'html_url' => 'https://example.com/CVE-1234.yaml',
            ],
            [
                'type' => 'file',
                'name' => 'ignore.txt',
                'path' => 'vendor/package/ignore.txt',
                'html_url' => 'https://example.com/ignore.txt',
            ],
        ];

        $githubClient = new GitHubApiClient($this->getStubClient([
            'GET /repos/FriendsOfPHP/security-advisories/contents/vendor/package' => [200, $files],
            'GET /repos/FriendsOfPHP/security-advisories/contents/vendor/package/CVE-1234.yaml' => [200, ['content' => base64_encode("cve: CVE-1234\ntitle: Example\nseverity: high\n")]],
        ]));
        $provider = new PackagistSecurityProvider($githubClient);

        $advisories = $provider->getSecurityAdvisories($package);

        $this->assertCount(1, $advisories);
        $this->assertInstanceOf(SecurityAdvisory::class, $advisories[0]);
        $this->assertSame('CVE-1234', $advisories[0]->getId());
        $this->assertSame('Example', $advisories[0]->getTitle());
        $this->assertSame('high', $advisories[0]->getSeverity());
        $this->assertSame('https://example.com/CVE-1234.yaml', $advisories[0]->getLink());
    }

    public function testGetSecurityAdvisoriesReturnsEmptyWhenDirectoryIsMissing(): void
    {
        $githubClient = new GitHubApiClient($this->getStubClient([
            'GET /repos/FriendsOfPHP/security-advisories/contents/vendor/package' => [404, ['message' => 'Not Found']],
        ]));
        $provider = new PackagistSecurityProvider($githubClient);

        $this->assertSame([], $provider->getSecurityAdvisories(new ComposerPackage('vendor/package')));
    }

    public function testGetSecurityAdvisoriesSkipsMissingFileContent(): void
    {
        $files = [[
            'type' => 'file',
            'name' => 'CVE-1234.yaml',
            'path' => 'vendor/package/CVE-1234.yaml',
            'html_url' => 'https://example.com/CVE-1234.yaml',
        ]];
        $githubClient = new GitHubApiClient($this->getStubClient([
            'GET /repos/FriendsOfPHP/security-advisories/contents/vendor/package' => [200, $files],
            'GET /repos/FriendsOfPHP/security-advisories/contents/vendor/package/CVE-1234.yaml' => [404, ['message' => 'Not Found']],
        ]));
        $provider = new PackagistSecurityProvider($githubClient);

        $this->assertSame([], $provider->getSecurityAdvisories(new ComposerPackage('vendor/package')));
    }
}
