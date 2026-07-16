<?php

declare(strict_types=1);

namespace KCFinder\Infrastructure;

use KCFinder\Contract\OperationObserverInterface;
use KCFinder\Domain\OperationContext;

final class NullOperationObserver implements OperationObserverInterface
{
    public function before(OperationContext $operation): mixed
    {
        return null;
    }

    public function succeeded(OperationContext $operation, mixed $previousState = null): void
    {
    }
}
