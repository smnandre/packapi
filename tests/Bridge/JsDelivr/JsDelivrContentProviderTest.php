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

namespace PackApi\Tests\Bridge\JsDelivr;

use PackApi\Bridge\JsDelivr\JsDelivrApiClient;
use PackApi\Bridge\JsDelivr\JsDelivrContentProvider;
use PackApi\Model\ContentOverview;
use PackApi\Model\File;
use PackApi\Package\ComposerPackage;
use PackApi\Package\NpmPackage;
use PackApi\Package\SwiftPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(JsDelivrContentProvider::class)]
final class JsDelivrContentProviderTest extends TestCase
{
    private JsDelivrContentProvider $provider;

    public function testSupportsNpmAndSwiftPackages(): void
    {
        $client = new JsDelivrApiClient(new MockHttpClient());
        $this->provider = new JsDelivrContentProvider($client);

        $npm = new NpmPackage('test');
        $swift = new SwiftPackage('Alamofire', 'Alamofire');
        $other = new ComposerPackage('vendor/package');

        $this->assertTrue($this->provider->supports($npm));
        $this->assertTrue($this->provider->supports($swift));
        $this->assertFalse($this->provider->supports($other));
    }

    public function testGetContentOverviewReturnsNullWhenApiReturnsNull(): void
    {
        $http = new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ]);
        $client = new JsDelivrApiClient($http);
        $this->provider = new JsDelivrContentProvider($client);

        $package = new NpmPackage('test');

        $this->assertNull($this->provider->getContentOverview($package));
    }

    public function testGetContentOverviewReturnsNullForUnsupportedPackage(): void
    {
        $provider = new JsDelivrContentProvider(new JsDelivrApiClient(new MockHttpClient()));

        $this->assertNull($provider->getContentOverview(new ComposerPackage('vendor/package')));
    }

    public function testGetContentOverviewUsesGitHubPathForSwiftPackage(): void
    {
        $capturedUrl = null;
        $http = new MockHttpClient(function (string $method, string $url) use (&$capturedUrl): MockResponse {
            $capturedUrl = $url;

            return new MockResponse('{"files":[]}');
        });
        $provider = new JsDelivrContentProvider(new JsDelivrApiClient($http));

        $this->assertNull($provider->getContentOverview(new SwiftPackage('Alamofire', 'Alamofire')));
        $this->assertStringEndsWith('v1/package/gh/Alamofire/Alamofire/flat', $capturedUrl);
    }

    public function testGetContentOverviewBuildsModel(): void
    {
        $files = [
            ['name' => 'README.md', 'size' => 100, 'time' => '2024-01-01T00:00:00Z'],
            ['name' => 'LICENSE', 'size' => 50],
            ['name' => 'test/example.php', 'size' => 20],
            ['name' => '.gitattributes', 'size' => 1],
            ['name' => '.gitignore', 'size' => 1],
            ['name' => 'src/index.js', 'size' => 100],
        ];
        $http = new MockHttpClient([
            new MockResponse(json_encode(['files' => $files], JSON_THROW_ON_ERROR), ['http_code' => 200]),
        ]);
        $client = new JsDelivrApiClient($http);
        $this->provider = new JsDelivrContentProvider($client);
        $package = new NpmPackage('pkg');

        $overview = $this->provider->getContentOverview($package);

        $this->assertInstanceOf(ContentOverview::class, $overview);
        $this->assertSame(6, $overview->getFileCount());
        $this->assertSame(272, $overview->getTotalSize());
        $this->assertTrue($overview->hasReadme());
        $this->assertTrue($overview->hasLicense());
        $this->assertTrue($overview->hasTests());
        $this->assertTrue($overview->hasGitattributes());
        $this->assertTrue($overview->hasGitignore());
        $this->assertSame(['test/example.php'], $overview->getIgnoredFiles());

        $paths = array_map(fn (File $f) => $f->getPath(), $overview->getFiles());
        $this->assertContains('README.md', $paths);
        $this->assertContains('src/index.js', $paths);
    }
}
