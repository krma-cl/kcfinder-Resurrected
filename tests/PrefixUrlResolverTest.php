<?php

declare(strict_types=1);

use KCFinder\Infrastructure\PrefixUrlResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PrefixUrlResolverTest extends TestCase
{
    public static function authorityProvider(): array
    {
        return array(
            'localhost with port' => array('http://localhost:8080/storage', 'http://localhost:8080/storage/documents/report.pdf'),
            'domain with port' => array('https://files.example.test:8443/storage', 'https://files.example.test:8443/storage/documents/report.pdf'),
            'IPv6 loopback with port' => array('http://[::1]:8080/storage', 'http://[::1]:8080/storage/documents/report.pdf'),
        );
    }

    #[DataProvider('authorityProvider')]
    public function testResolverPreservesTheCompleteConfiguredAuthority(string $prefix, string $expected): void
    {
        $resolver = new PrefixUrlResolver($prefix);

        self::assertSame($expected, $resolver->resolve('/documents/report.pdf'));
    }

    public function testResolverEncodesPathSegmentsWithoutChangingThePrefix(): void
    {
        $resolver = new PrefixUrlResolver('/storage/transparencia');

        self::assertSame(
            '/storage/transparencia/informes/informe%20p%C3%BAblico.pdf',
            $resolver->resolve('/informes/informe público.pdf')
        );
    }
}
