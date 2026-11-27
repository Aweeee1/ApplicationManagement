<?php
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $to) { header('Location: ' . $to); exit; }
function flash(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_ok(): bool { return hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? ''); }
function json_out(array $data, int $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
const PREF_ACCENTS = ['Blush' => '#f4b6cc', 'Rose' => '#9f1239', 'Gold' => '#a16207', 'Emerald' => '#047857', 'Teal' => '#0f766e', 'Indigo' => '#4338ca', 'Violet' => '#7e22ce', 'Ocean' => '#0369a1', 'Slate' => '#475569'];
const PREF_CHOICES = ['theme' => ['light', 'dark', 'auto'], 'size' => ['100', '115', '130', '150'], 'font' => ['inter', 'atkinson', 'system', 'serif'],
                      'space' => ['cozy', 'roomy'], 'corners' => ['round', 'square'], 'contrast' => ['0', '1'], 'motion' => ['0', '1']];
// Only listed values are ever accepted, so a saved setting can never inject anything into a page.
function prefs_clean($in): array {
    $o = ['theme' => 'dark', 'size' => '100', 'font' => 'inter', 'space' => 'cozy', 'corners' => 'round', 'contrast' => '0', 'motion' => '0', 'accent' => '#f4b6cc'];
    if (!is_array($in)) return $o;
    foreach (PREF_CHOICES as $k => $ok) if (isset($in[$k]) && in_array((string)$in[$k], $ok, true)) $o[$k] = (string)$in[$k];
    if (isset($in['accent']) && in_array($in['accent'], PREF_ACCENTS, true)) $o['accent'] = $in['accent'];
    return $o;
}
// Text color that stays readable on top of the accent color (light accents need dark text).
function pref_on(string $accent): string { return $accent === '#f4b6cc' ? '#1a0a10' : '#ffffff'; }
function badge(string $status): string {
    $c = ['Pending' => 'secondary', 'Reviewing' => 'info', 'Interview' => 'warning', 'Approved' => 'success', 'Rejected' => 'danger',
          'active' => 'success', 'disabled' => 'secondary', 'pending' => 'warning', 'admin' => 'dark', 'staff' => 'primary'];
    $i = ['Pending' => 'hourglass-split', 'Reviewing' => 'search', 'Interview' => 'chat-dots', 'Approved' => 'check-circle', 'Rejected' => 'x-circle',
          'active' => 'check-circle', 'disabled' => 'slash-circle', 'pending' => 'hourglass-split', 'admin' => 'shield-check', 'staff' => 'person']; // icon + word, so color is never the only clue
    return '<span class="badge text-bg-' . ($c[$status] ?? 'secondary') . '"><i class="bi bi-' . ($i[$status] ?? 'circle') . ' me-1" aria-hidden="true"></i>' . e(ucfirst($status)) . '</span>';
}

// ---------------------------------------------------------------- security helpers
function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? ''; } // never trust X-Forwarded-For unless you run behind your own proxy

function nonce(): string { return $GLOBALS['csp_nonce'] ??= base64_encode(random_bytes(16)); }

// Browser-side protections: no framing, no MIME sniffing, only known script/style sources.
function security_headers(): void {
    if (headers_sent()) return;
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'nonce-" . nonce() . "'; "
        . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; "
        . "img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') header('Strict-Transport-Security: max-age=15552000');
    if (!defined('IS_API')) { header('Cache-Control: no-store'); } // pages with private data are never cached
}

function hash_pw(string $pw): string { return password_hash($pw, PASSWORD_DEFAULT, ['cost' => 12]); }

