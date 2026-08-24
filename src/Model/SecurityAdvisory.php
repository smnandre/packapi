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
final class SecurityAdvisory
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $severity,
        public readonly string $link,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getLink(): string
    {
        return $this->link;
    }
}
