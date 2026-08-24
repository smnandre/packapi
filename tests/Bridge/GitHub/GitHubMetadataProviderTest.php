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

use PackApi\Bridge\GitHub\GitHubApiClient;
use PackApi\Bridge\GitHub\GitHubMetadataProvider;
use PackApi\Model\Metadata;
use PackApi\Package\ComposerPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(GitHubMetadataProvider::class)]
final class GitHubMetadataProviderTest extends TestCase
{
    public function testGetMetadataReturnsModel(): void
    {
        $responses = [
            new MockResponse(json_encode([
                'name' => 'repo',
                'description' => 'desc',
                'license' => ['name' => 'MIT'],
                'html_url' => 'https://github.com/owner/repo',
            ], JSON_THROW_ON_ERROR)),
        ];
        $client = new GitHubApiClient(new MockHttpClient($responses));

        $provider = new GitHubMetadataProvider($client);
        $package = new ComposerPackage('owner/repo');
        $package->setRepositoryUrl('https://github.com/owner/repo');

        $metadata = $provider->getMetadata($package);

        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertSame('repo', $metadata->name);
        $this->assertSame('desc', $metadata->description);
        $this->assertSame('MIT', $metadata->license);
        $this->assertSame('https://github.com/owner/repo', $metadata->repository);
    }

    public function testSupportsReturnTrueForGitHubRepositories(): void
    {
        $client = new GitHubApiClient(new MockHttpClient());
        $provider = new GitHubMetadataProvider($client);
        $package = new ComposerPackage('owner/repo');

        $this->assertFalse($provider->supports($package));
        $package->setRepositoryUrl('https://example.com/owner/repo');
        $this->assertFalse($provider->supports($package));

        $package->setRepositoryUrl('https://github.com/owner/repo');
        $this->assertTrue($provider->supports($package));
    }

    public function testGetMetadataReturnsNullWithoutRepository(): void
    {
        $provider = new GitHubMetadataProvider(new GitHubApiClient(new MockHttpClient()));

        $this->assertNull($provider->getMetadata(new ComposerPackage('owner/repo')));
    }

    public function testGetMetadataReturnsNullForInvalidRepositoryUrl(): void
    {
        $provider = new GitHubMetadataProvider(new GitHubApiClient(new MockHttpClient()));
        $package = new ComposerPackage('owner/repo');
        $package->setRepositoryUrl('not-a-repository');

        $this->assertNull($provider->getMetadata($package));
    }

    public function testGetMetadataReturnsNullWhenRepositoryIsMissing(): void
    {
        $provider = new GitHubMetadataProvider(new GitHubApiClient(new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ])));
        $package = new ComposerPackage('owner/repo');
        $package->setRepositoryUrl('https://github.com/owner/repo');

        $this->assertNull($provider->getMetadata($package));
    }

    public function testGetMetadataReturnsNullForMalformedGitHubRepository(): void
    {
        $provider = new GitHubMetadataProvider(new GitHubApiClient(new MockHttpClient()));
        $package = new ComposerPackage('owner/repo');
        $package->setRepositoryUrl('https://github.com/owner!/repo');

        $this->assertNull($provider->getMetadata($package));
    }
}
