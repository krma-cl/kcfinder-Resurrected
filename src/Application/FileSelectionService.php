<?php

declare(strict_types=1);

namespace KCFinder\Application;

use KCFinder\Contract\AuthorizationInterface;
use KCFinder\Contract\FileMetadataProviderInterface;
use KCFinder\Domain\FileDescriptor;
use KCFinder\Domain\LogicalPath;
use KCFinder\Exception\AuthorizationDenied;

final class FileSelectionService
{
    public const OPERATION = 'select';

    public function __construct(
        private readonly FileMetadataProviderInterface $metadata,
        private readonly AuthorizationInterface $authorization
    ) {
    }

    public function select(string $path): FileDescriptor
    {
        $logicalPath = LogicalPath::fromString($path)->value();
        if (!$this->authorization->can(self::OPERATION, $logicalPath)) {
            throw new AuthorizationDenied();
        }

        return $this->metadata->metadata($logicalPath);
    }
}
