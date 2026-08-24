<div align="center">
<h1><img src=".github/packapi.png" alt="Pack API" width="100%" /></h1>

&nbsp; ![PHP Version](https://img.shields.io/badge/PHP-8.3+-7A8593?logoColor=7A8593&labelColor=161406)
&nbsp; ![CI](https://img.shields.io/github/actions/workflow/status/smnandre/packapi/CI.yaml?branch=main&label=Tests&labelColor=161406&color=7A8593)
&nbsp; ![Release](https://img.shields.io/github/v/release/smnandre/packapi?label=Stable&labelColor=161406&color=7A8593)
&nbsp; [![GitHub Sponsors](https://img.shields.io/github/sponsors/smnandre?logo=githubsponsors&logoColor=7A8593&label=%20Sponsor&labelColor=161406&color=7A8593)](https://github.com/sponsors/smnandre)
&nbsp; ![License](https://img.shields.io/github/license/smnandre/packapi?label=License&labelColor=161406&color=7A8593)

</div>

Get insights from **Composer**, **NPM**, **Swift packages**, **GitHub**, and more via a unified, strongly‑typed API.


## Features

- **Multi‑ecosystem**: Composer, NPM, Swift packages, GitHub, jsDelivr, OSV, BundlePhobia
- **Analyses**: Metadata, downloads, security, activity, quality
- **Strong typing**: PHP 8.3+ with strict types
- **Extensible**: Provider/factory architecture
- **Well‑tested**: Extensive automated test suite
- **Lean deps**: Symfony components and PSR interfaces
- **HTTP/3 (QUIC)** support with graceful fallback

## Quick Start

### Installation

```bash
composer require smnandre/packapi
```

### Basic Usage

```php
use PackApi\Bridge\Packagist\PackagistProviderFactory;
use PackApi\Http\HttpClientFactory;
use PackApi\Inspector\{MetadataInspector, DownloadStatsInspector};
use PackApi\Package\ComposerPackage;

$http = new HttpClientFactory();
$packagist = new PackagistProviderFactory($http);

$metadata = new MetadataInspector([
    $packagist->createMetadataProvider(),
]);
$downloads = new DownloadStatsInspector([
    $packagist->createStatsProvider(),
]);

$package = new ComposerPackage('symfony/console');
$meta = $metadata->getMetadata($package);
$stats = $downloads->getStats($package);

echo 'Package: '.($meta?->name ?? 'N/A')."\n";
echo 'Monthly downloads: '.($stats?->get('monthly')?->getCount() ?? 'N/A')."\n";
```

For activity, content, quality, and OSV security, add the relevant provider factories (GitHub, jsDelivr, OSV) and pass them to the corresponding inspectors.

## Supported Package Types

| Ecosystem    | Package Type      | Metadata | Downloads | Security | Activity | Content | Bundle Size |
|--------------|-------------------|----------|-----------|----------|----------|---------|-------------|
| **Composer** | `ComposerPackage` | Yes      | Yes       | Yes      | Yes      | Yes     | No          |
| **NPM**      | `NpmPackage`      | Yes      | Yes       | Yes      | Yes      | Yes     | Yes         |
| **Swift**    | `SwiftPackage`    | Via GitHub | Via jsDelivr | Via GitHub | Via GitHub | Via GitHub/jsDelivr | No |

GitHub providers can enrich any package that has a GitHub repository URL. They expose repository metadata, content, activity, and security advisories, but not registry download counts.

## Usage Examples

### Package Metadata Analysis

```php
use PackApi\Inspector\MetadataInspector;
use PackApi\Bridge\Packagist\PackagistProviderFactory;
use PackApi\Package\ComposerPackage;

$factory = new PackagistProviderFactory($httpClient);
$inspector = new MetadataInspector([
    $factory->createMetadataProvider()
]);

$package = new ComposerPackage('laravel/framework');
$metadata = $inspector->getMetadata($package);

echo $metadata->getName() . "\n";        // laravel/framework  
echo $metadata->getDescription() . "\n"; // The Laravel Framework
echo $metadata->getLicense() . "\n";     // MIT
echo $metadata->getRepository() . "\n";  // https://github.com/laravel/framework
```

### Download Statistics

```php
use PackApi\Inspector\DownloadStatsInspector;
use PackApi\Package\NpmPackage;

$inspector = new DownloadStatsInspector([
    $npmFactory->createDownloadStatsProvider(),
    $packagistFactory->createStatsProvider()
]);

$package = new NpmPackage('react');
$stats = $inspector->getStats($package);

$monthly = $stats->get('monthly');
if ($monthly) {
    echo "Downloads this month: " . number_format($monthly->getCount()) . "\n";
    $days = $monthly->getEnd()->diff($monthly->getStart())->days + 1;
    echo "Daily average: " . number_format($monthly->getCount() / $days) . "\n";
}
```

### Swift Package CDN Statistics

Swift packages are identified by their GitHub owner and repository. jsDelivr tracks CDN requests for tagged GitHub releases; these counts measure CDN use, not SwiftPM resolution downloads.

```php
use PackApi\Bridge\JsDelivr\JsDelivrProviderFactory;
use PackApi\Inspector\DownloadStatsInspector;
use PackApi\Package\SwiftPackage;

$inspector = new DownloadStatsInspector([
    (new JsDelivrProviderFactory($httpFactory))->createStatsProvider(),
]);

$stats = $inspector->getStats(new SwiftPackage('Alamofire', 'Alamofire'));
echo $stats?->get('monthly')?->getCount() ?? 'N/A';
```

### Security Advisory Scanning

```php
use PackApi\Inspector\SecurityInspector;
use PackApi\Bridge\OSV\OSVProviderFactory;
use PackApi\Bridge\GitHub\GitHubProviderFactory;

$inspector = new SecurityInspector([
    $osvFactory->createSecurityProvider(),      // OSV Database
    $githubFactory->createSecurityProvider()   // GitHub Security Advisories
]);

$advisories = $inspector->getSecurityAdvisories($package);

foreach ($advisories as $advisory) {
    echo "ALERT: {$advisory->getTitle()}\n";
    echo "   Severity: {$advisory->getSeverity()}\n";
    echo "   Link: {$advisory->getLink()}\n\n";
}
```

### Project Activity Analysis

```php
use PackApi\Inspector\ActivityInspector;

$inspector = new ActivityInspector([
    $githubFactory->createActivityProvider()
]);

$activity = $inspector->getActivitySummary($package);

echo "Last commit: " . $activity->getLastCommit()?->format('Y-m-d') . "\n";
echo "Contributors: " . $activity->getContributors() . "\n";
echo "Open issues: " . $activity->getOpenIssues() . "\n";
echo "Latest release: " . $activity->getLastRelease() . "\n";
```

### Package Content Analysis

```php
use PackApi\Inspector\ContentInspector;

$inspector = new ContentInspector([
    $jsDelivrFactory->createContentProvider(),
    $githubFactory->createContentProvider()
]);

$content = $inspector->getContentOverview($package);

echo "Files: " . $content->getFileCount() . "\n";
echo "Total size: " . number_format($content->getTotalSize()) . " bytes\n";
echo "Has README: " . ($content->hasReadme() ? 'Yes' : 'No') . "\n";
echo "Has tests: " . ($content->hasTests() ? 'Yes' : 'No') . "\n";
```

### Bundle Size Analysis (NPM)

```php
use PackApi\Bridge\BundlePhobia\BundlePhobiaProviderFactory;
$httpFactory = new HttpClientFactory();
$factory = new BundlePhobiaProviderFactory($httpFactory);
$sizeProvider = $factory->createBundleSizeProvider();

$package = new NpmPackage('lodash');
$bundleSize = $sizeProvider->getBundleSize($package);

if ($bundleSize) {
    echo "Bundle size: " . $bundleSize->getFormattedSize() . "\n";
    echo "Gzipped: " . $bundleSize->getFormattedGzipSize() . "\n";
    echo "Dependencies: " . $bundleSize->getDependencyCount() . "\n";
}
```

## Configuration

### HTTP/3 (QUIC)

PackApi supports HTTP/3 for improved performance:

```php
use PackApi\Http\HttpClientFactory;

$httpFactory = new HttpClientFactory();
$client = $httpFactory->createClient([
    'enable_quic' => true  // Automatic fallback if not supported
]);
```

### Caching

Enable HTTP caching at the Symfony HTTP client level (e.g., `CachingHttpClient` with an HttpKernel `Store`). PackApi does not require a separate configuration object.

### Logging

Pass a PSR‑3 logger to `HttpClientFactory` to log outgoing requests in examples and providers.

### GitHub Authentication

For higher GitHub rate limits, provide a token:

```php
// Via environment variable
$_ENV['GITHUB_TOKEN'] = 'ghp_your_token_here';

// Pass the token to GitHubProviderFactory when creating providers
// $github = new GitHubProviderFactory($httpFactory, $_ENV['GITHUB_TOKEN'] ?? null);
```

## Architecture

PackApi uses a clean, extensible architecture:

### Core Components

- **Packages**: Represent different package types (`ComposerPackage`, `NpmPackage`, `SwiftPackage`)
- **Inspectors**: Analyze specific aspects (metadata, downloads, security, etc.)
- **Providers**: Fetch data from external sources (Packagist, GitHub, NPM, etc.)
- **Models**: Strongly-typed value objects for results
- **Facade**: Runs a configured set of inspectors through one entry point

### Provider Pattern

Each inspector accepts one or more providers from the corresponding factory. Providers are tried in order until one succeeds.

```php
$security = new SecurityInspector([
    $osvFactory->createSecurityProvider(),
    $githubFactory->createSecurityProvider(),
    $packagistFactory->createSecurityProvider(),
]);

$advisories = $security->getSecurityAdvisories($package);
```

## Testing

PackApi has comprehensive test coverage:

```bash
# Run tests
composer test

# Run with coverage
composer test-coverage

# Check code style
composer cs

# Fix code style
composer cs-fix
```

## Examples

Run the sample scripts in `examples/` to try PackApi quickly:

- `examples/metadata-analysis.php`: print package metadata
- `examples/download-stats-analysis.php`: print download periods
- `examples/content-analysis.php`: analyze files and flags
- `examples/activity-analysis.php`: summarize repo activity (set `GITHUB_TOKEN` for richer data)
- `examples/security-analysis.php`: list security advisories (OSV/GitHub)
- `examples/bundlephobia-size.php`: show NPM bundle sizes
- `examples/all-analysis.php`: run a combined analysis with sensible fallbacks

Usage:

```bash
php examples/metadata-analysis.php
php examples/all-analysis.php
```

### Adding New Providers

```php
// 1. Implement the provider interface
class MyCustomProvider implements MetadataProviderInterface 
{
    public function supports(Package $package): bool { /* ... */ }
    public function getMetadata(Package $package): ?Metadata { /* ... */ }
}

// 2. Create a factory
class MyCustomProviderFactory 
{
    public function createMetadataProvider(): MyCustomProvider 
    {
        return new MyCustomProvider($this->httpClient);
    }
}

// 3. Use in inspector
$inspector = new MetadataInspector([
    new MyCustomProvider($httpClient)
]);
```

## Requirements

- **PHP 8.3+** (uses modern PHP features)
- **ext-json** (for API responses)
- **ext-curl** (for HTTP requests)
- **ext-mbstring** (for string handling)

### Optional

- **ext-curl with HTTP/3** (for QUIC support)
- **Redis/Memcached** (for distributed caching)

---

## Contributing

Contributions are welcome! Please start by creating an issue to discuss your changes.

---

## Credits

Created and maintained by [Simon André](https://github.com/smnandre).

> [!TIP]
> This library is developed and maintained by a single developer in their free time.
>
> To ensure continued maintenance and improvements, consider [sponsoring development](https://github.com/sponsors/smnandre).

---

## License

MIT License - see [LICENSE](LICENSE) file for details.
