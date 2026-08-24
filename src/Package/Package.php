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

namespace PackApi\Package;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
abstract class Package
{
    protected ?string $repositoryUrl = null;

    public function __construct(
        protected readonly string $name,
        protected readonly string $identifier,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setRepositoryUrl(?string $url): void
    {
        $this->repositoryUrl = $url;
    }

    public function getRepositoryUrl(): ?string
    {
        return $this->repositoryUrl;
    }
}
