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

namespace PackApi\Bridge\JsDelivr;

use PackApi\Model\ContentOverview;
use PackApi\Model\File;
use PackApi\Package\NpmPackage;
use PackApi\Package\Package;
use PackApi\Package\SwiftPackage;
use PackApi\Provider\ContentProviderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class JsDelivrContentProvider implements ContentProviderInterface
{
    public function __construct(private JsDelivrApiClient $client)
    {
    }

    public function supports(Package $package): bool
    {
        return $package instanceof NpmPackage || $package instanceof SwiftPackage;
    }

    public function getContentOverview(Package $package): ?ContentOverview
    {
        $filesData = match (true) {
            $package instanceof NpmPackage => $this->client->fetchFileList('npm/'.$package->getName()),
            $package instanceof SwiftPackage => $this->client->fetchFileList("gh/{$package->getOwner()}/{$package->getRepository()}"),
            default => null,
        };
        if (!$filesData || empty($filesData['files'])) {
            return null;
        }
        $files = array_map(function ($f) {
            return new File(
                $f['name'],
                $f['size'] ?? 0,
                false,
                isset($f['time']) ? new \DateTimeImmutable($f['time']) : null
            );
        }, $filesData['files']);
        $fileCount = count($files);
        $totalSize = array_sum(array_map(fn ($f) => $f->size, $files));
        $hasReadme = (bool) array_filter($files, fn ($f) => false !== stripos($f->path, 'readme'));
        $hasLicense = (bool) array_filter($files, fn ($f) => false !== stripos($f->path, 'license'));
        $hasTests = (bool) array_filter($files, fn ($f) => false !== stripos($f->path, 'test'));
        $hasGitattributes = (bool) array_filter($files, fn ($f) => '.gitattributes' === $f->path);
        $hasGitignore = (bool) array_filter($files, fn ($f) => '.gitignore' === $f->path);
        $ignoredFiles = array_values(array_filter(
            array_map(fn ($f) => $f->path, $files),
            fn ($name) => preg_match('/(example|demo|sample|docs|test)/i', $name)
        ));

        return new ContentOverview(
            $fileCount,
            $totalSize,
            $hasReadme,
            $hasLicense,
            $hasTests,
            $ignoredFiles,
            $files,
            $hasGitattributes,
            $hasGitignore
        );
    }
}
