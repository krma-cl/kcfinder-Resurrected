<?php

declare(strict_types=1);

namespace KCFinder\Infrastructure;

use InvalidArgumentException;
use KCFinder\Contract\UrlResolverInterface;

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
        if ($logicalPath === '' || $logicalPath[0] !== '/') {
            throw new InvalidArgumentException('The logical path must start with a slash.');
        }

        $segments = explode('/', substr($logicalPath, 1));
        if (in_array('', $segments, true) || in_array('.', $segments, true) || in_array('..', $segments, true)) {
            throw new InvalidArgumentException('The logical path contains an invalid segment.');
        }

        return $this->prefix . '/' . implode('/', array_map('rawurlencode', $segments));
    }
}