// Returns a list of friendly problems (empty = password is acceptable).
// Length is what makes passwords strong, so there are no "must contain a symbol" rules; a short sentence works well.
function password_problems(string $pw, string $email = '', string $name = ''): array {
    $p = [];
    if (mb_strlen($pw) < MIN_PASSWORD) $p[] = 'Use at least ' . MIN_PASSWORD . ' characters. A short sentence is easy to remember and hard to guess.';
    if (strlen($pw) > 72) $p[] = 'That password is too long. Please use 72 characters or fewer.';
    $low = mb_strtolower($pw);
    $common = ['password', 'password1', 'password123', '1234567890', '123456789', 'qwertyuiop', 'qwerty123', 'iloveyou', 'welcome123', 'admin12345',
               'letmein123', 'abc1234567', 'larosenoire', 'rosenoire1', '0123456789', 'passw0rd12', 'password12', 'password!23', 'qwertyuiop1', '1q2w3e4r5t', 'iloveyou123', 'welcome1234', 'changeme123', 'administrator', 'letmein1234', 'monkey12345', 'football123', 'sunshine123'];
    if (str_contains($low, 'rosenoire') || str_contains($low, 'password')) $p[] = 'Please do not use the word "password" or the restaurant name in your password.';
    if (in_array($low, $common, true) || preg_match('/^(.)\1+$/u', $pw) || str_contains('01234567890123456789', $low) || str_contains('abcdefghijklmnopqrstuvwxyz', $low))
        $p[] = 'That password is too easy to guess. Try a few unrelated words instead.';
    $local = mb_strtolower(explode('@', $email)[0] ?? '');
    if (mb_strlen($local) >= 4 && str_contains($low, $local)) $p[] = 'Please do not use your email address inside your password.';
    foreach (preg_split('/\s+/', mb_strtolower(trim($name))) ?: [] as $part)
        if (mb_strlen($part) >= 4 && str_contains($low, $part)) { $p[] = 'Please do not use your name inside your password.'; break; }
    return $p;
}

// ---- Rate limiting (stored in the database so it survives new sessions and cookie clearing) ----
function throttle_hit(string $kind, string $ident): void {
    db()->prepare('INSERT INTO login_attempts (kind, ident) VALUES (?, ?)')->execute([$kind, mb_substr($ident, 0, 190)]);
    if (random_int(1, 50) === 1) db()->exec('DELETE FROM login_attempts WHERE created_at < NOW() - INTERVAL 2 DAY');
}
// Seconds until the limit lifts (0 = not limited): $max hits inside the last $minutes minutes.
function throttle_wait(string $kind, string $ident, int $max, int $minutes): int {
    $st = db()->prepare('SELECT UNIX_TIMESTAMP(created_at) + ? - UNIX_TIMESTAMP() AS wait_s FROM login_attempts
                         WHERE kind = ? AND ident = ? AND created_at > NOW() - INTERVAL ? MINUTE
                         ORDER BY id DESC LIMIT 1 OFFSET ' . max(0, $max - 1));
    $st->execute([$minutes * 60, $kind, mb_substr($ident, 0, 190), $minutes]);
    $r = $st->fetchColumn();
    return $r === false ? 0 : max(1, (int)$r);
}
function throttle_count(string $kind, string $ident, int $minutes): int {
    $st = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE kind = ? AND ident = ? AND created_at > NOW() - INTERVAL ? MINUTE');
    $st->execute([$kind, mb_substr($ident, 0, 190), $minutes]);
    return (int)$st->fetchColumn();
}
function throttle_clear(string $kind, string $ident): void {
    db()->prepare('DELETE FROM login_attempts WHERE kind = ? AND ident = ?')->execute([$kind, mb_substr($ident, 0, 190)]);
}
function wait_text(int $s): string { return $s >= 90 ? ceil($s / 60) . ' minutes' : $s . ' seconds'; }

// "7 days ago" -> "1 week ago", for the careers page.
function ago(int $days): string {
    if ($days < 1) return 'today';
    if ($days === 1) return '1 day ago';
    if ($days < 7) return "$days days ago";
    if ($days < 30) { $w = intdiv($days, 7); return $w . ($w === 1 ? ' week' : ' weeks') . ' ago'; }
    $m = intdiv($days, 30); return $m . ($m === 1 ? ' month' : ' months') . ' ago';
}
