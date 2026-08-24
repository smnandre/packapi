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
final class BundleSize
{
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly ?string $description,
        public readonly int $size,
        public readonly int $gzip,
        public readonly int $dependencyCount,
        public readonly int $dependencySize,
        public readonly bool $hasJSModule,
        public readonly bool $hasSideEffects,
        public readonly bool $isScoped,
        public readonly ?string $repository,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getGzipSize(): int
    {
        return $this->gzip;
    }

    public function getDependencyCount(): int
    {
        return $this->dependencyCount;
    }

    public function getDependencySize(): int
    {
        return $this->dependencySize;
    }

    public function hasJSModule(): bool
    {
        return $this->hasJSModule;
    }

    public function hasSideEffects(): bool
    {
        return $this->hasSideEffects;
    }

    public function isScoped(): bool
    {
        return $this->isScoped;
    }

    public function getRepository(): ?string
    {
        return $this->repository;
    }

    /**
     * Get human-readable size format.
     */
    public function getFormattedSize(): string
    {
        return $this->formatBytes($this->size);
    }

    /**
     * Get human-readable gzipped size format.
     */
    public function getFormattedGzipSize(): string
    {
        return $this->formatBytes($this->gzip);
    }

    /**
     * Get human-readable dependency size format.
     */
    public function getFormattedDependencySize(): string
    {
        return $this->formatBytes($this->dependencySize);
    }

    private function formatBytes(int $bytes): string
    {
        if (0 === $bytes) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $base = 1024;
        $exp = (int) floor(log($bytes) / log($base));

        return round($bytes / pow($base, $exp), 1).' '.$units[$exp];
    }
}
