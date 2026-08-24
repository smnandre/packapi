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

namespace PackApi\Inspector;

use PackApi\Auth\EnvAuthenticationManager;
use PackApi\Bridge\GitHub\GitHubProviderFactory;
use PackApi\Bridge\JsDelivr\JsDelivrProviderFactory;
use PackApi\Bridge\Npm\NpmProviderFactory;
use PackApi\Bridge\OSV\OSVProviderFactory;
use PackApi\Bridge\Packagist\PackagistProviderFactory;
use PackApi\Http\HttpClientFactory;
use PackApi\Http\HttpClientFactoryInterface;
use PackApi\Provider\ActivityProviderInterface;
use PackApi\Provider\ContentProviderInterface;
use PackApi\Provider\DownloadStatsProviderInterface;
use PackApi\Provider\MetadataProviderInterface;
use PackApi\Provider\SecurityProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class PackageInspectorBuilder
{
    /** @var list<MetadataProviderInterface> */
    private array $metadataProviders = [];

    /** @var list<DownloadStatsProviderInterface> */
    private array $downloadProviders = [];

    /** @var list<ContentProviderInterface> */
    private array $contentProviders = [];

    /** @var list<ActivityProviderInterface> */
    private array $activityProviders = [];

    /** @var list<SecurityProviderInterface> */
    private array $securityProviders = [];

    private ?QualityInspectorInterface $qualityInspector = null;

    private function __construct(
        private readonly HttpClientFactoryInterface $httpClientFactory,
        #[\SensitiveParameter]
        private ?string $githubToken,
    ) {
    }

    public static function defaults(?HttpClientFactoryInterface $httpClientFactory = null): self
    {
        return new self(
            $httpClientFactory ?? new HttpClientFactory(),
            (new EnvAuthenticationManager())->getGitHubToken(),
        );
    }

    public function withGitHubToken(#[\SensitiveParameter] ?string $token): self
    {
        $builder = clone $this;
        $builder->githubToken = $token;

        return $builder;
    }

    public function withMetadataProvider(MetadataProviderInterface $provider): self
    {
        $builder = clone $this;
        $builder->metadataProviders[] = $provider;

        return $builder;
    }

    public function withDownloadProvider(DownloadStatsProviderInterface $provider): self
    {
        $builder = clone $this;
        $builder->downloadProviders[] = $provider;

        return $builder;
    }

    public function withContentProvider(ContentProviderInterface $provider): self
    {
        $builder = clone $this;
        $builder->contentProviders[] = $provider;

        return $builder;
    }

    public function withActivityProvider(ActivityProviderInterface $provider): self
    {
        $builder = clone $this;
        $builder->activityProviders[] = $provider;

        return $builder;
    }

    public function withSecurityProvider(SecurityProviderInterface $provider): self
    {
        $builder = clone $this;
        $builder->securityProviders[] = $provider;

        return $builder;
    }

    public function withQualityInspector(QualityInspectorInterface $inspector): self
    {
        $builder = clone $this;
        $builder->qualityInspector = $inspector;

        return $builder;
    }

    public function build(): PackageInspectorFacade
    {
        $packagist = new PackagistProviderFactory($this->httpClientFactory);
        $npm = new NpmProviderFactory($this->httpClientFactory);
        $github = new GitHubProviderFactory($this->httpClientFactory, $this->githubToken);
        $jsdelivr = new JsDelivrProviderFactory($this->httpClientFactory);
        $osv = new OSVProviderFactory($this->httpClientFactory);

        $metadataInspector = new MetadataInspector([
            ...$this->metadataProviders,
            $packagist->createMetadataProvider(),
            $npm->createMetadataProvider(),
            $jsdelivr->createMetadataProvider(),
            $github->createMetadataProvider(),
        ]);
        $downloadInspector = new DownloadStatsInspector([
            ...$this->downloadProviders,
            $packagist->createStatsProvider(),
            $npm->createDownloadStatsProvider(),
            $jsdelivr->createStatsProvider(),
        ]);
        $contentInspector = new ContentInspector([
            ...$this->contentProviders,
            $github->createContentProvider(),
            $packagist->createContentProvider(),
            $npm->createContentProvider(),
            $jsdelivr->createContentProvider(),
        ]);
        $activityInspector = new ActivityInspector([
            ...$this->activityProviders,
            $github->createActivityProvider(),
            $packagist->createActivityProvider(),
        ]);
        $securityInspector = new SecurityInspector([
            ...$this->securityProviders,
            $osv->createSecurityProvider(),
            $github->createSecurityProvider(),
            $packagist->createSecurityProvider(),
        ]);

        return new PackageInspectorFacade(
            $metadataInspector,
            $downloadInspector,
            $contentInspector,
            $activityInspector,
            $securityInspector,
            $this->qualityInspector ?? new QualityInspector($contentInspector, $metadataInspector),
        );
    }
}
