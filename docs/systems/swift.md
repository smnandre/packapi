# Swift Package System

`SwiftPackage` represents a Swift Package Manager package hosted in GitHub.

```php
use PackApi\Package\SwiftPackage;

$package = new SwiftPackage('Alamofire', 'Alamofire');
```

The package name and identifier are both `Alamofire/Alamofire`; its repository URL is set to `https://github.com/Alamofire/Alamofire`.

This makes the package compatible with the existing GitHub providers for metadata, activity, security, and repository content. The jsDelivr bridge also supports it for CDN statistics and released-file listings:

```php
use PackApi\Bridge\JsDelivr\JsDelivrProviderFactory;
use PackApi\Inspector\DownloadStatsInspector;

$downloads = new DownloadStatsInspector([
    (new JsDelivrProviderFactory($httpFactory))->createStatsProvider(),
]);

$stats = $downloads->getStats($package);
```

jsDelivr reports CDN requests for tagged GitHub releases. This is not a count of SwiftPM dependency resolutions. PackApi does not currently use Swift Package Index as a data provider.
