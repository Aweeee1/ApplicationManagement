<?php
// Public endpoint: anyone can submit an application, with a CV or resume, from the careers page. No login required.
define('IS_API', true);
require __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../services/applications.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);
// A file bigger than the server's post_max_size arrives as an empty request.
if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) json_out(['ok' => false, 'message' => 'That file is too big. Please send a file under ' . MAX_CV_MB . ' MB.'], 413);
if (!csrf_ok()) json_out(['ok' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);
if (!empty($_POST['website'])) json_out(['ok' => true, 'message' => 'Thank you. We received your application.']); // honeypot: bots fill this
if ($w = throttle_wait('apply-ip', client_ip(), 5, 60)) json_out(['ok' => false, 'message' => 'Too many applications from this device. Please try again in ' . wait_text($w) . '.'], 429);
if (time() - ($_SESSION['last_apply'] ?? 0) < 30) json_out(['ok' => false, 'message' => 'Please wait a few seconds before sending another application.'], 429);

[$f, $err] = clean_application($_POST + []);
if ($err) json_out(['ok' => false, 'message' => implode(' ', $err)], 422);

$cv = [null, null];
$up = $_FILES['cv'] ?? null;
if ($up && $up['error'] !== UPLOAD_ERR_NO_FILE) {
    [$stored, $orig, $problem] = store_cv($up);
    if ($problem) json_out(['ok' => false, 'message' => $problem], 422);
    $cv = [$stored, $orig];
} elseif (CV_REQUIRED) {
    json_out(['ok' => false, 'message' => 'Please attach your CV or resume.'], 422);
}

$pdo = db();
try {
    $pdo->prepare("INSERT INTO applications (applicant_name, email, phone, position, status, notes, source, cv_file, cv_name) VALUES (?,?,?,?, 'Pending', ?, 'public', ?, ?)")
        ->execute([$f['applicant_name'], $f['email'], $f['phone'], $f['position'], $f['notes'], $cv[0], $cv[1]]);
} catch (Throwable $e) {
    if ($cv[0]) @unlink(cv_path($cv[0]));   // do not leave an orphaned file behind
    throw $e;
}
audit('Public application', '#' . $pdo->lastInsertId() . " {$f['applicant_name']} ({$f['position']})" . ($cv[0] ? ' + CV' : ''), ['id' => null, 'name' => $f['applicant_name']]);
$_SESSION['last_apply'] = time();
throttle_hit('apply-ip', client_ip());
json_out(['ok' => true, 'message' => 'Thank you. We received your application and CV, and will be in touch.']);
