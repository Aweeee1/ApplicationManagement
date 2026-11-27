<?php
require __DIR__ . '/../auth/session.php';
require_login();
$q = trim($_GET['q'] ?? '');
$status = in_array($_GET['status'] ?? '', STATUSES, true) ? $_GET['status'] : '';
$source = in_array($_GET['source'] ?? '', ['staff', 'public'], true) ? $_GET['source'] : '';
$where = []; $params = [];
if ($q !== '') { $where[] = '(applicant_name LIKE ? OR email LIKE ? OR position LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
if ($status) { $where[] = 'status = ?'; $params[] = $status; }
if ($source) { $where[] = 'source = ?'; $params[] = $source; }
$st = db()->prepare('SELECT * FROM applications' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC, id DESC LIMIT 500');
$st->execute($params);
$rows = $st->fetchAll();
$title = 'Applications';
include __DIR__ . '/../components/header.php';
$api = url('api/applications.php');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <div><span class="eyebrow">Recruitment</span><h1 class="h2 brand-title mb-0">Applications</h1></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#appModal" id="addBtn"><i class="bi bi-plus-lg"></i> New application</button>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-12 col-md-5"><input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email or position" aria-label="Search"></div>
  <div class="col-6 col-md-2"><select class="form-select" name="status" aria-label="Status"><option value="">All statuses</option>
    <?php foreach (STATUSES as $s): ?><option <?= $s === $status ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
  <div class="col-6 col-md-2"><select class="form-select" name="source" aria-label="Source"><option value="">All sources</option>
    <option value="public" <?= $source === 'public' ? 'selected' : '' ?>>Public page</option><option value="staff" <?= $source === 'staff' ? 'selected' : '' ?>>Added by staff</option></select></div>
  <div class="col-12 col-md-3 d-flex gap-2"><button class="btn btn-outline-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button><a class="btn btn-outline-secondary" href="<?= url('pages/applications.php') ?>">Reset</a></div>
</form>

<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
  <thead><tr><th>Applicant</th><th class="d-none d-md-table-cell">Position</th><th class="d-none d-lg-table-cell">Contact</th><th>Status</th><th class="d-none d-lg-table-cell">Added</th><th class="text-end">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr id="row-<?= $r['id'] ?>">
      <td><span class="fw-medium"><?= e($r['applicant_name']) ?></span><?= $r['source'] === 'public' ? ' <span class="badge text-bg-light border">Web</span>' : '' ?><small class="d-md-none d-block text-body-secondary"><?= e($r['position']) ?></small></td>
      <td class="d-none d-md-table-cell"><?= e($r['position']) ?></td>
      <td class="d-none d-lg-table-cell"><?= e($r['email']) ?><small class="d-block text-body-secondary"><?= e($r['phone']) ?></small></td>
      <td><?= badge($r['status']) ?></td>
      <td class="d-none d-lg-table-cell small text-body-secondary"><?= e(substr($r['created_at'], 0, 10)) ?></td>
      <td class="text-end text-nowrap">
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#appModal" data-json="<?= e(json_encode($r)) ?>"><i class="bi bi-pencil"></i> Edit</button>
        <?php if (is_admin()): ?><button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delModal" data-id="<?= $r['id'] ?>" data-name="<?= e($r['applicant_name']) ?>"><i class="bi bi-trash3"></i> Delete</button><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-body-secondary py-5">No applications match. Try a different search or add a new application.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>

<div class="modal fade" id="appModal" data-crud="form" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
  <form class="modal-content" data-api="<?= $api ?>">
    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="create"><input type="hidden" name="id" value="">
    <div class="modal-header"><h2 class="modal-title h5" data-new="New application" data-edit="Edit application"></h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body"><div class="row g-3">
      <div class="col-md-6"><label class="form-label" for="app-applicant_name">Applicant name <span class="req">*</span></label><input class="form-control" id="app-applicant_name" name="applicant_name" required maxlength="120"></div>
      <div class="col-md-6"><label class="form-label" for="app-position">Position <span class="req">*</span></label><input class="form-control" id="app-position" name="position" list="positions" required maxlength="120">
        <datalist id="positions"><?php foreach (POSITIONS as $p): ?><option value="<?= e($p) ?>"><?php endforeach; ?></datalist></div>
      <div class="col-md-6"><label class="form-label" for="app-email">Email <span class="req">*</span></label><input class="form-control" type="email" id="app-email" name="email" required></div>
      <div class="col-md-6"><label class="form-label" for="app-phone">Phone</label><input class="form-control" id="app-phone" name="phone" maxlength="40"></div>
      <div class="col-md-6"><label class="form-label" for="app-status">Status</label><select class="form-select" id="app-status" name="status"><?php foreach (STATUSES as $s): ?><option><?= $s ?></option><?php endforeach; ?></select></div>
      <div class="col-12"><label class="form-label" for="app-notes">Notes</label><textarea class="form-control" id="app-notes" name="notes" rows="3" maxlength="2000"></textarea></div>
      <div class="col-12"><div class="alert alert-danger form-msg d-none mb-0"></div></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save application</button></div>
  </form></div></div>

<?php if (is_admin()): ?>
<div class="modal fade" id="delModal" data-crud="delete" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered">
  <form class="modal-content" data-api="<?= $api ?>">
    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="">
    <div class="modal-header"><h2 class="modal-title h5">Delete application</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">Delete the application from <strong class="js-name"></strong>? This can't be undone.<div class="alert alert-danger form-msg d-none mt-3 mb-0"></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep it</button><button class="btn btn-danger" type="submit">Delete application</button></div>
  </form></div></div>
<?php endif; ?>
<?php include __DIR__ . '/../components/footer.php'; ?>
