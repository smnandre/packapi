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

use PackApi\Model\ContentOverview;
use PackApi\Model\File;
use PackApi\Package\Package;
use PackApi\Provider\ContentProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class GitHubContentProvider implements ContentProviderInterface
{
    public function __construct(private GitHubApiClient $client)
    {
    }

    public function supports(Package $package): bool
    {
        $repo = $package->getRepositoryUrl();

        return is_string($repo) && str_contains($repo, 'github.com');
    }

    public function getContentOverview(Package $package): ?ContentOverview
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

            $filesData = $this->client->fetchRepoFiles($repoName);
            if (!$filesData) {
                return null;
            }

            $contents = $filesData['contents'] ?? [];
            $importantFiles = $filesData['important_files'] ?? [];

            $files = [];
            foreach ($contents as $item) {
                $files[] = new File(
                    path: $item['name'] ?? 'unknown',
                    size: $item['size'] ?? 0,
                    isDirectory: ($item['type'] ?? '') === 'dir',
                );
            }

            $fileCount = count($files);
            $totalSize = array_sum(array_map(fn ($f) => $f->getSize(), $files));

            $hasReadme = $filesData['has_readme'] ?? false;
            $hasLicense = $filesData['has_license'] ?? false;
            $hasSecurityPolicy = $filesData['has_security_policy'] ?? false;

            $hasTests = (bool) array_filter($files, fn ($f) => preg_match('/(test|spec)/i', $f->getPath()));

            $hasGitattributes = (bool) array_filter($files, fn ($f) => '.gitattributes' === $f->getPath());
            $hasGitignore = (bool) array_filter($files, fn ($f) => '.gitignore' === $f->getPath());

            $ignoredFiles = array_values(array_filter(
                array_map(fn ($f) => $f->getPath(), $files),
                fn ($name) => preg_match('/(example|demo|sample|docs|test|spec)/i', $name)
            ));

            return new ContentOverview(
                fileCount: $fileCount,
                totalSize: $totalSize,
                hasReadme: $hasReadme,
                hasLicense: $hasLicense,
                hasTests: $hasTests,
                ignoredFiles: $ignoredFiles,
                files: $files,
                hasGitattributes: $hasGitattributes,
                hasGitignore: $hasGitignore
            );
        } catch (\Exception) {
            return null;
        }
    }
}
