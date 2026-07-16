<?php

declare(strict_types=1);

namespace KCFinder\Contract;

use KCFinder\Domain\OperationContext;

interface OperationObserverInterface
{
    public function before(OperationContext $operation): mixed;

    public function succeeded(OperationContext $operation, mixed $previousState = null): void;
}
