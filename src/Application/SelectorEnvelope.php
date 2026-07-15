<?php

declare(strict_types=1);

namespace KCFinder\Application;

use InvalidArgumentException;
use JsonSerializable;
use KCFinder\Domain\FileDescriptor;

/** @implements JsonSerializable<array<string, mixed>> */
final readonly class SelectorEnvelope implements JsonSerializable
{
    /** @param array<int, FileDescriptor> $files */
    private function __construct(
        private string $event,
        private ?FileDescriptor $file,
        private array $files
    ) {
    }

    public static function single(FileDescriptor $file): self
    {
        return new self('kcfinder:file-selected', $file, array());
    }

    /** @param array<int, FileDescriptor> $files */
    public static function multiple(array $files): self
    {
        if ($files === []) {
            throw new InvalidArgumentException('At least one selected file is required.');
        }
        foreach ($files as $file) {
            if (!$file instanceof FileDescriptor) {
                throw new InvalidArgumentException('Every selected item must be a file descriptor.');
            }
        }

        return new self('kcfinder:files-selected', null, array_values($files));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = array('event' => $this->event, 'version' => 1);
        if ($this->file !== null) {
            $data['file'] = $this->file->toArray();
        } else {
            $data['files'] = array_map(
                static fn (FileDescriptor $file): array => $file->toArray(),
                $this->files
            );
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
