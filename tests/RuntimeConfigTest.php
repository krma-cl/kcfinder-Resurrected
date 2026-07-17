<?php

declare(strict_types=1);

namespace KCFinder\Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class RuntimeConfigTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testTrustedHostCanProvideRequestScopedPrivateConfiguration(): void
    {
        $observer = new \stdClass();
        $GLOBALS['KCFINDER_RUNTIME_CONFIG'] = array(
            'disabled' => false,
            'uploadDir' => '/trusted/storage',
            '_operationObserver' => $observer,
        );

        /** @var array<string, mixed> $config */
        $config = require dirname(__DIR__) . '/conf/config.php';

        self::assertFalse($config['disabled']);
        self::assertSame('/trusted/storage', $config['uploadDir']);
        self::assertSame($observer, $config['_operationObserver']);
        self::assertTrue($config['_sessionCsrf']);
    }
}
