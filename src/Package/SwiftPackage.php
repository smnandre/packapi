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

use PackApi\Exception\ValidationException;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class SwiftPackage extends Package
{
    private readonly string $owner;
    private readonly string $repository;

    public function __construct(string $owner, string $repository)
    {
        self::validateSegment($owner, 'owner');
        self::validateSegment($repository, 'repository');

        $this->owner = $owner;
        $this->repository = $repository;

        parent::__construct("{$owner}/{$repository}", "{$owner}/{$repository}");
        $this->setRepositoryUrl("https://github.com/{$owner}/{$repository}");
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function getRepository(): string
    {
        return $this->repository;
    }

    private static function validateSegment(string $value, string $label): void
    {
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $value)) {
            throw new ValidationException(sprintf('Invalid Swift package %s: use letters, numbers, dots, underscores, or hyphens without a leading separator', $label));
        }
    }
}
