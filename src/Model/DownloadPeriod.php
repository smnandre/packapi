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
final class DownloadPeriod
{
    public function __construct(
        public readonly string $type, // e.g. 'total', 'weekly', 'monthly'
        public readonly int $count,
        public readonly \DateTimeImmutable $start,
        public readonly \DateTimeImmutable $end,
    ) {
    }

    public function is(string $type): bool
    {
        return $this->type === $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getStart(): \DateTimeImmutable
    {
        return $this->start;
    }

    public function getEnd(): \DateTimeImmutable
    {
        return $this->end;
    }
}
