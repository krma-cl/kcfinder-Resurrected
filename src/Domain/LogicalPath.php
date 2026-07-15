<?php

declare(strict_types=1);

namespace KCFinder\Domain;

use InvalidArgumentException;
use Stringable;

final readonly class LogicalPath implements Stringable
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $path): self
    {
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\')) {
            throw new InvalidArgumentException('The logical path is invalid.');
        }

        $path = '/' . ltrim($path, '/');
        $segments = explode('/', substr($path, 1));
        if (in_array('', $segments, true) || in_array('.', $segments, true) || in_array('..', $segments, true)) {
            throw new InvalidArgumentException('The logical path contains an invalid segment.');
        }

        return new self($path);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
