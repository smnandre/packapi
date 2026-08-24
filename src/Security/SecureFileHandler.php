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

namespace PackApi\Security;

use PackApi\Exception\ValidationException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class SecureFileHandler implements SecureFileHandlerInterface
{
    public const int MAX_FILE_SIZE = 104857600; // 100MB
    public const array ALLOWED_EXTENSIONS = ['tar', 'gz', 'zip', 'json', 'txt', 'md'];
    public const int TIMEOUT = 30;

    private readonly string $tempDir;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        ?string $tempDir = null,
    ) {
        $this->tempDir = $tempDir ?? sys_get_temp_dir();
    }

    public function downloadSafely(string $url, int $maxSize = self::MAX_FILE_SIZE): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new ValidationException("Invalid URL provided: {$url}");
        }

        $tmpDir = $this->createSecureTempDir();
        $tmpFile = $tmpDir.'/download_'.uniqid('', true);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => self::TIMEOUT,
                'max_duration' => self::TIMEOUT,
                'headers' => [
                    'User-Agent' => 'PackApi/1.0 Security Scanner',
                ],
            ]);

            $contentType = $response->getHeaders()['content-type'][0] ?? '';
            if (!$this->isAllowedContentType($contentType)) {
                throw new ValidationException("Disallowed content type: {$contentType}");
            }

            $handle = fopen($tmpFile, 'w');
            if (!$handle) {
                throw new ValidationException('Cannot create temporary file');
            }

            $downloadedSize = 0;
            foreach ($this->httpClient->stream($response) as $chunk) {
                $content = $chunk->getContent();
                $downloadedSize += strlen($content);

                if ($downloadedSize > $maxSize) {
                    fclose($handle);
                    throw new ValidationException("File size exceeds maximum allowed size of {$maxSize} bytes");
                }

                fwrite($handle, $content);
            }
            fclose($handle);

            return $tmpFile;
        } catch (TransportExceptionInterface $e) {
            $this->cleanup($tmpDir);
            throw new ValidationException('Network error downloading file: '.$e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            $this->cleanup($tmpDir);
            throw $e;
        }
    }

    public function extractSafely(string $archivePath, string $destination): array
    {
        if (!file_exists($archivePath)) {
            throw new ValidationException("Archive file does not exist: {$archivePath}");
        }

        $fileSize = filesize($archivePath);
        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new ValidationException("Archive size {$fileSize} exceeds maximum allowed size");
        }

        if (!is_dir($destination)) {
            if (!mkdir($destination, 0755, true) && !is_dir($destination)) {
                throw new ValidationException("Cannot create destination directory: {$destination}");
            }
        }

        $extractedFiles = [];

        try {
            if (str_ends_with($archivePath, '.tar.gz') || str_ends_with($archivePath, '.tgz')) {
                $phar = new \PharData($archivePath);
                $phar->decompress();
                $tarPath = str_replace(['.gz', '.tgz'], ['.tar', '.tar'], $archivePath);
                $pharTar = new \PharData($tarPath);
                $extractedFiles = $this->extractPharSafely($pharTar, $destination);
                unlink($tarPath);
            } elseif (str_ends_with($archivePath, '.tar')) {
                $phar = new \PharData($archivePath);
                $extractedFiles = $this->extractPharSafely($phar, $destination);
            } elseif (str_ends_with($archivePath, '.zip')) {
                $zip = new \ZipArchive();
                if (true === $zip->open($archivePath)) {
                    $extractedFiles = $this->extractZipSafely($zip, $destination);
                    $zip->close();
                } else {
                    throw new ValidationException('Cannot open ZIP archive');
                }
            } else {
                throw new ValidationException('Unsupported archive format');
            }

            return $extractedFiles;
        } catch (\Exception $e) {
            $this->cleanup($destination);
            throw new ValidationException('Error extracting archive: '.$e->getMessage(), 0, $e);
        }
    }

    public function validatePath(string $path): bool
    {
        $normalizedPath = realpath($path) ?: $path;

        $dangerousPatterns = [
            '..',
            '/..',
            '../',
            '\\..\\',
            '../',
            '..\\',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (str_contains($path, $pattern) || str_contains($normalizedPath, $pattern)) {
                return false;
            }
        }

        return true;
    }

    private function createSecureTempDir(): string
    {
        $tmpDir = $this->tempDir.'/packapi_'.uniqid('', true);
        if (!mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) {
            throw new ValidationException("Cannot create temporary directory: {$tmpDir}");
        }

        return $tmpDir;
    }

    private function isAllowedContentType(string $contentType): bool
    {
        $allowedTypes = [
            'application/gzip',
            'application/x-gzip',
            'application/x-tar',
            'application/x-compressed-tar',
            'application/zip',
            'application/octet-stream',
            'text/plain',
        ];

        return in_array($contentType, $allowedTypes, true) || str_starts_with($contentType, 'application/');
    }

    /**
     * @return list<string>
     */
    private function extractPharSafely(\PharData $phar, string $destination): array
    {
        $extractedFiles = [];
        $maxFiles = 1000; // Prevent zip bombs
        $fileCount = 0;

        foreach ($phar as $file) {
            if (++$fileCount > $maxFiles) {
                throw new ValidationException("Archive contains too many files (limit: {$maxFiles})");
            }

            $filePath = $file->getFilename();

            if (!$this->validatePath($filePath)) {
                throw new ValidationException("Dangerous file path detected: {$filePath}");
            }

            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if ($extension && !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            $extractedFiles[] = $filePath;
        }

        $phar->extractTo($destination, null, true);

        return $extractedFiles;
    }

    /**
     * @return list<string>
     */
    private function extractZipSafely(\ZipArchive $zip, string $destination): array
    {
        $extractedFiles = [];
        $maxFiles = 1000;

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            if ($i > $maxFiles) {
                throw new ValidationException("Archive contains too many files (limit: {$maxFiles})");
            }

            $filePath = $zip->getNameIndex($i);

            if (!$this->validatePath($filePath)) {
                throw new ValidationException("Dangerous file path detected: {$filePath}");
            }

            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if ($extension && !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            $extractedFiles[] = $filePath;
        }

        $zip->extractTo($destination);

        return $extractedFiles;
    }

    private function cleanup(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_dir($path)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileInfo) {
                if ($fileInfo->isDir()) {
                    rmdir($fileInfo->getRealPath());
                } else {
                    unlink($fileInfo->getRealPath());
                }
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }
}
