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

use PackApi\Model\ActivitySummary;
use PackApi\Package\Package;
use PackApi\Provider\ActivityProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class GitHubActivityProvider implements ActivityProviderInterface
{
    public function __construct(private GitHubApiClient $client)
    {
    }

    public function supports(Package $package): bool
    {
        // Try to infer GitHub repository URL from Composer package name
        // e.g. symfony/ux-live-component => https://github.com/symfony/ux-live-component
        $repoUrl = $package->getRepositoryUrl();
        if ($repoUrl) {
            return str_contains($repoUrl, 'github.com');
        }

        $id = $package->getIdentifier();
        if (preg_match('#^([\w-]+)/([\w.-]+)$#', $id)) {
            return true;
        }

        return false;
    }

    public function getActivitySummary(Package $package): ?ActivitySummary
    {
        $repo = $package->getRepositoryUrl();
        if (!$repo) {
            return null;
        }

        try {
            $repoName = $this->client->extractRepoName($repo);
            if (!$repoName) {
                return null;
            }

            $data = $this->client->fetchRepoActivity($repoName);
            if (!$data) {
                return null;
            }

            $stats = $data['activity_stats'] ?? [];
            $repository = $data['repository'] ?? [];

            $lastCommitDate = null;
            if (!empty($stats['last_commit_date'])) {
                $lastCommitDate = new \DateTimeImmutable($stats['last_commit_date']);
            }

            return new ActivitySummary(
                lastCommit: $lastCommitDate,
                contributors: $stats['contributor_count'] ?? 0,
                openIssues: $repository['open_issues_count'] ?? 0,
                lastRelease: $stats['last_release_date'] ?? null,
            );
        } catch (\Exception) {
            return null;
        }
    }
}
