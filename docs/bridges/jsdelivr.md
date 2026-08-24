# jsDelivr Bridge Documentation

> **Integration with jsDelivr API for CDN statistics and package metadata**

The jsDelivr Bridge interacts with the jsDelivr data API to retrieve CDN request counts, package metadata and file listings for npm packages and GitHub-hosted Swift packages.

---

## 📋 **Overview**

| Component | Purpose | Status |
|-----------|---------|--------|
| `JsDelivrApiClient` | jsDelivr API communication | ✅ Complete |
| `JsDelivrMetadataProvider` | Package metadata extraction | ✅ Complete |
| `JsDelivrStatsProvider` | CDN download statistics | ✅ Complete |
| `JsDelivrContentProvider` | Package file listing | ✅ Complete |
| `JsDelivrProviderFactory` | Provider instantiation | ✅ Complete |

---

## 🔧 **Configuration**

### **Authentication**
jsDelivr does not require authentication. The factory simply sets a `User-Agent` header for polite usage.

### **Rate Limiting**
No official limits are documented, but it is recommended to add your own rate limiter if making large numbers of requests.

### **API Base**
`JsDelivrProviderFactory` scopes the HTTP client to `https://data.jsdelivr.com`, which is the base URI for all API calls.

---

## 🏭 **Provider Factory Usage**

```php
use PackApi\Bridge\JsDelivr\JsDelivrProviderFactory;
use PackApi\Http\HttpClientFactory;

$httpFactory = new HttpClientFactory();
$factory = new JsDelivrProviderFactory($httpFactory);

$statsProvider = $factory->createStatsProvider();
$metadataProvider = $factory->createMetadataProvider();
$contentProvider = $factory->createContentProvider();
```

---

## 🔍 **API Client Methods**

### **Package Information**
```php
$client = new JsDelivrApiClient($httpClient);

// Fetch metadata for an npm package
$meta = $client->fetchPackageMeta('npm/lodash');
```

### **File Listing**
```php
// List files for the latest version
$files = $client->fetchFileList('npm/lodash');

// List files for a specific version
$files = $client->fetchFileList('npm/lodash', '4.17.21');
```

### **CDN statistics**

```php
// npm package
$stats = $client->fetchPackageStats('npm', 'lodash');

// tagged GitHub release, including a Swift package
$stats = $client->fetchPackageStats('gh', 'Alamofire/Alamofire');
```

---

## 📦 **Provider Implementations**

### **Metadata Provider**
Uses `fetchPackageMeta()` to populate the generic `Metadata` model with name, description, license and repository URL.

### **Stats Provider**
Reads the daily `hits.dates` values from `fetchPackageStats()` and exposes their rolling reporting window as the `monthly` `DownloadPeriod`. `cdnRequests`, `cdnBandwidth`, and `lastUpdated` are also populated on `DownloadStats`.

Custom periods are calculated from the daily values currently returned by jsDelivr. A period outside that reporting window returns `null`.

### **Content Provider**
Transforms the `fetchFileList()` response into a `ContentOverview` with file count, total size and file flags.

---

## 🔄 **Caching Strategy**
Responses can be cached by your HTTP client implementation. The factory only sets the base URI and headers.

### **Cache Configuration**
Enable HTTP caching at the client level in your application if desired. PackApi does not require a separate configuration class.

---

## ⚠️ **Error Handling**
`JsDelivrApiClient` throws `NetworkException` on non‑200 responses or transport errors.

### **HTTP Status Codes**
- **200**: Success
- **404**: Package or version not found → returns `null`
- **500**: Server error → throws `NetworkException`

---

## 🔧 **Advanced Configuration**

### **Custom HTTP Client Options**
```php
$httpClient = $httpFactory->createClient(['timeout' => 10]);
$factory = new JsDelivrProviderFactory($httpFactory);
```

### **CDN Endpoint Configuration**
Override the base URI if using a mirror:
```php
$client = $httpFactory->createClient([
    'base_uri' => 'https://data.jsdelivr.com/',
]);
```

---

## 📊 **Performance Considerations**

### **Request Optimization**
- Cache responses to minimize network calls
- Avoid requesting file lists for very large packages unless necessary

### **Memory Usage**
- Large file lists can consume memory; process them iteratively if required

---

## 🧪 **Testing**
Use `MockHttpClient` from Symfony to simulate API responses when testing providers.

---

## 🔗 **jsDelivr API Documentation**
- **[jsDelivr Data API](https://github.com/jsdelivr/data.jsdelivr.com)**

---

## 🚀 **Future Enhancements**
- Additional CDN statistics endpoints when available
- Improved version handling and cache directives

---

*Last updated: 2026-08-24*
