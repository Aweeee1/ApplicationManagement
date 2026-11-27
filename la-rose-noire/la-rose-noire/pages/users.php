<?php
require __DIR__ . '/../auth/session.php';
require_admin();
$q = trim($_GET['q'] ?? '');
$fs = in_array($_GET['status'] ?? '', ['active', 'disabled', 'pending'], true) ? $_GET['status'] : '';
$w = []; $pr = [];
if ($q !== '') { $w[] = '(name LIKE ? OR email LIKE ?)'; array_push($pr, "%$q%", "%$q%"); }
if ($fs !== '') { $w[] = 'status = ?'; $pr[] = $fs; }
$st = db()->prepare('SELECT id, name, email, role, status, created_at FROM users' . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . " ORDER BY status = 'pending' DESC, created_at DESC");
$st->execute($pr);
$users = $st->fetchAll();
$me = (int)$_SESSION['user']['id'];
$title = 'Users';
include __DIR__ . '/../components/header.php';
$api = url('api/users.php');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <div><span class="eyebrow">Administration</span><h1 class="h2 brand-title mb-0">Users</h1></div>
  <div class="d-flex gap-2">
    <form class="d-flex gap-2" method="get"><input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search users" aria-label="Search users"><button class="btn btn-outline-primary"><i class="bi bi-search"></i> Search</button></form>
    <button class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#userModal"><i class="bi bi-plus-lg"></i> New user</button>
  </div>
</div>

<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
  <thead><tr><th>Name</th><th class="d-none d-md-table-cell">Email</th><th>Role</th><th>Status</th><th class="d-none d-lg-table-cell">Joined</th><th class="text-end">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><span class="fw-medium"><?= e($u['name']) ?></span><?= (int)$u['id'] === $me ? ' <small class="text-body-secondary">(you)</small>' : '' ?><small class="d-md-none d-block text-body-secondary"><?= e($u['email']) ?></small></td>
      <td class="d-none d-md-table-cell"><?= e($u['email']) ?></td>
      <td><?= badge($u['role']) ?></td><td><?= badge($u['status']) ?></td>
      <td class="d-none d-lg-table-cell small text-body-secondary"><?= e(substr($u['created_at'], 0, 10)) ?></td>
      <td class="text-end text-nowrap">
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#userModal" data-json="<?= e(json_encode($u)) ?>"><i class="bi bi-pencil"></i> <?= $u['status'] === 'pending' ? 'Review' : 'Edit' ?></button>
        <?php if ((int)$u['id'] !== $me): ?><button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delModal" data-id="<?= $u['id'] ?>" data-name="<?= e($u['name']) ?>"><i class="bi bi-trash3"></i> Delete</button><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$users): ?><tr><td colspan="6" class="text-center text-body-secondary py-5">No users found.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>

<div class="modal fade" id="userModal" data-crud="form" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered">
  <form class="modal-content" data-api="<?= $api ?>">
    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="create"><input type="hidden" name="id" value="">
    <div class="modal-header"><h2 class="modal-title h5" data-new="New user" data-edit="Edit user"></h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body"><div class="row g-3">
      <div class="col-12"><label class="form-label" for="usr-name">Full name <span class="req">*</span></label><input class="form-control" id="usr-name" name="name" required maxlength="100"></div>
      <div class="col-12"><label class="form-label" for="usr-email">Email <span class="req">*</span></label><input class="form-control" type="email" id="usr-email" name="email" required></div>
      <div class="col-6"><label class="form-label" for="usr-role">Role</label><select class="form-select" id="usr-role" name="role"><option value="staff">Staff</option><option value="admin">Admin</option></select></div>
      <div class="col-6"><label class="form-label" for="usr-status">Status</label><select class="form-select" id="usr-status" name="status"><option value="active">Active</option><option value="pending">Waiting for approval</option><option value="disabled">Disabled</option></select></div>
      <div class="col-12"><label class="form-label" for="usr-password">Password</label><input class="form-control" type="password" id="usr-password" name="password" minlength="10" maxlength="72" autocomplete="new-password">
        <div class="form-text js-pw-hint" data-new="At least 10 characters. A short sentence works well." data-edit="Leave blank to keep the current password."></div></div>
      <div class="col-12"><div class="alert alert-danger form-msg d-none mb-0"></div></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save user</button></div>
  </form></div></div>

<div class="modal fade" id="delModal" data-crud="delete" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered">
  <form class="modal-content" data-api="<?= $api ?>">
    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="">
    <div class="modal-header"><h2 class="modal-title h5">Delete user</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">Delete the account for <strong class="js-name"></strong>? They will no longer be able to sign in.<div class="alert alert-danger form-msg d-none mt-3 mb-0"></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep user</button><button class="btn btn-danger" type="submit">Delete user</button></div>
  </form></div></div>
<?php include __DIR__ . '/../components/footer.php'; ?>
