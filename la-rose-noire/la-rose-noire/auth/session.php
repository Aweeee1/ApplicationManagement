<?php
// Include this first on every page/API. Starts the session, enforces the 15-minute idle limit
// and re-checks that the signed-in account still exists and is active.
require_once __DIR__ . '/../connection/config.php';
require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../services/helpers.php';
require_once __DIR__ . '/../services/audit.php';

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');   // refuse session ids the server did not create
ini_set('session.use_only_cookies', '1');
session_name('lrn_session');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => $https, 'path' => '/']);
if (session_status() === PHP_SESSION_NONE) session_start();
security_headers();

function is_admin(): bool { return ($_SESSION['user']['role'] ?? '') === 'admin'; }
function require_login(): void {
    if (!empty($_SESSION['user'])) return;
    if (defined('IS_API')) json_out(['ok' => false, 'message' => 'Please sign in again.'], 401);
    redirect(url('auth/login.php'));
}
function require_admin(): void {
    require_login();
    if (is_admin()) return;
    if (defined('IS_API')) json_out(['ok' => false, 'message' => 'Administrators only.'], 403);
    flash('danger', 'Only administrators can open that page.');
    redirect(url('pages/dashboard.php'));
}

if (!empty($_SESSION['user'])) {
    $st = db()->prepare('SELECT id, name, email, role, status, prefs FROM users WHERE id = ?');
    $st->execute([$_SESSION['user']['id']]);
    $u = $st->fetch();
    $reason = null;
    if (time() - ($_SESSION['last'] ?? time()) > IDLE_LIMIT) {
        audit('Auto logout', 'Session idle for 15 minutes');
        $reason = 'You were signed out after 15 minutes of inactivity.';
    } elseif (time() - ($_SESSION['started'] ?? time()) > SESSION_MAX_HOURS * 3600) {
        audit('Auto logout', 'Session reached its ' . SESSION_MAX_HOURS . ' hour limit');
        $reason = 'For your security, please sign in again.';
    } elseif (!$u || $u['status'] !== 'active') {
        $reason = 'Your account is no longer active.';
    }
    if ($reason) {
        session_unset();
        session_regenerate_id(true);
        if (defined('IS_API')) json_out(['ok' => false, 'message' => $reason], 401);
        flash('warning', $reason);
        redirect(url('auth/login.php'));
    }
    $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role'], 'prefs' => prefs_clean(json_decode((string)$u['prefs'], true))];
    $_SESSION['last'] = time();
}
