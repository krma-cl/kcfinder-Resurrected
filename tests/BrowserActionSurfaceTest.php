<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/core/autoload.php';

final class BrowserActionSurfaceTest extends TestCase
{
    public function testLegacyBrowserActionsRemainAvailable(): void
    {
        $reflection = new ReflectionClass(kcfinder\browser::class);
        $actions = array();

        foreach ($reflection->getMethods(ReflectionMethod::IS_PROTECTED) as $method) {
            if (str_starts_with($method->getName(), 'act_')) {
                $actions[] = substr($method->getName(), 4);
            }
        }

        sort($actions);

        self::assertSame(array(
            'browser',
            'chDir',
            'cp_cbd',
            'crop',
            'delete',
            'deleteDir',
            'download',
            'downloadClipboard',
            'downloadDir',
            'downloadSelected',
            'dragUrl',
            'editimage',
            'expand',
            'init',
            'mv_cbd',
            'newDir',
            'rename',
            'renameDir',
            'rm_cbd',
            'search',
            'select',
            'thumb',
            'upload',
        ), $actions);
    }
}
