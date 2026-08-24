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
final class Metadata
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly ?string $license = null,
        public readonly ?string $repository = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLicense(): ?string
    {
        return $this->license;
    }

    public function getRepository(): ?string
    {
        return $this->repository;
    }
}
