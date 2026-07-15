<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ResponsiveBrowserSurfaceTest extends TestCase
{
    public function testBrowserTemplateExposesTheFolderDrawerControls(): void
    {
        $template = file_get_contents(dirname(__DIR__) . '/tpl/tpl_browser.php');

        self::assertIsString($template);
        self::assertStringContainsString('id="folderToggle"', $template);
        self::assertStringContainsString('aria-controls="left"', $template);
        self::assertStringContainsString('aria-expanded="false"', $template);
        self::assertStringContainsString('id="foldersBackdrop"', $template);
    }

    public function testResponsiveControllerCoversKeyboardAndFolderSelectionFlows(): void
    {
        $controller = file_get_contents(dirname(__DIR__) . '/js/050.init.js');
        $folders = file_get_contents(dirname(__DIR__) . '/js/090.folders.js');

        self::assertIsString($controller);
        self::assertIsString($folders);
        self::assertStringContainsString('_.openFolders = function', $controller);
        self::assertStringContainsString('_.closeFolders = function', $controller);
        self::assertStringContainsString("event.key === 'Escape'", $controller);
        self::assertStringContainsString("event.key !== 'Tab'", $controller);
        self::assertStringContainsString('_.closeFolders(true);', $folders);
    }

    public function testCoreStylesProvideTouchTargetsAndResponsiveListReduction(): void
    {
        $styles = file_get_contents(dirname(__DIR__) . '/css/000.base.css');

        self::assertIsString($styles);
        self::assertStringContainsString('@media (max-width: 767.98px)', $styles);
        self::assertStringContainsString('min-width: 44px', $styles);
        self::assertStringContainsString('min-height: 44px', $styles);
        self::assertStringContainsString('#files table td.dimensions', $styles);
        self::assertStringContainsString('#files table td.time', $styles);
    }

    public function testUploadOverlayTracksItsToolbarActionAfterReflow(): void
    {
        $toolbar = file_get_contents(dirname(__DIR__) . '/js/060.toolbar.js');
        $controller = file_get_contents(dirname(__DIR__) . '/js/050.init.js');

        self::assertIsString($toolbar);
        self::assertIsString($controller);
        self::assertStringContainsString('_.positionUploadButton = function', $toolbar);
        self::assertStringContainsString('left: btn.get(0).offsetLeft', $toolbar);
        self::assertStringContainsString('_.positionUploadButton();', $controller);
        self::assertMatchesRegularExpression('/\$\(\x27#lang\x27\)\.transForm\(\);\s+_.resize\(\);/', $toolbar);
    }
}
