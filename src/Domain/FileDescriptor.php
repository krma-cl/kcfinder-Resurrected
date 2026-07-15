<?php

declare(strict_types=1);

namespace KCFinder\Domain;

use InvalidArgumentException;
use JsonSerializable;

/** @implements JsonSerializable<array{name: string, path: string, url: string, mime: string, size: int}> */
final class FileDescriptor implements JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly string $url,
        public readonly string $mime,
        public readonly int $size
    ) {
        if ($name === '' || str_contains($name, "\0") || basename(str_replace('\\', '/', $name)) !== $name) {
            throw new InvalidArgumentException('The file name must not contain a path.');
        }

        if ($path === '' || $path[0] !== '/' || LogicalPath::fromString($path)->value() !== $path) {
            throw new InvalidArgumentException('The logical path must be absolute and use forward slashes.');
        }

        if (basename($path) !== $name) {
            throw new InvalidArgumentException('The file name must match the logical path.');
        }

        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            throw new InvalidArgumentException('The resolved URL is invalid.');
        }

        if (!preg_match('#^[a-z0-9][a-z0-9.+-]*/[a-z0-9][a-z0-9.+-]*$#i', $mime)) {
            throw new InvalidArgumentException('The MIME type is invalid.');
        }

        if ($size < 0) {
            throw new InvalidArgumentException('The file size cannot be negative.');
        }
    }

    /** @return array{name: string, path: string, url: string, mime: string, size: int} */
    public function toArray(): array
    {
        return array(
            'name' => $this->name,
            'path' => $this->path,
            'url' => $this->url,
            'mime' => $this->mime,
            'size' => $this->size,
        );
    }

    /** @return array{name: string, path: string, url: string, mime: string, size: int} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
