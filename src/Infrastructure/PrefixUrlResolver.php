<?php

declare(strict_types=1);

namespace KCFinder\Infrastructure;

use InvalidArgumentException;
use KCFinder\Contract\UrlResolverInterface;
use KCFinder\Domain\LogicalPath;

final class PrefixUrlResolver implements UrlResolverInterface
{
    private string $prefix;

    public function __construct(string $prefix)
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $prefix) || str_contains($prefix, '?') || str_contains($prefix, '#')) {
            throw new InvalidArgumentException('The URL prefix is invalid.');
        }

        $this->prefix = rtrim($prefix, '/');
    }

    public function resolve(string $logicalPath): string
    {
        if ($logicalPath === '' || $logicalPath[0] !== '/' || LogicalPath::fromString($logicalPath)->value() !== $logicalPath) {
            throw new InvalidArgumentException('The logical path must start with a slash.');
        }

        $segments = explode('/', substr($logicalPath, 1));
        return $this->prefix . '/' . implode('/', array_map('rawurlencode', $segments));
    }
}
