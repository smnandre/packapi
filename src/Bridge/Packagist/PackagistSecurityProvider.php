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

namespace PackApi\Bridge\Packagist;

use PackApi\Bridge\GitHub\GitHubApiClient;
use PackApi\Model\SecurityAdvisory;
use PackApi\Package\Package;
use PackApi\Provider\SecurityProviderInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class PackagistSecurityProvider implements SecurityProviderInterface
{
    public function __construct(
        private readonly GitHubApiClient $githubClient,
    ) {
    }

    public function supports(Package $package): bool
    {
        return $package->getIdentifier() && str_contains($package->getIdentifier(), '/');
    }

    /**
     * @return SecurityAdvisory[]
     */
    public function getSecurityAdvisories(Package $package): array
    {
        $repoName = 'FriendsOfPHP/security-advisories';
        $packagePath = $package->getIdentifier();

        $files = $this->githubClient->fetchRepoContents($repoName, $packagePath);

        if (null === $files) {
            return [];
        }

        $advisories = [];

        foreach ($files as $file) {
            if ('file' !== $file['type'] || !str_ends_with($file['name'], '.yaml')) {
                continue;
            }

            $fileContent = $this->githubClient->fetchFileContent($repoName, $file['path']);

            if (null === $fileContent) {
                continue;
            }

            $data = Yaml::parse($fileContent);

            $advisories[] = new SecurityAdvisory(
                $data['cve'] ?? $file['name'],
                $data['title'] ?? 'Security advisory',
                $data['severity'] ?? 'unknown',
                $file['html_url']
            );
        }

        return $advisories;
    }
}
