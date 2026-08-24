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

namespace PackApi\Auth;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class EnvAuthenticationManager implements AuthenticationManagerInterface
{
    public function __construct(private readonly string $githubTokenEnvVariable = 'GITHUB_TOKEN')
    {
    }

    public function getGitHubToken(): ?string
    {
        $token = getenv($this->githubTokenEnvVariable);

        return false !== $token ? $token : null;
    }
}
