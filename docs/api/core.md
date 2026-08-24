# Core API

## PackageInspectorFacade

`PackageInspectorFacade` is the main entry point for a complete package analysis. The default preset configures the built-in Packagist, NPM, GitHub, jsDelivr, and OSV providers.

```php
use PackApi\Inspector\PackageInspectorFacade;
use PackApi\Package\ComposerPackage;

$report = PackageInspectorFacade::defaults()
    ->inspect(new ComposerPackage('symfony/console'));
```

Pass a GitHub token when you need higher API limits:

```php
$inspector = PackageInspectorFacade::defaults(
    githubToken: getenv('GITHUB_TOKEN') ?: null,
);
```

The existing `analyze()` method remains available and returns its legacy array shape. New code should prefer `inspect()` and its typed `PackageReport`.

## PackageReport

The report exposes every detailed result as a typed readonly property:

- `metadata`
- `downloads`
- `content`
- `activity`
- `securityAdvisories`
- `quality`

It also answers common questions without requiring callers to traverse the models:

```php
$report->isComplete();
$report->getMissingSections();
$report->getMonthlyDownloads();
$report->hasSecurityAdvisories();
$report->getSecurityAdvisoryCount();
$report->getRepositoryUrl();
$report->getSummary();
```

An empty security result is considered available data. A `null` section means no configured provider returned data.

## PackageInspectorBuilder

Use the builder when a custom provider must run before the defaults:

```php
$inspector = PackageInspectorFacade::builder()
    ->withMetadataProvider($metadataProvider)
    ->withSecurityProvider($securityProvider)
    ->build();
```

The builder is immutable. Each `with...()` method returns a new builder and leaves the previous instance unchanged. Custom providers are evaluated before default providers.

Supported methods are:

- `withGitHubToken()`
- `withMetadataProvider()`
- `withDownloadProvider()`
- `withContentProvider()`
- `withActivityProvider()`
- `withSecurityProvider()`
- `withQualityInspector()`

Pass a custom `HttpClientFactoryInterface` to `defaults()` or `builder()` to reuse application logging, caching, or HTTP options.

## Package classes

### ComposerPackage

```php
use PackApi\Package\ComposerPackage;

$package = new ComposerPackage('symfony/console');
```

### NpmPackage

```php
use PackApi\Package\NpmPackage;

$package = new NpmPackage('@playwright/test');
```

### SwiftPackage

`SwiftPackage` represents a Swift Package Manager package hosted in a GitHub repository.

```php
use PackApi\Package\SwiftPackage;

$package = new SwiftPackage('Alamofire', 'Alamofire');
```

Its repository URL is set automatically to `https://github.com/Alamofire/Alamofire`.
