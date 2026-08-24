# Core Classes API Reference

## PackageInspectorFacade

### Constructor
### Methods
### Usage Examples

## Configuration

There is no global configuration class. Configure behavior via:
- HTTP options passed to `HttpClientFactory`
- Environment variables (e.g., `GITHUB_TOKEN`)

## Package Classes

### Package (Abstract)
### ComposerPackage
### NpmPackage
### SwiftPackage

`SwiftPackage` represents a Swift Package Manager package hosted in a GitHub repository.

```php
use PackApi\Package\SwiftPackage;

$package = new SwiftPackage('Alamofire', 'Alamofire');
// https://github.com/Alamofire/Alamofire is set as its repository URL.
```

## HTTP Client Factory

### HttpClientFactory
### Interface Definition
### Configuration Options

## Authentication Manager

### EnvAuthenticationManager
### Interface Definition
### Usage Examples

## Cache Interface

### CacheInterface
### FilesystemCache
### MemoryCache

## Logger

### PackApiLogger
### Configuration
### Usage Examples
