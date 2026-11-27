<?php
// Staff CRUD for applications. Create/update: any signed-in user. Delete: administrators only.
define('IS_API', true);
require __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../services/applications.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);
if (!csrf_ok()) json_out(['ok' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);

$pdo = db();
$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($action === 'delete') {
    if (!is_admin()) json_out(['ok' => false, 'message' => 'Only administrators can delete applications.'], 403);
    $st = $pdo->prepare('SELECT applicant_name, cv_file FROM applications WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) json_out(['ok' => false, 'message' => 'Application not found.'], 404);
    $pdo->prepare('DELETE FROM applications WHERE id = ?')->execute([$id]);
    if (!empty($row['cv_file'])) @unlink(cv_path($row['cv_file']));
    audit('Deleted application', "#$id {$row['applicant_name']}");
    flash('success', 'Application deleted.');
    json_out(['ok' => true]);
}

if (!in_array($action, ['create', 'update'], true)) json_out(['ok' => false, 'message' => 'Unknown action.'], 400);
[$f, $err] = clean_application($_POST);
if ($err) json_out(['ok' => false, 'message' => implode(' ', $err)], 422);

if ($action === 'create') {
    $pdo->prepare("INSERT INTO applications (applicant_name,email,phone,position,status,notes,source,created_by) VALUES (?,?,?,?,?,?,'staff',?)")
        ->execute([$f['applicant_name'], $f['email'], $f['phone'], $f['position'], $f['status'], $f['notes'], $_SESSION['user']['id']]);
    audit('Created application', '#' . $pdo->lastInsertId() . " {$f['applicant_name']} ({$f['position']})");
    flash('success', 'Application added.');
} else {
    $chk = $pdo->prepare('SELECT id FROM applications WHERE id = ?');
    $chk->execute([$id]);
    if (!$chk->fetch()) json_out(['ok' => false, 'message' => 'Application not found.'], 404);
    $pdo->prepare('UPDATE applications SET applicant_name=?, email=?, phone=?, position=?, status=?, notes=? WHERE id=?')
        ->execute([$f['applicant_name'], $f['email'], $f['phone'], $f['position'], $f['status'], $f['notes'], $id]);
    audit('Updated application', "#$id {$f['applicant_name']} (status: {$f['status']})");
    flash('success', 'Application updated.');
}
json_out(['ok' => true]);
