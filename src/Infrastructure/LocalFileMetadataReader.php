<?php

declare(strict_types=1);

namespace KCFinder\Infrastructure;

use finfo;
use InvalidArgumentException;
use KCFinder\Contract\FileMetadataProviderInterface;
use KCFinder\Contract\UrlResolverInterface;
use KCFinder\Domain\FileDescriptor;
use KCFinder\Domain\LogicalPath;
use RuntimeException;

final class LocalFileMetadataReader implements FileMetadataProviderInterface
{
    private string $root;

    public function __construct(
        string $root,
        private readonly UrlResolverInterface $urlResolver
    ) {
        $realRoot = realpath($root);
        if ($realRoot === false || !is_dir($realRoot)) {
            throw new InvalidArgumentException('The storage root does not exist.');
        }

        $this->root = rtrim($this->normalizePhysicalPath($realRoot), '/');
    }

    public function describe(string $path): FileDescriptor
    {
        return $this->metadata($path);
    }

    public function metadata(string $logicalPath): FileDescriptor
    {
        $logicalPath = LogicalPath::fromString($logicalPath)->value();
        $physicalPath = realpath($this->root . $logicalPath);
        if ($physicalPath === false) {
            throw new RuntimeException('The requested file does not exist.');
        }

        $physicalPath = $this->normalizePhysicalPath($physicalPath);
        if (!str_starts_with($physicalPath, $this->root . '/') || !is_file($physicalPath) || !is_readable($physicalPath)) {
            throw new RuntimeException('The requested file is outside the storage root or is not readable.');
        }

        $size = filesize($physicalPath);
        if ($size === false) {
            throw new RuntimeException('The file size could not be determined.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($physicalPath);
        if (!is_string($mime) || $mime === '') {
            $mime = 'application/octet-stream';
        }

        return new FileDescriptor(
            basename($logicalPath),
            $logicalPath,
            $this->urlResolver->resolve($logicalPath),
            strtolower($mime),
            $size
        );
    }

    private function normalizePhysicalPath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
