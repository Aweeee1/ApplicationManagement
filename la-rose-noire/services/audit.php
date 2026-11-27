<?php
// Writes one row to audit_logs. $user overrides the signed-in user (e.g. at login/register).
function audit(string $action, string $details = '', ?array $user = null): void {
    $u = $user ?? ($_SESSION['user'] ?? null);
    db()->prepare('INSERT INTO audit_logs (user_id, user_name, action, details, ip) VALUES (?,?,?,?,?)')
        ->execute([$u['id'] ?? null, $u['name'] ?? 'Guest', $action, mb_substr($details, 0, 255), $_SERVER['REMOTE_ADDR'] ?? '']);
}
