<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FilesystemFixture.php';

final class ModernSelectorActionTest extends TestCase
{
    private FilesystemFixture $fixture;

    protected function setUp(): void
    {
        $this->fixture = new FilesystemFixture();
        $_POST = array();
        $_SESSION = array('kcCsrf' => 'fixture-token');
        $_COOKIE = array('kcCsrf' => 'fixture-token');
    }

    protected function tearDown(): void
    {
        $_POST = array();
        $_SESSION = array();
        $_COOKIE = array();
        $this->fixture->destroy();
    }

    public function testSingleSelectionReturnsServerVerifiedMetadata(): void
    {
        $contents = 'quarterly report';
        $this->fixture->writeTypeFile('documents/report.txt', $contents);

        $result = $this->fixture->browser()->selectFixtureFiles($this->request(array('report.txt')));

        self::assertSame('kcfinder:file-selected', $result['event']);
        self::assertSame(1, $result['version']);
        self::assertSame(array(
            'name' => 'report.txt',
            'path' => '/documents/report.txt',
            'url' => '/storage/files/documents/report.txt',
            'mime' => 'text/plain',
            'size' => strlen($contents),
        ), $result['file']);
    }

    public function testSingleSelectionWorksAtTheTypeRoot(): void
    {
        $this->fixture->writeTypeFile('root.txt', 'root file');
        $request = $this->request(array('root.txt'));
        $request['dir'] = '';

        $result = $this->fixture->browser()->selectFixtureFiles($request);

        self::assertSame('kcfinder:file-selected', $result['event']);
        self::assertSame('/root.txt', $result['file']['path']);
    }

    public function testMultipleSelectionPreservesOrder(): void
    {
        $this->fixture->writeTypeFile('documents/second.txt', '22');
        $this->fixture->writeTypeFile('documents/first.txt', '1');

        $result = $this->fixture->browser()->selectFixtureFiles(
            $this->request(array('second.txt', 'first.txt'), '1')
        );

        self::assertSame('kcfinder:files-selected', $result['event']);
        self::assertSame(array('second.txt', 'first.txt'), array_column($result['files'], 'name'));
    }

    public function testSelectorMustBeEnabled(): void
    {
        $result = $this->fixture->browser()->selectFixtureFiles(
            $this->request(array('report.txt')),
            false
        );

        self::assertSame('selector_disabled', $result['error']['code']);
    }

    public function testInvalidCsrfTokenIsRejected(): void
    {
        $request = $this->request(array('report.txt'));
        $request['csrf_token'] = 'wrong-token';

        $result = $this->fixture->browser()->selectFixtureFiles($request);

        self::assertSame('invalid_csrf', $result['error']['code']);
    }

    public function testMultipleSelectionRequiresExplicitOptIn(): void
    {
        $result = $this->fixture->browser()->selectFixtureFiles(
            $this->request(array('first.txt', 'second.txt'), '1'),
            true,
            false
        );

        self::assertSame('multiple_not_enabled', $result['error']['code']);
    }

    public function testTraversalAndMissingFilesDoNotLeakPhysicalPaths(): void
    {
        $this->fixture->createTypeDirectory('documents');
        $traversal = $this->fixture->browser()->selectFixtureFiles($this->request(array('../secret.txt')));
        $missing = $this->fixture->browser()->selectFixtureFiles($this->request(array('missing.txt')));

        self::assertSame('invalid_file', $traversal['error']['code']);
        self::assertSame('selection_failed', $missing['error']['code']);
        self::assertStringNotContainsString($this->fixture->root(), json_encode($missing, JSON_THROW_ON_ERROR));
    }

    private function request(array $files, string $multiple = '0'): array
    {
        return array(
            'csrf_token' => 'fixture-token',
            'dir' => 'documents',
            'files' => $files,
            'multiple' => $multiple,
        );
    }
}
