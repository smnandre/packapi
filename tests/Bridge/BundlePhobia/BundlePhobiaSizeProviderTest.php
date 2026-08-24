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

namespace PackApi\Tests\Bridge\BundlePhobia;

use PackApi\Bridge\BundlePhobia\BundlePhobiaApiClient;
use PackApi\Bridge\BundlePhobia\BundlePhobiaSizeProvider;
use PackApi\Model\BundleSize;
use PackApi\Package\ComposerPackage;
use PackApi\Package\NpmPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(BundlePhobiaSizeProvider::class)]
final class BundlePhobiaSizeProviderTest extends TestCase
{
    private BundlePhobiaApiClient|MockObject $bundlePhobiaApiClient;
    private BundlePhobiaSizeProvider $provider;

    protected function setUp(): void
    {
        $this->bundlePhobiaApiClient = $this->createStub(BundlePhobiaApiClient::class);
        $this->provider = new BundlePhobiaSizeProvider($this->bundlePhobiaApiClient);
    }

    private function useMockApiClient(): void
    {
        $this->bundlePhobiaApiClient = $this->createMock(BundlePhobiaApiClient::class);
        $this->provider = new BundlePhobiaSizeProvider($this->bundlePhobiaApiClient);
    }

    public function testSupportsNpmPackage(): void
    {
        $package = new NpmPackage('react');

        $this->assertTrue($this->provider->supports($package));
    }

    public function testDoesNotSupportComposerPackage(): void
    {
        $package = new ComposerPackage('vendor/package');

        $this->assertFalse($this->provider->supports($package));
    }

    public function testGetBundleSizeReturnsNullWhenApiReturnsNull(): void
    {
        $package = new NpmPackage('nonexistent-package');
        $this->useMockApiClient();

        $this->bundlePhobiaApiClient
            ->expects($this->once())
            ->method('getBundleSize')
            ->with('nonexistent-package', null)
            ->willReturn(null);

        $result = $this->provider->getBundleSize($package);

        $this->assertNull($result);
    }

    public function testGetBundleSizeReturnsBundleSize(): void
    {
        $package = new NpmPackage('react');
        $apiData = [
            'name' => 'react',
            'version' => '18.2.0',
            'description' => 'React is a JavaScript library for building user interfaces.',
            'size' => 42000,
            'gzip' => 13000,
            'dependencyCount' => 0,
            'dependencySizes' => 0,
            'hasJSModule' => false,
            'hasSideEffects' => true,
            'scoped' => false,
            'repository' => [
                'url' => 'https://github.com/facebook/react.git',
            ],
        ];
        $this->useMockApiClient();

        $this->bundlePhobiaApiClient
            ->expects($this->once())
            ->method('getBundleSize')
            ->with('react', null)
            ->willReturn($apiData);

        $result = $this->provider->getBundleSize($package);

        $this->assertInstanceOf(BundleSize::class, $result);
        $this->assertSame('react', $result->getName());
        $this->assertSame('18.2.0', $result->getVersion());
        $this->assertSame('React is a JavaScript library for building user interfaces.', $result->getDescription());
        $this->assertSame(42000, $result->getSize());
        $this->assertSame(13000, $result->getGzipSize());
        $this->assertSame(0, $result->getDependencyCount());
        $this->assertSame(0, $result->getDependencySize());
        $this->assertFalse($result->hasJSModule());
        $this->assertTrue($result->hasSideEffects());
        $this->assertFalse($result->isScoped());
        $this->assertSame('https://github.com/facebook/react.git', $result->getRepository());
    }

    public function testGetBundleSizeForVersionCallsApiWithVersion(): void
    {
        $package = new NpmPackage('react');
        $version = '17.0.2';
        $apiData = [
            'name' => 'react',
            'version' => '17.0.2',
            'size' => 39000,
            'gzip' => 12000,
        ];
        $this->useMockApiClient();

        $this->bundlePhobiaApiClient
            ->expects($this->once())
            ->method('getBundleSize')
            ->with('react', '17.0.2')
            ->willReturn($apiData);

        $result = $this->provider->getBundleSizeForVersion($package, $version);

        $this->assertInstanceOf(BundleSize::class, $result);
        $this->assertSame('react', $result->getName());
        $this->assertSame('17.0.2', $result->getVersion());
        $this->assertSame(39000, $result->getSize());
        $this->assertSame(12000, $result->getGzipSize());
    }

