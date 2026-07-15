<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/core/autoload.php';

final class LegacyUploadJsonContractTest extends TestCase
{
    public function testSuccessfulUploadJsonRetainsLegacyFields(): void
    {
        $result = $this->invokeLegacyCallback(
            'https://example.test/upload/files/document.pdf',
            ''
        );

        self::assertSame(array(
            'uploaded' => 1,
            'url' => 'https://example.test/upload/files/document.pdf',
            'fileName' => 'document.pdf',
        ), $result);
    }

    public function testFailedUploadJsonRetainsLegacyErrorShape(): void
    {
        self::assertSame(array(
            'uploaded' => 0,
            'error' => array(
                'message' => 'Upload rejected',
            ),
        ), $this->invokeLegacyCallback('', 'Upload rejected'));
    }

    private function invokeLegacyCallback(string $url, string $message): array
    {
        $reflection = new ReflectionClass(kcfinder\uploader::class);
        $uploader = $reflection->newInstanceWithoutConstructor();
        $callback = $reflection->getMethod('callBack_json');

        return $callback->invoke($uploader, $url, $message);
    }
}
