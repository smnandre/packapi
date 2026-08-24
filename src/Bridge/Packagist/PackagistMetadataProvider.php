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

namespace PackApi\Bridge\Packagist;

use PackApi\Model\Metadata;
use PackApi\Package\Package;
use PackApi\Provider\MetadataProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class PackagistMetadataProvider implements MetadataProviderInterface
{
    public function __construct(private PackagistApiClient $client)
    {
    }

    public function supports(Package $package): bool
    {
        return $package->getIdentifier() && str_contains($package->getIdentifier(), '/');
    }

    public function getMetadata(Package $package): ?Metadata
    {
        $data = $this->client->fetchPackage($package->getIdentifier());
        if (empty($data['package'])) {
            return null;
        }
        $info = $data['package'];

        return new Metadata(
            name: $info['name'] ?? $package->getIdentifier(),
            description: $info['description'] ?? '',
            license: is_array($info['license'] ?? null) ? implode(',', $info['license']) : ($info['license'] ?? null),
            repository: $info['repository'] ?? null,
        );
    }
}
