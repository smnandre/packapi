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

use PackApi\Model\ContentOverview;
use PackApi\Package\Package;
use PackApi\Provider\ContentProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class ContentInspector implements ContentInspectorInterface
{
    /**
     * @param iterable<ContentProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    public function getContentOverview(Package $package): ?ContentOverview
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($package)) {
                $content = $provider->getContentOverview($package);
                if (null !== $content) {
                    return $content;
                }
            }
        }

        return null;
    }
}
