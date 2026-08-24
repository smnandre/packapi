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
final class NpmPackage extends Package
{
    public function __construct(string $name)
    {
        $this->validatePackageName($name);
        parent::__construct($name, $name);
    }

    private function validatePackageName(string $name): void
    {
        // NPM package names must be:
        // - lowercase
        // - no spaces
        // - no uppercase letters
        // - can contain hyphens and underscores
        // - can be scoped (@scope/package-name)
        // - length between 1-214 characters

        if (empty($name)) {
            throw new ValidationException('NPM package name cannot be empty');
        }

        if (strlen($name) > 214) {
            throw new ValidationException('NPM package name cannot exceed 214 characters');
        }

        // Check for scoped package format (@scope/name)
        if (str_starts_with($name, '@')) {
            if (!preg_match('/^@[a-z0-9-_.]+\/[a-z0-9-_.]+$/', $name)) {
                throw new ValidationException('Invalid NPM scoped package name format. Must be @scope/package-name with lowercase letters, numbers, hyphens, underscores, and dots only');
            }
        } else {
            if (!preg_match('/^[a-z0-9-_.]+$/', $name)) {
                throw new ValidationException('Invalid NPM package name format. Must contain only lowercase letters, numbers, hyphens, underscores, and dots');
            }
        }

        if (str_contains($name, '..')) {
            throw new ValidationException('NPM package name cannot contain consecutive dots');
        }

        if (str_starts_with($name, '.') || str_ends_with($name, '.')) {
            throw new ValidationException('NPM package name cannot start or end with a dot');
        }

        if (str_starts_with($name, '-') || str_ends_with($name, '-')) {
            throw new ValidationException('NPM package name cannot start or end with a hyphen');
        }
    }
}
