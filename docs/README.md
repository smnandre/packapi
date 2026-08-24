# PackApi Documentation

> **Modern PHP Library for Package Analysis Across Multiple Ecosystems**

Welcome to the comprehensive documentation for PackApi, a provider-based library that analyzes Composer, NPM, and Swift packages through Packagist, GitHub, jsDelivr, OSV, and other data providers.

---

## 📚 **Documentation Structure**

### **Getting Started**
- **[Installation & Setup](guides/installation.md)** - Quick start guide
- **[Basic Usage](guides/basic-usage.md)** - Your first package analysis
- **[Configuration](guides/configuration.md)** - Settings and customization

### **Bridge Integrations** 📦
External service integrations for fetching package data:

- **[GitHub Bridge](bridges/github.md)** - Repository analysis and activity tracking
- **[Packagist Bridge](bridges/packagist.md)** - Composer package metadata and stats
- **[NPM Bridge](bridges/npm.md)** - Node.js package registry integration
- **[jsDelivr Bridge](bridges/jsdelivr.md)** - CDN statistics and content analysis
- **[BundlePhobia Bridge](bridges/bundlephobia.md)** - Bundle size analysis service
- **[OSV Bridge](bridges/osv.md)** - Security advisories from OSV

### **Package Systems** 🔧
Package-specific implementations and providers:

- **[Composer System](systems/composer.md)** - PHP package ecosystem
- **[NPM System](systems/npm.md)** - Node.js package ecosystem
- **[Swift System](systems/swift.md)** - GitHub-hosted Swift packages

### **Analysis Types** 🔍
Available package analysis capabilities:

- **[Metadata Analysis](analysis/metadata.md)** - Package information, licensing, and repository details
- **[Download Statistics](analysis/download-stats.md)** - Usage metrics and trends
- **[Content Analysis](analysis/content.md)** - File structure, documentation, and code quality
- **[Security Analysis](analysis/security.md)** - Vulnerability scanning and advisories
- **[Activity Analysis](analysis/activity.md)** - Repository activity and maintenance status
- **[Quality Analysis](analysis/quality.md)** - Code quality scoring and best practices

### **API Reference** 📖
- **[Core Classes](api/core.md)** - Main interfaces and facades
- **[Providers](api/providers.md)** - Provider interfaces and implementations
- **[Inspectors](api/inspectors.md)** - Analysis orchestration classes
- **[Models](api/models.md)** - Data objects and structures
- **[Exceptions](api/exceptions.md)** - Error handling classes

---

## 🚀 **Quick Start Example**

```php
use PackApi\Bridge\Packagist\PackagistProviderFactory;
use PackApi\Bridge\GitHub\GitHubProviderFactory;
use PackApi\Bridge\OSV\OSVProviderFactory;
use PackApi\Http\HttpClientFactory;
use PackApi\Inspector\{MetadataInspector, DownloadStatsInspector, ContentInspector, ActivityInspector, SecurityInspector, QualityInspector};
use PackApi\Package\ComposerPackage;

$httpFactory = new HttpClientFactory();
$packagist = new PackagistProviderFactory($httpFactory);
$github    = new GitHubProviderFactory($httpFactory, $_ENV['GITHUB_TOKEN'] ?? null);
$osv       = new OSVProviderFactory($httpFactory);

$metadataInspector = new MetadataInspector([
    $packagist->createMetadataProvider(),
    $github->createMetadataProvider(),
]);
$downloadsInspector = new DownloadStatsInspector([
    $packagist->createStatsProvider(),
]);

$package = new ComposerPackage('symfony/maker-bundle');
$metadata = $metadataInspector->getMetadata($package);
$downloads = $downloadsInspector->getStats($package);

echo "Package: " . ($metadata?->name ?? 'N/A') . "\n";
echo "Downloads (monthly): " . ($downloads?->get('monthly')?->getCount() ?? 'N/A') . "\n";
```

---

## 🏗️ **Architecture Overview**

PackApi follows a **provider-based architecture** with three key layers:

```
┌─────────────────┐
│     Facade      │ ← Single entry point
├─────────────────┤
│   Inspectors    │ ← Orchestration layer
├─────────────────┤
│   Providers     │ ← Data source implementations
└─────────────────┘
```

### **Key Patterns**
- **Provider Pattern**: Each data source (GitHub, NPM, etc.) implements provider interfaces
- **Inspector Pattern**: Orchestrates multiple providers for comprehensive analysis
- **Bridge Pattern**: Encapsulates external API integrations
- **Factory Pattern**: Creates configured provider instances

---

## 🔧 **Supported Ecosystems**

| Package type | Metadata | Downloads | Content | Security | Activity |
|--------------|----------|-----------|---------|----------|----------|
| `ComposerPackage` | Packagist, GitHub | Packagist | Packagist, GitHub | Packagist, OSV, GitHub | Packagist, GitHub |
| `NpmPackage` | NPM, jsDelivr, GitHub | NPM, jsDelivr | NPM, jsDelivr, GitHub | OSV, GitHub | GitHub |
| `SwiftPackage` | GitHub | jsDelivr | GitHub, jsDelivr | GitHub | GitHub |

GitHub providers require a GitHub repository URL. `SwiftPackage` sets it automatically; other package types can receive one through `Package::setRepositoryUrl()` or registry metadata.

---

## 📋 **Requirements**

- **PHP**: 8.3 or higher
- **Extensions**: `curl`, `json`, `mbstring`
- **Dependencies**: Symfony HTTP Client, PSR-compatible logger

---

## 🤝 **Contributing**

1. Fork the repository and create a feature branch
2. Write tests for your changes and ensure they pass
3. Follow PSR-12 code standards with `composer cs-fix`
4. Submit a pull request with a clear description

---

## 📄 **License**

This project is licensed under the MIT License. See the [LICENSE](../LICENSE) file for details.

---

## 🔗 **Links**

- **[GitHub Repository](https://github.com/smnandre/packapi)**
- **[Issue Tracker](https://github.com/smnandre/packapi/issues)**
- **[Changelog](../CHANGELOG.md)**

---

*Documentation last updated: 2026-08-24*
