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

namespace PackApi\System\Npm;

use PackApi\Bridge\Npm\NpmApiClient;
use PackApi\Model\Metadata;
use PackApi\Package\NpmPackage;
use PackApi\Package\Package;
use PackApi\Provider\MetadataProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class NpmMetadataProvider implements MetadataProviderInterface
{
    public function __construct(private readonly NpmApiClient $client)
    {
    }

    public function supports(Package $package): bool
    {
        return $package instanceof NpmPackage;
    }

    public function getMetadata(Package $package): ?Metadata
    {
        $data = $this->client->fetchPackageInfo($package->getName());

        if (null === $data) {
            return null;
        }

        $repositoryUrl = null;
        if (isset($data['repository'])) {
            if (is_string($data['repository'])) {
                $repositoryUrl = $data['repository'];
            } elseif (is_array($data['repository']) && isset($data['repository']['url'])) {
                $repositoryUrl = $data['repository']['url'];
            }
        }

        $license = null;
        if (isset($data['license'])) {
            if (is_string($data['license'])) {
                $license = $data['license'];
            } elseif (is_array($data['license']) && isset($data['license']['type'])) {
                $license = $data['license']['type'];
            }
        }

        return new Metadata(
            name: $data['name'] ?? $package->getName(),
            description: $data['description'] ?? null,
            license: $license,
            repository: $repositoryUrl
        );
    }
}
