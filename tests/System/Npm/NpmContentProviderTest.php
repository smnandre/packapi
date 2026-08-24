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
use PackApi\Model\ContentOverview;
use PackApi\Package\ComposerPackage;
use PackApi\Package\NpmPackage;
use PackApi\System\Npm\NpmContentProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(NpmContentProvider::class)]
final class NpmContentProviderTest extends TestCase
{
    private NpmApiClient|MockObject $npmApiClient;
    private NpmContentProvider $provider;

    protected function setUp(): void
    {
        $this->npmApiClient = $this->createStub(NpmApiClient::class);
        $this->provider = new NpmContentProvider($this->npmApiClient);
    }

    private function useMockApiClient(): void
    {
        $this->npmApiClient = $this->createMock(NpmApiClient::class);
        $this->provider = new NpmContentProvider($this->npmApiClient);
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

    public function testGetContentOverviewReturnsNullWhenApiClientReturnsNull(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();

        $this->npmApiClient
            ->expects($this->once())
            ->method('fetchPackageInfo')
            ->with('test-package')
            ->willReturn(null);

        $result = $this->provider->getContentOverview($package);

        $this->assertNull($result);
    }

    public function testGetContentOverviewReturnsContentOverviewWithBasicInfo(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();
        $apiData = [
            'name' => 'test-package',
            'dist' => [
                'fileCount' => 15,
                'unpackedSize' => 45000,
            ],
        ];

        $this->npmApiClient
            ->expects($this->once())
            ->method('fetchPackageInfo')
            ->with('test-package')
            ->willReturn($apiData);

        $result = $this->provider->getContentOverview($package);

        $this->assertInstanceOf(ContentOverview::class, $result);
        $this->assertSame(15, $result->fileCount);
        $this->assertSame(45000, $result->totalSize);
        $this->assertFalse($result->hasReadme);
        $this->assertFalse($result->hasLicense);
        $this->assertFalse($result->hasTests);
    }

    public function testGetContentOverviewDetectsReadme(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();
        $apiData = [
            'name' => 'test-package',
            'readme' => '# Test Package\n\nThis is a test package.',
        ];

        $this->npmApiClient
            ->expects($this->once())
            ->method('fetchPackageInfo')
            ->with('test-package')
            ->willReturn($apiData);

        $result = $this->provider->getContentOverview($package);

        $this->assertTrue($result->hasReadme);
    }

    public function testGetContentOverviewDetectsLicense(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();
        $apiData = [
            'name' => 'test-package',
            'license' => 'MIT',
        ];

        $this->npmApiClient
            ->expects($this->once())
            ->method('fetchPackageInfo')
            ->with('test-package')
            ->willReturn($apiData);

        $result = $this->provider->getContentOverview($package);

        $this->assertTrue($result->hasLicense);
    }

    public function testGetContentOverviewDetectsTestsFromScripts(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();
        $apiData = [
            'name' => 'test-package',
            'scripts' => [
                'start' => 'node index.js',
                'test' => 'jest',
            ],
        ];

        $this->npmApiClient
            ->expects($this->once())
            ->method('fetchPackageInfo')
            ->with('test-package')
            ->willReturn($apiData);

        $result = $this->provider->getContentOverview($package);

        $this->assertTrue($result->hasTests);
    }

    public function testGetContentOverviewDetectsTestsFromDevDependencies(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();
        $apiData = [
            'name' => 'test-package',
            'devDependencies' => [
                'jest' => '^29.0.0',
                'typescript' => '^4.8.0',
            ],
        ];

        $this->npmApiClient
            ->expects($this->once())
            ->method('fetchPackageInfo')
            ->with('test-package')
            ->willReturn($apiData);

        $result = $this->provider->getContentOverview($package);

        $this->assertTrue($result->hasTests);
    }

    public function testGetContentOverviewDetectsVariousTestFrameworks(): void
    {
        $testFrameworks = ['jest', 'mocha', 'jasmine', 'karma', 'ava'];

        foreach ($testFrameworks as $index => $framework) {
            // Create a new provider for each test to avoid mock conflicts
            $npmApiClient = $this->createMock(NpmApiClient::class);
            $provider = new NpmContentProvider($npmApiClient);

            $package = new NpmPackage('test-package');
            $apiData = [
                'name' => 'test-package',
                'scripts' => [
                    $framework => 'run tests',
                ],
            ];

            $npmApiClient
                ->expects($this->once())
                ->method('fetchPackageInfo')
                ->with('test-package')
                ->willReturn($apiData);

            $result = $provider->getContentOverview($package);

            $this->assertTrue($result->hasTests, "Should detect tests for framework: $framework");
        }
    }

    public function testGetContentOverviewHandlesEmptyData(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();
        $apiData = [];

        $this->npmApiClient
            ->expects($this->once())
            ->method('fetchPackageInfo')
            ->with('test-package')
            ->willReturn($apiData);

        $result = $this->provider->getContentOverview($package);

        $this->assertInstanceOf(ContentOverview::class, $result);
        $this->assertSame(0, $result->fileCount);
        $this->assertSame(0, $result->totalSize);
        $this->assertFalse($result->hasReadme);
        $this->assertFalse($result->hasLicense);
        $this->assertFalse($result->hasTests);
    }
}
