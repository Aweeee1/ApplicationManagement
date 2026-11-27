<?php
// User management (administrators only): create, update (role/status/password), delete.
define('IS_API', true);
require __DIR__ . '/../auth/session.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);
if (!csrf_ok()) json_out(['ok' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);

$pdo = db();
$me = (int)$_SESSION['user']['id'];
$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

function fail(string $m, int $c = 422) { json_out(['ok' => false, 'message' => $m], $c); }
function other_active_admins(PDO $pdo, int $exceptId): int {
    $st = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active' AND id <> ?");
    $st->execute([$exceptId]);
    return (int)$st->fetchColumn();
}
function find_user(PDO $pdo, int $id): array {
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: fail('User not found.', 404);
}

if ($action === 'delete') {
    if ($id === $me) fail('You cannot delete your own account.');
    $u = find_user($pdo, $id);
    if ($u['role'] === 'admin' && $u['status'] === 'active' && other_active_admins($pdo, $id) === 0) fail('At least one active administrator is required.');
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    audit('Deleted user', "#$id {$u['email']}");
    flash('success', 'User deleted.');
    json_out(['ok' => true]);
}

if (!in_array($action, ['create', 'update'], true)) fail('Unknown action.', 400);

$name = trim($_POST['name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$role = $_POST['role'] ?? 'staff';
$status = $_POST['status'] ?? 'active';
$pw = $_POST['password'] ?? '';
$err = [];
if ($name === '' || mb_strlen($name) > 100) $err[] = 'Enter a name (up to 100 characters).';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err[] = 'Enter a valid email address.';
if (!in_array($role, ['admin', 'staff'], true)) $err[] = 'Choose a valid role.';
if (!in_array($status, ['active', 'disabled', 'pending'], true)) $err[] = 'Choose a valid status.';
if ($action === 'create' || $pw !== '') $err = array_merge($err, password_problems($pw, $email, $name));
if ($err) fail(implode(' ', $err));

$dup = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
$dup->execute([$email, $action === 'update' ? $id : 0]);
if ($dup->fetch()) fail('Another account already uses this email.');

if ($action === 'create') {
    $pdo->prepare('INSERT INTO users (name,email,password_hash,role,status) VALUES (?,?,?,?,?)')
        ->execute([$name, $email, hash_pw($pw), $role, $status]);
    audit('Created user', '#' . $pdo->lastInsertId() . " $email ($role)");
    flash('success', 'User created.');
} else {
    $u = find_user($pdo, $id);
    if ($id === $me && ($role !== 'admin' || $status !== 'active')) fail('You cannot remove your own admin access or disable yourself.');
    $losesAdmin = $u['role'] === 'admin' && $u['status'] === 'active' && ($role !== 'admin' || $status !== 'active');
    if ($losesAdmin && other_active_admins($pdo, $id) === 0) fail('At least one active administrator is required.');
    if ($pw !== '') {
        $pdo->prepare('UPDATE users SET name=?, email=?, role=?, status=?, password_hash=? WHERE id=?')
            ->execute([$name, $email, $role, $status, hash_pw($pw), $id]);
    } else {
        $pdo->prepare('UPDATE users SET name=?, email=?, role=?, status=? WHERE id=?')->execute([$name, $email, $role, $status, $id]);
    }
    audit('Updated user', "#$id $email ($role, $status)" . ($pw !== '' ? ' + password reset' : ''));
    flash('success', 'User updated.');
}
json_out(['ok' => true]);
