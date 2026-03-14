<?php
/**
 * Authentication helpers – session management and access control.
 */

const SESSION_KEY = 'admin_user_id';
const CSRF_KEY    = 'csrf_token';

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
        ]);
    }
}

function is_logged_in(): bool
{
    start_session();
    return !empty($_SESSION[SESSION_KEY]);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

function login_user(int $userId): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION[SESSION_KEY] = $userId;
    regenerate_csrf();
}

function logout_user(): void
{
    start_session();
    $_SESSION = [];
    session_destroy();
}

// ---------------------------------------------------------------------------
// CSRF helpers
// ---------------------------------------------------------------------------

function get_csrf_token(): string
{
    start_session();
    if (empty($_SESSION[CSRF_KEY])) {
        regenerate_csrf();
    }
    return $_SESSION[CSRF_KEY];
}

function regenerate_csrf(): void
{
    $_SESSION[CSRF_KEY] = bin2hex(random_bytes(32));
}

function verify_csrf(string $token): bool
{
    start_session();
    return !empty($_SESSION[CSRF_KEY]) && hash_equals($_SESSION[CSRF_KEY], $token);
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(get_csrf_token()) . '">';
}

// ---------------------------------------------------------------------------
// Misc helpers
// ---------------------------------------------------------------------------

/**
 * Returns all settings as a key => value array.
 */
function get_settings(): array
{
    $rows = get_db()->query("SELECT key, value FROM settings")->fetchAll();
    $out  = [];
    foreach ($rows as $row) {
        $out[$row['key']] = $row['value'];
    }
    return $out;
}

/**
 * Escapes output for safe HTML display.
 */
function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
