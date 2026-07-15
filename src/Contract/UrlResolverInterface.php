<?php

declare(strict_types=1);

namespace KCFinder\Contract;

interface UrlResolverInterface
{
    public function resolve(string $logicalPath): string;
}
