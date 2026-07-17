<?php

namespace kcfinder;

/** Remove any previously granted KCFinder authorization from the session. */
function revoke_access()
{
    if (!isset($_SESSION) || !is_array($_SESSION)) {
        $_SESSION = array();
    }

    $_SESSION['KCFINDER'] = array('disabled' => true);
    unset($_SESSION['kcCsrf']);
}

/**
 * Make a newly issued double-submit token available during the current
 * request as well as subsequent requests.
 */
function synchronize_csrf_token(string $token): void
{
    if (!isset($_SESSION) || !is_array($_SESSION)) {
        $_SESSION = array();
    }
    if (!isset($_COOKIE) || !is_array($_COOKIE)) {
        $_COOKIE = array();
    }

    $_SESSION['kcCsrf'] = $token;
    $_COOKIE['kcCsrf'] = $token;
}
