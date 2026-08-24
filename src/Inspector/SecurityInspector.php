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

use PackApi\Model\SecurityAdvisory;
use PackApi\Package\Package;
use PackApi\Provider\SecurityProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class SecurityInspector implements SecurityInspectorInterface
{
    /**
     * @param iterable<SecurityProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    /**
     * @return SecurityAdvisory[]|null returns null if not supported or no data available
     */
    public function getSecurityAdvisories(Package $package): ?array
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($package)) {
                return $provider->getSecurityAdvisories($package);
            }
        }

        return null;
    }
}
