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

namespace PackApi\System\Composer;

use PackApi\Bridge\Packagist\PackagistApiClient;
use PackApi\Model\Metadata;
use PackApi\Package\ComposerPackage;
use PackApi\Package\Package;
use PackApi\Provider\MetadataProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class ComposerMetadataProvider implements MetadataProviderInterface
{
    public function __construct(private readonly PackagistApiClient $client)
    {
    }

    public function supports(Package $package): bool
    {
        return $package instanceof ComposerPackage;
    }

    public function getMetadata(Package $package): ?Metadata
    {
        $data = $this->client->fetchPackage($package->getIdentifier());

        if (empty($data['package'])) {
            return null;
        }

        $info = $data['package'];
        $licenseData = $info['license'] ?? null;
        $license = null;
        if (is_array($licenseData)) {
            $license = implode(',', $licenseData);
        } elseif (is_string($licenseData)) {
            $license = $licenseData;
        }

        return new Metadata(
            name: $info['name'] ?? $package->getIdentifier(),
            description: $info['description'] ?? null,
            license: $license,
            repository: $info['repository'] ?? null,
        );
    }
}
