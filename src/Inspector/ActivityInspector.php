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

use PackApi\Model\ActivitySummary;
use PackApi\Package\Package;
use PackApi\Provider\ActivityProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class ActivityInspector implements ActivityInspectorInterface
{
    /**
     * @param iterable<ActivityProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    public function getActivitySummary(Package $package): ?ActivitySummary
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($package)) {
                return $provider->getActivitySummary($package);
            }
        }

        return null;
    }
}
