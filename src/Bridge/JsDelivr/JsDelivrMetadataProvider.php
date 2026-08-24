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

namespace PackApi\Bridge\JsDelivr;

use PackApi\Model\Metadata;
use PackApi\Package\NpmPackage;
use PackApi\Package\Package;
use PackApi\Provider\MetadataProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class JsDelivrMetadataProvider implements MetadataProviderInterface
{
    public function __construct(private JsDelivrApiClient $client)
    {
    }

    public function supports(Package $package): bool
    {
        return $package instanceof NpmPackage;
    }

    public function getMetadata(Package $package): ?Metadata
    {
        if (!$package instanceof NpmPackage) {
            return null;
        }

        $meta = $this->client->fetchPackageMeta('npm/'.$package->getName());
        if (!$meta) {
            return null;
        }

        return new Metadata(
            name: $meta['name'] ?? $package->getIdentifier(),
            description: $meta['description'] ?? '',
            license: $meta['license'] ?? null,
            repository: $meta['repository'] ?? null,
        );
    }
}
