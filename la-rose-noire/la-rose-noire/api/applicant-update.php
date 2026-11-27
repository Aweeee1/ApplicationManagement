<?php
// Public endpoint: an applicant saves changes, proving who they are with their secret key.
// The key decides which application is changed; nothing else in the request can pick a different one.
define('IS_API', true);
require __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../services/applications.php';
require_once __DIR__ . '/../services/secret.php';
require_once __DIR__ . '/../connection/jobs.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);
if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) json_out(['ok' => false, 'message' => 'That file is too big. Please send a file under ' . MAX_CV_MB . ' MB.'], 413);
if (!csrf_ok()) json_out(['ok' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);
$app = secret_require((string)($_POST['secret_key'] ?? ''));   // also rate-limits wrong keys

$roles = array_column(jobs(), 'title');
$roles[] = $app['position'];
[$f, $err] = clean_application([
    'applicant_name' => $_POST['applicant_name'] ?? '',
    'email'          => $_POST['email'] ?? '',
    'phone'          => $_POST['phone'] ?? '',
    'position'       => $_POST['position'] ?? $app['position'],
    'notes'          => $_POST['notes'] ?? '',
    'status'         => $app['status'],      // applicants can never change their own status
]);
if (!in_array($f['position'], $roles, true)) $err[] = 'Choose one of the open roles.';
if ($err) json_out(['ok' => false, 'message' => implode(' ', $err)], 422);

$cvFile = $app['cv_file']; $cvName = $app['cv_name']; $newCv = false;
$up = $_FILES['cv'] ?? null;
if ($up && $up['error'] !== UPLOAD_ERR_NO_FILE) {
    [$stored, $orig, $problem] = store_cv($up);
    if ($problem) json_out(['ok' => false, 'message' => $problem], 422);
    $cvFile = $stored; $cvName = $orig; $newCv = true;
}

$labels = ['applicant_name' => 'name', 'email' => 'email', 'phone' => 'phone', 'position' => 'position', 'notes' => 'about me'];
$changed = [];
foreach ($labels as $k => $label) if ((string)$f[$k] !== (string)$app[$k]) $changed[] = $label;
if ($newCv) $changed[] = 'CV';
if (!$changed) json_out(['ok' => true, 'changed' => false, 'message' => 'Nothing was changed. Edit a detail and save again, or close this page.']);

$pdo = db();
try {
    $pdo->prepare('UPDATE applications SET applicant_name=?, email=?, phone=?, position=?, notes=?, cv_file=?, cv_name=?, self_updated_at=NOW() WHERE id=?')
        ->execute([$f['applicant_name'], $f['email'], $f['phone'], $f['position'], $f['notes'], $cvFile, $cvName, $app['id']]);
} catch (Throwable $e) {
    if ($newCv) @unlink(cv_path($cvFile));   // do not leave an orphaned file behind
    throw $e;
}
if ($newCv && !empty($app['cv_file'])) @unlink(cv_path($app['cv_file']));   // the replaced CV is removed
audit('Applicant updated details', "#{$app['id']} {$f['applicant_name']}: " . implode(', ', $changed), ['id' => null, 'name' => $f['applicant_name']]);
json_out(['ok' => true, 'changed' => true, 'cv_name' => $cvName, 'message' => 'Your changes were saved. Our team can see them now. Your secret key still works until ' . $app['key_expires'] . '.']);
