<?php

declare(strict_types=1);

namespace KCFinder\Domain;

use InvalidArgumentException;
use JsonSerializable;

final readonly class OperationContext implements JsonSerializable
{
    public const RESOURCE_FILE = 'file';
    public const RESOURCE_DIRECTORY = 'directory';

    private const OPERATIONS = array(
        'upload',
        'edit',
        'move',
        'rename',
        'delete',
        'create_directory',
    );

    public string $path;
    public ?string $targetPath;

    public function __construct(
        public string $operation,
        string $path,
        ?string $targetPath = null,
        public string $resource = self::RESOURCE_FILE
    ) {
        if (!in_array($operation, self::OPERATIONS, true)) {
            throw new InvalidArgumentException('The observed operation is not supported.');
        }
        if (!in_array($resource, array(self::RESOURCE_FILE, self::RESOURCE_DIRECTORY), true)) {
            throw new InvalidArgumentException('The observed resource type is not supported.');
        }
        if ($resource === self::RESOURCE_DIRECTORY && $operation !== 'create_directory') {
            throw new InvalidArgumentException('Only directory creation is currently observable.');
        }
        if (in_array($operation, array('move', 'rename'), true) !== ($targetPath !== null)) {
            throw new InvalidArgumentException('Move and rename operations require a target path only.');
        }
        $this->path = LogicalPath::fromString($path)->value();
        $this->targetPath = $targetPath === null ? null : LogicalPath::fromString($targetPath)->value();
    }

    public function resultingPath(): string
    {
        return $this->targetPath ?? $this->path;
    }

    /** @return array{operation: string, resource: string, path: string, targetPath: ?string} */
    public function toArray(): array
    {
        return array(
            'operation' => $this->operation,
            'resource' => $this->resource,
            'path' => $this->path,
            'targetPath' => $this->targetPath,
        );
    }

    /** @return array{operation: string, resource: string, path: string, targetPath: ?string} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
