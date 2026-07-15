<?php

declare(strict_types=1);

namespace KCFinder\Application;

final readonly class SelectorOptions
{
    private function __construct(
        public bool $enabled,
        public bool $multiple,
        public ?string $targetOrigin,
        public ?string $error
    ) {
    }

    /** @param array<string, mixed> $query @param array<int, mixed> $configuredOrigins */
    public static function fromRequest(array $query, array $configuredOrigins, string $currentOrigin): self
    {
        $mode = $query['selector'] ?? null;
        if (!is_string($mode) || !in_array(strtolower($mode), array('1', 'v1'), true)) {
            return new self(false, false, null, null);
        }

        $currentOrigin = self::normalizeOrigin($currentOrigin);
        if ($currentOrigin === null) {
            return new self(false, false, null, 'The current selector origin is invalid.');
        }

        $allowed = array($currentOrigin);
        foreach ($configuredOrigins as $origin) {
            if (!is_string($origin)) {
                continue;
            }
            $origin = self::normalizeOrigin($origin);
            if ($origin !== null && !in_array($origin, $allowed, true)) {
                $allowed[] = $origin;
            }
        }

        $requestedOrigin = $query['selectorOrigin'] ?? $currentOrigin;
        $requestedOrigin = is_string($requestedOrigin) ? self::normalizeOrigin($requestedOrigin) : null;
        if ($requestedOrigin === null || !in_array($requestedOrigin, $allowed, true)) {
            return new self(false, false, null, 'The requested selector origin is not allowed.');
        }

        $multiple = isset($query['selectorMultiple']) && is_string($query['selectorMultiple']) &&
            in_array(strtolower($query['selectorMultiple']), array('1', 'true'), true);

        return new self(true, $multiple, $requestedOrigin, null);
    }

    /** @return array{enabled: bool, multiple: bool, targetOrigin: string|null, error: string|null} */
    public function toArray(): array
    {
        return array(
            'enabled' => $this->enabled,
            'multiple' => $this->multiple,
            'targetOrigin' => $this->targetOrigin,
            'error' => $this->error,
        );
    }

    private static function normalizeOrigin(string $origin): ?string
    {
        if ($origin === '*' || preg_match('/[\x00-\x20\x7F]/', $origin)) {
            return null;
        }

        $parts = parse_url($origin);
        if (
            !is_array($parts) ||
            !isset($parts['scheme'], $parts['host']) ||
            !in_array(strtolower($parts['scheme']), array('http', 'https'), true) ||
            isset($parts['user']) ||
            isset($parts['pass']) ||
            isset($parts['query']) ||
            isset($parts['fragment']) ||
            (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/')
        ) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower(trim($parts['host'], '[]'));
        if ($host === '') {
            return null;
        }

        if (str_contains($host, ':')) {
            $host = '[' . $host . ']';
        }

        return $scheme . '://' . $host . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
}
