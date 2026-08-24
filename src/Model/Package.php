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

namespace PackApi\Model;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class Package
{
    public function __construct(
        public readonly string $vendor,
        public readonly string $name,
    ) {
    }

    public function getFullName(): string
    {
        return "{$this->vendor}/{$this->name}";
    }
}