    public function testGetBundleSizeForVersionReturnsNullWhenApiReturnsNull(): void
    {
        $package = new NpmPackage('react');
        $this->useMockApiClient();
        $this->bundlePhobiaApiClient
            ->expects($this->once())
            ->method('getBundleSize')
            ->willReturn(null);

        $this->assertNull($this->provider->getBundleSizeForVersion($package, '1.0.0'));
    }

    public function testGetBundleSizeForVersionReturnsNullOnFailure(): void
    {
        $package = new NpmPackage('react');
        $this->useMockApiClient();
        $this->bundlePhobiaApiClient
            ->expects($this->once())
            ->method('getBundleSize')
            ->willThrowException(new \RuntimeException('API error'));

        $this->assertNull($this->provider->getBundleSizeForVersion($package, '1.0.0'));
    }

    public function testGetPackageHistoryReturnsHistoryData(): void
    {
        $package = new NpmPackage('react');
        $historyData = [
            'name' => 'react',
            'versions' => [
                ['version' => '18.2.0', 'size' => 42000, 'gzip' => 13000],
                ['version' => '18.1.0', 'size' => 41500, 'gzip' => 12800],
                ['version' => '18.0.0', 'size' => 41000, 'gzip' => 12500],
            ],
        ];
        $this->useMockApiClient();

        $this->bundlePhobiaApiClient
            ->expects($this->once())
            ->method('getPackageHistory')
            ->with('react')
            ->willReturn($historyData);

        $result = $this->provider->getPackageHistory($package);

        $this->assertSame($historyData, $result);
    }

    public function testGetPackageHistoryReturnsNullForUnsupportedPackage(): void
    {
        $package = new ComposerPackage('vendor/package');

        $result = $this->provider->getPackageHistory($package);

        $this->assertNull($result);
    }

    public function testGetPackageHistoryReturnsNullOnFailure(): void
    {
        $package = new NpmPackage('react');
        $this->useMockApiClient();
        $this->bundlePhobiaApiClient
            ->expects($this->once())
            ->method('getPackageHistory')
            ->willThrowException(new \RuntimeException('API error'));

        $this->assertNull($this->provider->getPackageHistory($package));
    }

    public function testGetBundleSizeHandlesExceptionGracefully(): void
    {
        $package = new NpmPackage('test-package');
        $this->useMockApiClient();

        $this->bundlePhobiaApiClient
            ->expects($this->once())
            ->method('getBundleSize')
            ->willThrowException(new \Exception('API error'));

        $result = $this->provider->getBundleSize($package);

        $this->assertNull($result);
    }

    public function testGetBundleSizeHandlesMissingFields(): void
    {
        $package = new NpmPackage('minimal-package');
        $apiData = [
            'name' => 'minimal-package',
            // Missing most fields to test defaults
        ];
        $this->useMockApiClient();

        $this->bundlePhobiaApiClient
            ->expects($this->once())
            ->method('getBundleSize')
            ->with('minimal-package', null)
            ->willReturn($apiData);

        $result = $this->provider->getBundleSize($package);

        $this->assertInstanceOf(BundleSize::class, $result);
        $this->assertSame('minimal-package', $result->getName());
        $this->assertSame('unknown', $result->getVersion());
        $this->assertNull($result->getDescription());
        $this->assertSame(0, $result->getSize());
        $this->assertSame(0, $result->getGzipSize());
        $this->assertSame(0, $result->getDependencyCount());
        $this->assertSame(0, $result->getDependencySize());
        $this->assertFalse($result->hasJSModule());
        $this->assertFalse($result->hasSideEffects());
        $this->assertFalse($result->isScoped());
        $this->assertNull($result->getRepository());
    }
}
