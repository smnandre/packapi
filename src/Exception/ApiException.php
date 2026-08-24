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

namespace PackApi\Exception;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class ApiException extends \RuntimeException
{
    /**
     * @param array<string, mixed>|null $responseData
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly ?int $httpCode = null,
        public readonly ?array $responseData = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
