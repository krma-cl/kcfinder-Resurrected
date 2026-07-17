<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/core/bootstrap.php';
require_once dirname(__DIR__) . '/integration/security.php';

final class CsrfValidationTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = array();
        $_COOKIE = array();
    }

    protected function tearDown(): void
    {
        $_SESSION = array();
        $_COOKIE = array();
    }

    public static function invalidTokenProvider(): array
    {
        return array(
            'missing session token' => array(array(), array(), '', 'CSRF token missing in session'),
            'empty session token' => array(array('kcCsrf' => ''), array(), '', 'CSRF token missing in session'),
            'array session token' => array(array('kcCsrf' => array('token')), array(), '', 'CSRF token missing in session'),
            'missing request token' => array(array('kcCsrf' => 'token'), array('kcCsrf' => 'token'), '', 'CSRF token not provided'),
            'array request token' => array(array('kcCsrf' => 'token'), array('kcCsrf' => 'token'), array('token'), 'CSRF token not provided'),
            'missing cookie token' => array(array('kcCsrf' => 'token'), array(), 'token', 'Invalid or missing CSRF token'),
            'array cookie token' => array(array('kcCsrf' => 'token'), array('kcCsrf' => array('token')), 'token', 'Invalid or missing CSRF token'),
            'different request token' => array(array('kcCsrf' => 'token'), array('kcCsrf' => 'token'), 'other', 'Invalid or missing CSRF token'),
            'different cookie token' => array(array('kcCsrf' => 'token'), array('kcCsrf' => 'other'), 'token', 'Invalid CSRF token'),
        );
    }

    #[DataProvider('invalidTokenProvider')]
    public function testInvalidCsrfStatesRetainTheirCurrentResponse(
        array $session,
        array $cookie,
        mixed $requestToken,
        string $expected
    ): void {
        $_SESSION = $session;
        $_COOKIE = $cookie;

        self::assertSame($expected, validateCSRF($requestToken));
    }

    public function testMatchingSessionCookieAndRequestTokenAreAccepted(): void
    {
        $_SESSION['kcCsrf'] = 'token';
        $_COOKIE['kcCsrf'] = 'token';

        self::assertTrue(validateCSRF('token'));
    }

    public function testNewTokenIsAvailableDuringTheIssuingRequest(): void
    {
        \kcfinder\synchronize_csrf_token('first-request-token');

        self::assertSame('first-request-token', $_SESSION['kcCsrf']);
        self::assertSame('first-request-token', $_COOKIE['kcCsrf']);
        self::assertTrue(validateCSRF('first-request-token'));
    }
}
