
<?php
// Public endpoint: applicants can update their application using their secret key.

define('IS_API', true);

require __DIR__ . '/../auth/session.php';

require_once __DIR__ . '/../services/applications.php';
require_once __DIR__ . '/../services/secret.php';
require_once __DIR__ . '/../services/notifications.php';
require_once __DIR__ . '/../connection/jobs.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

// Detect oversized POST requests.
if (
    empty($_POST) &&
    empty($_FILES) &&
    (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0
) {
    json_out([
        'ok' => false,
        'message' => 'That file is too big. Please send a file under ' . MAX_CV_MB . ' MB.'
    ], 413);
}

// CSRF validation.
if (!csrf_ok()) {
    json_out([
        'ok' => false,
        'message' => 'Your session expired. Refresh the page and try again.'
    ], 419);
}

// Validate secret key.
$app = secret_require(
    (string)($_POST['secret_key'] ?? '')
);

// Allowed positions.
$roles = array_column(jobs(), 'title');
$roles[] = $app['position'];

// Validate submitted information.
[$f, $err] = clean_application([
    'applicant_name' => $_POST['applicant_name'] ?? '',
    'email' => $_POST['email'] ?? '',
    'phone' => $_POST['phone'] ?? '',
    'position' => $_POST['position'] ?? $app['position'],
    'notes' => $_POST['notes'] ?? '',
    'status' => $app['status']
]);

if (!in_array($f['position'], $roles, true)) {
    $err[] = 'Choose one of the open roles.';
}

if ($err) {
    json_out([
        'ok' => false,
        'message' => implode(' ', $err)
    ], 422);
}

// Preserve existing CV unless replaced.
$cvFile = $app['cv_file'];
$cvName = $app['cv_name'];

$newCv = false;

$up = $_FILES['cv'] ?? null;

if ($up && $up['error'] !== UPLOAD_ERR_NO_FILE) {

    [$stored, $orig, $problem] = store_cv($up);

    if ($problem) {
        json_out([
            'ok' => false,
            'message' => $problem
        ], 422);
    }

    $cvFile = $stored;
    $cvName = $orig;
    $newCv = true;

}

// Detect modified fields.
$labels = [
    'applicant_name' => 'name',
    'email' => 'email',
    'phone' => 'phone',
    'position' => 'position',
    'notes' => 'about me'
];

$changed = [];

foreach ($labels as $k => $label) {

    if ((string)$f[$k] !== (string)$app[$k]) {
        $changed[] = $label;
    }

}

if ($newCv) {
    $changed[] = 'CV';
}

// No changes: do not create a notification.
if (!$changed) {

    json_out([
        'ok' => true,
        'changed' => false,
        'message' => 'Nothing was changed. Edit a detail and save again, or close this page.'
    ]);

}

$pdo = db();

try {

    $pdo->prepare("
        UPDATE applications

        SET
            applicant_name = ?,
            email = ?,
            phone = ?,
            position = ?,
            notes = ?,
            cv_file = ?,
            cv_name = ?,
            self_updated_at = NOW()

        WHERE id = ?
    ")->execute([
        $f['applicant_name'],
        $f['email'],
        $f['phone'],
        $f['position'],
        $f['notes'],
        $cvFile,
        $cvName,
        $app['id']
    ]);

} catch (Throwable $e) {

    if ($newCv) {
        @unlink(cv_path($cvFile));
    }

    throw $e;

}

// Delete replaced CV.
if ($newCv && !empty($app['cv_file'])) {
    @unlink(cv_path($app['cv_file']));
}

// Audit log.
audit(
    'Applicant updated details',
    "#{$app['id']} {$f['applicant_name']}: " . implode(', ', $changed),
    ['id' => null, 'name' => $f['applicant_name']]
);

// Create administrator notification.
notify_admins(
    (int)$app['id'],
    'applicant_updated',
    'Applicant updated their application',
    $f['applicant_name'] .
    ' updated: ' . implode(', ', $changed) . '.'
);

// Return success.
json_out([
    'ok' => true,
    'changed' => true,
    'cv_name' => $cvName,
    'message' => 'Your changes were saved. Our team can see them now. Your secret key still works until ' . $app['key_expires'] . '.'
]);
