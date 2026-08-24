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

namespace PackApi\Exception;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class UnsupportedPackageException extends \LogicException
{
    public function __construct(string $packageClass)
    {
        parent::__construct(\sprintf('Unsupported package type "%s".', $packageClass));
    }
}
