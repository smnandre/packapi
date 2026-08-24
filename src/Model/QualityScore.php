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
final class QualityScore
{
    /**
     * @param array<string, mixed> $criteria e.g. ['hasReadme' => true, ...]
     */
    public function __construct(
        public readonly int $score, // 0-100
        public readonly array $criteria = [], // e.g. ['hasReadme' => true, ...]
        public readonly ?string $grade = null, // e.g. 'A', 'B', 'C'
        public readonly ?string $comment = null,
    ) {
    }

    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
}
