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

use PackApi\Exception\ValidationException;
use PackApi\Model\Metadata;
use PackApi\Package\Package;
use PackApi\Provider\MetadataProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class GitHubMetadataProvider implements MetadataProviderInterface
{
    public function __construct(
        private readonly GitHubApiClient $client,
    ) {
    }

    public function supports(Package $package): bool
    {
        $repository = $package->getRepositoryUrl();
        if (!$repository) {
            return false;
        }

        return str_contains($repository, 'github.com');
    }

    public function getMetadata(Package $package): ?Metadata
    {
        $repository = $package->getRepositoryUrl();
        if (!$repository) {
            return null;
        }

        try {
            $repoName = $this->client->extractRepoName($repository);
            if (!$repoName) {
                return null;
            }

            $repoData = $this->client->fetchRepoMetadata($repoName);
            if (!$repoData) {
                return null;
            }

            return new Metadata(
                name: $repoData['name'] ?? $package->getName(),
                description: $repoData['description'] ?? null,
                license: $repoData['license']['name'] ?? null,
                repository: $repoData['html_url'] ?? $repository,
            );
        } catch (ValidationException) {
            return null;
        }
    }
}
