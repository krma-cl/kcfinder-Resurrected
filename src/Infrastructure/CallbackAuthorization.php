<?php

declare(strict_types=1);

namespace KCFinder\Infrastructure;

use Closure;
use KCFinder\Contract\AuthorizationInterface;

final class CallbackAuthorization implements AuthorizationInterface
{
    /** @var Closure(string, string): bool */
    private Closure $decision;

    /** @param callable(string, string): bool $decision */
    public function __construct(callable $decision)
    {
        $this->decision = Closure::fromCallable($decision);
    }

    public function can(string $operation, string $logicalPath): bool
    {
        return ($this->decision)($operation, $logicalPath);
    }
}
