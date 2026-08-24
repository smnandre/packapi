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
use PackApi\Bridge\Packagist\PackagistContentProvider;
use PackApi\Package\ComposerPackage;
use PackApi\Package\NpmPackage;
use PackApi\Security\SecureFileHandler;
use PackApi\Security\SecureFileHandlerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

#[CoversClass(PackagistContentProvider::class)]
final class PackagistContentProviderTest extends TestCase
{
    private PackagistApiClient $apiClient;
    private SecureFileHandler $fileHandler;
    private PackagistContentProvider $provider;

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
        $this->fileHandler = new SecureFileHandler($this->getStubClient());
        $this->provider = new PackagistContentProvider($this->apiClient, $this->fileHandler);
    }

    public function testSupportsComposerPackageOnly(): void
    {
        $composer = new ComposerPackage('vendor/package');
        $npm = new NpmPackage('foo');

        $this->assertTrue($this->provider->supports($composer));
        $this->assertFalse($this->provider->supports($npm));
    }

    public function testGetContentOverviewReturnsNullWhenNoDist(): void
    {
        $package = new ComposerPackage('vendor/package');
        $apiData = [
            'package' => [
                'versions' => [
                    '1.0.0' => ['version' => '1.0.0'],
                ],
            ],
        ];

        $client = new PackagistApiClient($this->getStubClient([
            'GET packages/vendor/package.json' => [200, $apiData],
        ]));
        $provider = new PackagistContentProvider($client, new SecureFileHandler($this->getStubClient()));

        $this->assertNull($provider->getContentOverview($package));
    }

    public function testGetContentOverviewReturnsNullWhenNoVersions(): void
    {
        $package = new ComposerPackage('vendor/package');
        $apiData = [
            'package' => [
                'versions' => [],
            ],
        ];

        $client = new PackagistApiClient($this->getStubClient([
            'GET packages/vendor/package.json' => [200, $apiData],
        ]));
        $provider = new PackagistContentProvider($client, new SecureFileHandler($this->getStubClient()));

        $this->assertNull($provider->getContentOverview($package));
    }

    public function testGetContentOverviewReturnsNullOnInvalidDistUrl(): void
    {
        $package = new ComposerPackage('vendor/package');
        $apiData = [
            'package' => [
                'versions' => [
                    '1.0.0' => [
                        'version' => '1.0.0',
                        'dist' => ['url' => 'file:///not/allowed/archive.zip'],
                    ],
                ],
            ],
        ];

        $client = new PackagistApiClient($this->getStubClient([
            'GET packages/vendor/package.json' => [200, $apiData],
        ]));
        $provider = new PackagistContentProvider($client, new SecureFileHandler($this->getStubClient()));

        $this->assertNull($provider->getContentOverview($package));
    }

    public function testGetContentOverviewReturnsNullWhenExtractionDirectoryCannotBeCreated(): void
    {
        $package = new ComposerPackage('vendor/package');
        $client = new PackagistApiClient($this->getStubClient([
            'GET packages/vendor/package.json' => [200, [
                'package' => ['versions' => ['1.0.0' => ['dist' => ['url' => 'https://example.org/archive.zip']]]],
            ]],
        ]));
        $fileHandler = $this->createStub(SecureFileHandlerInterface::class);
        $fileHandler->method('downloadSafely')->willReturn('/missing/archive.zip');
        $tempFile = tempnam(sys_get_temp_dir(), 'packapi_blocked_');
        $this->assertNotFalse($tempFile);
        $provider = new PackagistContentProvider($client, $fileHandler, $tempFile);

        try {
            $this->assertNull($provider->getContentOverview($package));
        } finally {
            unlink($tempFile);
        }
    }

    public function testGetContentOverviewSuccessBuildsOverview(): void
    {
        $package = new ComposerPackage('vendor/package');
        $apiData = [
            'package' => [
                'versions' => [
                    '1.0.0' => [
                        'version' => '1.0.0',
                        'dist' => ['url' => 'https://example.org/archive.zip'],
                    ],
                ],
            ],
        ];

        $client = new PackagistApiClient($this->getStubClient([
            'GET packages/vendor/package.json' => [200, $apiData],
        ]));

        /** @var SecureFileHandlerInterface&\PHPUnit\Framework\MockObject\MockObject $fileHandler */
        $fileHandler = $this->createMock(SecureFileHandlerInterface::class);
        $archivePath = tempnam(sys_get_temp_dir(), 'packapi_archive_');
        $this->assertNotFalse($archivePath);

        $fileHandler
            ->expects($this->once())
            ->method('downloadSafely')
            ->with('https://example.org/archive.zip')
            ->willReturn($archivePath);

        $fileHandler
            ->expects($this->once())
            ->method('extractSafely')
            ->willReturnCallback(function (string $archivePath, string $destination): array {
                @mkdir($destination.'/tests', 0777, true);
                @mkdir($destination.'/docs', 0777, true);
                file_put_contents($destination.'/README.md', 'readme');
                file_put_contents($destination.'/LICENSE', 'license');
                file_put_contents($destination.'/.gitignore', 'ignored');
                file_put_contents($destination.'/.gitattributes', 'attrs');
                file_put_contents($destination.'/tests/ExampleTest.php', 'tests');
                file_put_contents($destination.'/docs/guide.md', 'docs');

                return ['README.md', 'LICENSE', '.gitignore', '.gitattributes', 'tests/ExampleTest.php', 'docs/guide.md'];
            });

        $fileHandler
            ->method('validatePath')
            ->willReturnCallback(static fn (string $path): bool => 'docs/guide.md' !== $path);

        $provider = new PackagistContentProvider($client, $fileHandler);

        $overview = $provider->getContentOverview($package);

        $this->assertNotNull($overview);
        $this->assertSame(5, $overview->fileCount);
        $this->assertSame(30, $overview->totalSize);
        $this->assertTrue($overview->hasReadme);
        $this->assertTrue($overview->hasLicense);
        $this->assertTrue($overview->hasTests);
        $this->assertTrue($overview->hasGitignore);
        $this->assertTrue($overview->hasGitattributes);

        $ignored = $overview->ignoredFiles;
        $this->assertContains('tests/ExampleTest.php', $ignored);
        $this->assertNotContains('docs/guide.md', $ignored);
        $this->assertFileDoesNotExist($archivePath);
    }
}
