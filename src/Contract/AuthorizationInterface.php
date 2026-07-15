<?php

declare(strict_types=1);

namespace KCFinder\Contract;

interface AuthorizationInterface
{
    public function can(string $operation, string $logicalPath): bool;
}
