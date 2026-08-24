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
final class File
{
    public function __construct(
        public readonly string $path,
        public readonly int $size,
        public readonly bool $isDirectory = false,
        public readonly ?\DateTimeImmutable $timestamp = null,
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function isDirectory(): bool
    {
        return $this->isDirectory;
    }

    public function getTimestamp(): ?\DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function getHumanSize(int $decimals = 2): string
    {
        $size = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            ++$i;
        }

        return round($size, $decimals).' '.$units[$i];
    }
}
