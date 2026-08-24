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

namespace PackApi\Bridge\GitHub;

use PackApi\Model\SecurityAdvisory;
use PackApi\Package\Package;
use PackApi\Provider\SecurityProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class GitHubSecurityProvider implements SecurityProviderInterface
{
    public function __construct(private GitHubApiClient $client)
    {
    }

    public function supports(Package $package): bool
    {
        $repo = $package->getRepositoryUrl();

        return is_string($repo) && str_contains($repo, 'github.com');
    }

    /**
     * @return SecurityAdvisory[]
     */
    public function getSecurityAdvisories(Package $package): array
    {
        $repo = $package->getRepositoryUrl();
        if (!$repo) {
            return [];
        }

        try {
            $repoName = $this->client->extractRepoName($repo);
            if (!$repoName) {
                return [];
            }

            $data = $this->client->fetchSecurityAdvisories($repoName);
            if (!$data) {
                return [];
            }

            $advisories = $data['security_advisories'] ?? [];
            if (empty($advisories)) {
                return [];
            }

            return array_map(fn ($advisory) => new SecurityAdvisory(
                id: $advisory['ghsa_id'] ?? $advisory['id'] ?? 'unknown',
                title: $advisory['summary'] ?? $advisory['title'] ?? 'Unknown Advisory',
                severity: $advisory['severity'] ?? 'unknown',
                link: $advisory['html_url'] ?? '',
            ), $advisories);
        } catch (\Exception) {
            return [];
        }
    }
}
