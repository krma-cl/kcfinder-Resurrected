<?php

declare(strict_types=1);

use KCFinder\Application\SelectorOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SelectorOptionsTest extends TestCase
{
    public function testSelectorIsDisabledByDefault(): void
    {
        self::assertSame(array(
            'enabled' => false,
            'multiple' => false,
            'targetOrigin' => null,
            'error' => null,
        ), SelectorOptions::fromRequest(array(), array(), 'http://localhost:8080')->toArray());
    }

    public function testSameOriginSelectorCanEnableMultipleSelection(): void
    {
        self::assertSame(array(
            'enabled' => true,
            'multiple' => true,
            'targetOrigin' => 'http://localhost:8080',
            'error' => null,
        ), SelectorOptions::fromRequest(
            array('selector' => 'v1', 'selectorMultiple' => 'true'),
            array(),
            'http://localhost:8080'
        )->toArray());
    }

    public function testExactConfiguredCrossOriginIsAllowed(): void
    {
        $options = SelectorOptions::fromRequest(
            array('selector' => '1', 'selectorOrigin' => 'https://app.example.cl:8443'),
            array('https://app.example.cl:8443'),
            'https://files.example.cl'
        );

        self::assertTrue($options->enabled);
        self::assertSame('https://app.example.cl:8443', $options->targetOrigin);
    }

    #[DataProvider('originProvider')]
    public function testOriginNormalizationPreservesPortsAndIpv6(string $origin): void
    {
        $options = SelectorOptions::fromRequest(array('selector' => '1'), array(), $origin);

        self::assertTrue($options->enabled);
        self::assertSame($origin, $options->targetOrigin);
    }

    public static function originProvider(): array
    {
        return array(
            'localhost with port' => array('http://localhost:8080'),
            'domain with port' => array('https://files.example.cl:8443'),
            'IPv6 with port' => array('http://[::1]:8080'),
        );
    }

    #[DataProvider('invalidOriginProvider')]
    public function testUnsafeOrUnlistedTargetOriginDisablesSelector(mixed $origin): void
    {
        $options = SelectorOptions::fromRequest(
            array('selector' => '1', 'selectorOrigin' => $origin),
            array('*', 'https://allowed.example.cl'),
            'https://files.example.cl'
        );

        self::assertFalse($options->enabled);
        self::assertNotNull($options->error);
    }

    public static function invalidOriginProvider(): array
    {
        return array(
            'wildcard' => array('*'),
            'unlisted' => array('https://other.example.cl'),
            'path' => array('https://allowed.example.cl/path'),
            'credentials' => array('https://user:pass@allowed.example.cl'),
            'array input' => array(array('https://allowed.example.cl')),
        );
    }
}
