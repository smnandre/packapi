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

namespace PackApi\Tests\System\Npm;

use PackApi\Bridge\Npm\NpmApiClient;
use PackApi\Model\Metadata;
use PackApi\Package\ComposerPackage;
use PackApi\Package\NpmPackage;
use PackApi\System\Npm\NpmMetadataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(NpmMetadataProvider::class)]
final class NpmMetadataProviderTest extends TestCase
{
    private NpmApiClient|MockObject $npmApiClient;
    private NpmMetadataProvider $provider;

    protected function setUp(): void
    {
        $this->npmApiClient = $this->createStub(NpmApiClient::class);
        $this->provider = new NpmMetadataProvider($this->npmApiClient);
    }

    private function useMockApiClient(): void
    {
        $this->npmApiClient = $this->createMock(NpmApiClient::class);
        $this->provider = new NpmMetadataProvider($this->npmApiClient);
    }

    public function testSupportsReturnsTrueForNpmPackage(): void
    {
        $package = new NpmPackage('test-package');

        $this->assertTrue($this->provider->supports($package));
    }

    public function testSupportsReturnsFalseForNonNpmPackage(): void
    {
        $package = new ComposerPackage('vendor/package');

        $this->assertFalse($this->provider->supports($package));
    }

    public function testGetMetadataReturnsNullWhenApiClientReturnsNull(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();

        $this->npmApiClient
            ->expects($this->once())
            ->method('fetchPackageInfo')
            ->with('test-package')
            ->willReturn(null);

        $result = $this->provider->getMetadata($package);

        $this->assertNull($result);
    }

    public function testGetMetadataReturnsMetadataWithAllFields(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();
        $apiData = [
            'name' => 'test-package',
            'description' => 'A test package',
            'license' => 'MIT',
            'repository' => [
                'url' => 'https://github.com/user/repo.git',
            ],
        ];

        $this->npmApiClient
            ->expects($this->once())
            ->method('fetchPackageInfo')
            ->with('test-package')
            ->willReturn($apiData);

        $result = $this->provider->getMetadata($package);

        $this->assertInstanceOf(Metadata::class, $result);
        $this->assertSame('test-package', $result->name);
        $this->assertSame('A test package', $result->description);
        $this->assertSame('MIT', $result->license);
        $this->assertSame('https://github.com/user/repo.git', $result->repository);
    }

    public function testGetMetadataHandlesMissingFields(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();
        $apiData = [
            'name' => 'test-package',
        ];

        $this->npmApiClient
            ->expects($this->once())
            ->method('fetchPackageInfo')
            ->with('test-package')
            ->willReturn($apiData);

        $result = $this->provider->getMetadata($package);

        $this->assertInstanceOf(Metadata::class, $result);
        $this->assertSame('test-package', $result->name);
        $this->assertNull($result->description);
        $this->assertNull($result->license);
        $this->assertNull($result->repository);
    }

    public function testGetMetadataHandlesStringRepository(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();
        $apiData = [
            'name' => 'test-package',
            'repository' => 'https://github.com/user/repo.git',
        ];

        $this->npmApiClient
            ->expects($this->once())
            ->method('fetchPackageInfo')
            ->with('test-package')
            ->willReturn($apiData);

        $result = $this->provider->getMetadata($package);

        $this->assertSame('https://github.com/user/repo.git', $result->repository);
    }

    public function testGetMetadataHandlesObjectLicense(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();
        $apiData = [
            'name' => 'test-package',
            'license' => [
                'type' => 'MIT',
            ],
        ];

        $this->npmApiClient
            ->expects($this->once())
            ->method('fetchPackageInfo')
            ->with('test-package')
            ->willReturn($apiData);

        $result = $this->provider->getMetadata($package);

        $this->assertSame('MIT', $result->license);
    }

    public function testGetMetadataFallsBackToPackageNameWhenNameMissing(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();
        $apiData = [];

        $this->npmApiClient
            ->expects($this->once())
            ->method('fetchPackageInfo')
            ->with('test-package')
            ->willReturn($apiData);

        $result = $this->provider->getMetadata($package);

        $this->assertSame('test-package', $result->name);
    }
}
