<?php

declare(strict_types=1);

namespace KCFinder\Contract;

use KCFinder\Domain\FileDescriptor;

interface FileMetadataProviderInterface
{
    public function metadata(string $logicalPath): FileDescriptor;
}
