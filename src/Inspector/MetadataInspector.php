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

use PackApi\Model\Metadata;
use PackApi\Package\Package;
use PackApi\Provider\MetadataProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class MetadataInspector implements MetadataInspectorInterface
{
    /**
     * @param iterable<MetadataProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    /**
     * Get metadata for the given package from the first supporting provider.
     *
     * @return Metadata|null returns null if not supported or no data available
     */
    public function getMetadata(Package $package): ?Metadata
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($package)) {
                return $provider->getMetadata($package);
            }
        }

        return null;
    }
}
