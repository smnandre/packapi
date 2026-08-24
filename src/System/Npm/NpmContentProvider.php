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

namespace PackApi\System\Npm;

use PackApi\Bridge\Npm\NpmApiClient;
use PackApi\Model\ContentOverview;
use PackApi\Package\NpmPackage;
use PackApi\Package\Package;
use PackApi\Provider\ContentProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class NpmContentProvider implements ContentProviderInterface
{
    public function __construct(private readonly NpmApiClient $client)
    {
    }

    public function supports(Package $package): bool
    {
        return $package instanceof NpmPackage;
    }

    public function getContentOverview(Package $package): ?ContentOverview
    {
        $data = $this->client->fetchPackageInfo($package->getName());

        if (null === $data) {
            return null;
        }

        $files = [];
        $fileCount = 0;
        $totalSize = 0;
        $hasReadme = false;
        $hasLicense = false;
        $hasTests = false;
        $hasGitignore = false;
        $hasGitattributes = false;

        if (isset($data['dist']) && isset($data['dist']['fileCount'])) {
            $fileCount = (int) $data['dist']['fileCount'];
        }

        if (isset($data['dist']) && isset($data['dist']['unpackedSize'])) {
            $totalSize = (int) $data['dist']['unpackedSize'];
        }

        if (isset($data['readme']) && !empty($data['readme'])) {
            $hasReadme = true;
        }

        if (isset($data['license']) && !empty($data['license'])) {
            $hasLicense = true;
        }

        if (isset($data['scripts']) && is_array($data['scripts'])) {
            $hasTests = isset($data['scripts']['test'])
                       || isset($data['scripts']['jest'])
                       || isset($data['scripts']['mocha'])
                       || isset($data['scripts']['jasmine'])
                       || isset($data['scripts']['karma'])
                       || isset($data['scripts']['ava']);
        }

        if (!$hasTests && isset($data['devDependencies']) && is_array($data['devDependencies'])) {
            $testFrameworks = ['jest', 'mocha', 'jasmine', 'karma', 'ava', 'tape', 'tap'];
            foreach ($testFrameworks as $framework) {
                if (isset($data['devDependencies'][$framework])) {
                    $hasTests = true;
                    break;
                }
            }
        }

        // NPM packages often include .gitignore and .gitattributes in their repository
        // We can infer their presence from the repository structure or dist info
        // For now, we'll assume common practices but this could be enhanced with tarball analysis

        return new ContentOverview(
            fileCount: $fileCount,
            totalSize: $totalSize,
            hasReadme: $hasReadme,
            hasLicense: $hasLicense,
            hasTests: $hasTests,
            ignoredFiles: [],
            files: $files,
            hasGitattributes: $hasGitattributes,
            hasGitignore: $hasGitignore
        );
    }
}
