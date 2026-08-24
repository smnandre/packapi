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
final class ContentOverview
{
    /**
     * @param File[] $files
     */
    public function __construct(
        public readonly int $fileCount,
        public readonly int $totalSize,
        public readonly bool $hasReadme = false,
        public readonly bool $hasLicense = false,
        public readonly bool $hasTests = false,
        /** @var string[] */
        public readonly array $ignoredFiles = [],
        public readonly array $files = [],
        public readonly bool $hasGitattributes = false,
        public readonly bool $hasGitignore = false,
    ) {
    }

    public function getFileCount(): int
    {
        return $this->fileCount;
    }

    public function getTotalSize(): int
    {
        return $this->totalSize;
    }

    public function hasReadme(): bool
    {
        return $this->hasReadme;
    }

    public function hasLicense(): bool
    {
        return $this->hasLicense;
    }

    public function hasTests(): bool
    {
        return $this->hasTests;
    }

    /**
     * @return string[]
     */
    public function getIgnoredFiles(): array
    {
        return $this->ignoredFiles;
    }

    /**
     * @return File[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function hasGitattributes(): bool
    {
        return $this->hasGitattributes;
    }

    public function hasGitignore(): bool
    {
        return $this->hasGitignore;
    }
}
