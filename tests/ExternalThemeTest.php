<?php

declare(strict_types=1);

namespace KCFinder\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class ExternalThemeTest extends TestCase
{
    public function testTrustedExternalThemeRootResolvesWithoutCopyingIntoCore(): void
    {
        require_once dirname(__DIR__) . '/core/autoload.php';
        $root = sys_get_temp_dir() . '/kcfinder-theme-' . bin2hex(random_bytes(4));
        mkdir($root . '/img/files/big', 0777, true);
        file_put_contents($root . '/img/files/big/_.png', 'theme-icon');

        $reflection = new ReflectionClass(\kcfinder\uploader::class);
        $uploader = $reflection->newInstanceWithoutConstructor();
        $config = new ReflectionProperty(\kcfinder\uploader::class, 'config');
        $config->setValue($uploader, array(
            'theme' => 'bootstrap5',
            '_themeRoots' => array('bootstrap5' => $root),
        ));
        $themeFile = new ReflectionMethod(\kcfinder\uploader::class, 'themeFile');

        self::assertSame(
            realpath($root . '/img/files/big/_.png'),
            realpath((string) $themeFile->invoke($uploader, 'img/files/big/_.png'))
        );
        self::assertFalse($themeFile->invoke($uploader, '../conf/config.php'));
    }
}
