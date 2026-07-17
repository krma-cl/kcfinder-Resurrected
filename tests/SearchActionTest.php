<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FilesystemFixture.php';

final class SearchActionTest extends TestCase
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

    public function testSearchReturnsFoldersWhoseNameOrFilesMatch(): void
    {
        $this->fixture->createTypeDirectory('contracts');
        $this->fixture->createTypeDirectory('documents/annual');
        $this->fixture->createTypeDirectory('unrelated');
        $this->fixture->writeTypeFile('documents/annual/contract-summary.pdf', 'pdf');
        $this->fixture->writeTypeFile('unrelated/notes.txt', 'notes');

        $result = $this->fixture->browser()->searchFixture($this->request('CONTRACT'));
        $nodes = $this->flattenTree($result['tree']);

        self::assertSame(2, $result['resultCount']);
        self::assertFalse($result['truncated']);
        self::assertArrayHasKey('files/contracts', $nodes);
        self::assertTrue($nodes['files/contracts']['searchMatch']);
        self::assertSame(0, $nodes['files/contracts']['matchedFiles']);
        self::assertArrayHasKey('files/documents/annual', $nodes);
        self::assertTrue($nodes['files/documents/annual']['searchMatch']);
        self::assertSame(1, $nodes['files/documents/annual']['matchedFiles']);
        self::assertArrayNotHasKey('files/unrelated', $nodes);
    }

    public function testSearchResultLimitIsConfigurable(): void
    {
        $this->fixture->createTypeDirectory('match-one');
        $this->fixture->createTypeDirectory('match-two');

        $result = $this->fixture->browser()->searchFixture(
            $this->request('match'),
            array('maxResults' => 1)
        );

        self::assertSame(1, $result['resultCount']);
        self::assertTrue($result['truncated']);
    }

    public function testTypeRootCanMatchByItsFolderName(): void
    {
        $this->fixture->writeTypeFile('report.txt', 'report');

        $result = $this->fixture->browser()->searchFixture($this->request('files'));

        self::assertSame(1, $result['resultCount']);
        self::assertSame('files', $result['tree']['name']);
        self::assertTrue($result['tree']['searchMatch']);
    }

    public function testSearchRejectsShortQueriesAndInvalidCsrfTokens(): void
    {
        $short = $this->fixture->browser()->searchFixture($this->request('a'));
        $invalidCsrf = $this->fixture->browser()->searchFixture(array(
            'csrf_token' => 'wrong-token',
            'query' => 'documents',
        ));

        self::assertSame('Invalid search query.', $short['error']);
        self::assertSame('Invalid or missing CSRF token', $invalidCsrf['error']);
    }

    public function testSearchCanBeDisabledWithoutExposingTheFilesystem(): void
    {
        $this->fixture->writeTypeFile('documents/report.txt', 'report');

        $result = $this->fixture->browser()->searchFixture(
            $this->request('report'),
            array('enabled' => false)
        );

        self::assertSame('Search is not enabled.', $result['error']);
        self::assertStringNotContainsString(
            $this->fixture->root(),
            json_encode($result, JSON_THROW_ON_ERROR)
        );
    }

    private function request(string $query): array
    {
        return array(
            'csrf_token' => 'fixture-token',
            'query' => $query,
        );
    }

    private function flattenTree(array $tree, string $parent = ''): array
    {
        $path = ltrim($parent . '/' . $tree['name'], '/');
        $nodes = array($path => $tree);

        foreach ($tree['dirs'] ?? array() as $child) {
            $nodes += $this->flattenTree($child, $path);
        }

        return $nodes;
    }
}
