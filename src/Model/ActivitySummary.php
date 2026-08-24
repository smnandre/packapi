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
final class ActivitySummary
{
    public function __construct(
        public readonly ?\DateTimeImmutable $lastCommit = null,
        public readonly int $contributors = 0,
        public readonly int $openIssues = 0,
        public readonly ?string $lastRelease = null,
    ) {
    }

    public function getLastCommit(): ?\DateTimeImmutable
    {
        return $this->lastCommit;
    }

    public function getContributors(): int
    {
        return $this->contributors;
    }

    public function getOpenIssues(): int
    {
        return $this->openIssues;
    }

    public function getLastRelease(): ?string
    {
        return $this->lastRelease;
    }
}
