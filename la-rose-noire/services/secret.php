<?php
// Applicant secret keys. After applying, the applicant is shown a key once. Only its SHA-256 hash is stored
// (so a copy of the database cannot reveal anyone's key). The key works for SECRET_KEY_DAYS days.
const SECRET_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // no 0/O or 1/I, so a handwritten key is easy to read back

function secret_new(): string {   // 16 characters = 80 bits of randomness, shown as ABCD-EFGH-JKLM-NPQR
    $s = '';
    for ($i = 0; $i < 16; $i++) $s .= SECRET_ALPHABET[random_int(0, 31)];
    return implode('-', str_split($s, 4));
}
function secret_normalize(string $k): string { return preg_replace('/[^A-Z2-9]/', '', strtoupper($k)); }   // ignores dashes, spaces and letter case
function secret_hash(string $k): string { return hash('sha256', secret_normalize($k)); }

// Returns [application row or null, 'ok' | 'expired' | 'invalid'].
function secret_find(string $key): array {
    $n = secret_normalize($key);
    if (strlen($n) !== 16) return [null, 'invalid'];
    $st = db()->prepare("SELECT *, (secret_expires_at > NOW()) AS key_valid, DATE_FORMAT(secret_expires_at, '%M %e, %Y, %l:%i %p') AS key_expires FROM applications WHERE secret_hash = ?");
    $st->execute([hash('sha256', $n)]);
    $a = $st->fetch();
    if (!$a) return [null, 'invalid'];
    return [$a, (int)$a['key_valid'] === 1 ? 'ok' : 'expired'];
}

// For the public APIs: returns the application the key belongs to, or stops with a friendly error.
// Wrong guesses are counted per device, so the key cannot be guessed by trying many.
function secret_require(string $key): array {
    if ($w = throttle_wait('key-ip', client_ip(), 10, 15))
        json_out(['ok' => false, 'message' => 'Too many wrong keys. For safety, please try again in ' . wait_text($w) . '.'], 429);
    [$app, $state] = secret_find($key);
    if ($state === 'ok') return $app;
    throttle_hit('key-ip', client_ip());
    if ($state === 'expired') json_out(['ok' => false, 'message' => 'This secret key expired on ' . $app['key_expires'] . '. Keys work for ' . SECRET_KEY_DAYS . ' days after applying. Please contact us if you still need to change something.'], 403);
    json_out(['ok' => false, 'message' => 'That secret key is not right. Check it and try again. A key looks like ABCD-EFGH-JKLM-NPQR.'], 403);
}
