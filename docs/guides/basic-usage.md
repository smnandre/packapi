# Basic usage

## Overview

PackApi can inspect Composer, NPM, and GitHub-hosted Swift packages through one configured facade. Start with the default preset. Add individual providers only when your application needs different ordering or a private data source.

## Inspect a package

```php
use PackApi\Inspector\PackageInspectorFacade;
use PackApi\Package\ComposerPackage;

$inspector = PackageInspectorFacade::defaults();
$report = $inspector->inspect(new ComposerPackage('symfony/console'));
```

`inspect()` requests metadata first. When metadata contains a GitHub repository, the facade attaches it to the package before requesting content, activity, and security data.

## Read the report

Detailed results remain typed and nullable:

```php
echo $report->metadata?->name ?? 'Unknown';
echo $report->downloads?->get('monthly')?->getCount() ?? 0;
echo $report->quality?->score ?? 0;

foreach ($report->securityAdvisories ?? [] as $advisory) {
    echo $advisory->severity.': '.$advisory->title;
}
```

Use the convenience methods for common application decisions:

```php
if (!$report->isComplete()) {
    echo 'Missing: '.implode(', ', $report->getMissingSections());
}

if ($report->hasSecurityAdvisories()) {
    echo $report->getSecurityAdvisoryCount().' advisories';
}

$summary = $report->getSummary();
```

The summary contains identifiers, repository URL, available and missing sections, monthly downloads, advisory count, quality result, and last commit date. It does not discard the detailed model objects stored on the report.

## Other package types

```php
use PackApi\Package\NpmPackage;
use PackApi\Package\SwiftPackage;

$npm = $inspector->inspect(new NpmPackage('@playwright/test'));
$swift = $inspector->inspect(new SwiftPackage('Alamofire', 'Alamofire'));
```

## GitHub authentication

The default builder reads `GITHUB_TOKEN` from the environment. You can also pass it explicitly:

```php
$inspector = PackageInspectorFacade::defaults(
    githubToken: getenv('GITHUB_TOKEN') ?: null,
);
```

## Add a custom provider

Custom providers run before the defaults:

```php
$inspector = PackageInspectorFacade::builder()
    ->withMetadataProvider($metadataProvider)
    ->withSecurityProvider($securityProvider)
    ->build();
```

The builder is immutable, so presets can be shared safely:

```php
$defaults = PackageInspectorFacade::builder();
$internal = $defaults->withMetadataProvider($internalMetadata);

$publicInspector = $defaults->build();
$internalInspector = $internal->build();
```

## Legacy array result

`analyze()` remains available for existing applications:

```php
$result = $inspector->analyze(new ComposerPackage('symfony/console'));
```

New code should use `inspect()` because `PackageReport` preserves types and distinguishes unavailable sections from empty results.
