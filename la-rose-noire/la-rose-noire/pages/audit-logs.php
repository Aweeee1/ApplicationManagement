<?php
require __DIR__ . '/../auth/session.php';
require_admin();
$pdo = db();
$q = trim($_GET['q'] ?? '');
$action = trim($_GET['action'] ?? '');
$where = []; $params = [];
if ($q !== '') { $where[] = '(user_name LIKE ? OR details LIKE ? OR ip LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
if ($action !== '') { $where[] = 'action = ?'; $params[] = $action; }
$w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$per = 25;
$cnt = $pdo->prepare('SELECT COUNT(*) FROM audit_logs' . $w); $cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$p = min(max(1, (int)($_GET['p'] ?? 1)), $pages);
$st = $pdo->prepare('SELECT * FROM audit_logs' . $w . ' ORDER BY id DESC LIMIT ' . $per . ' OFFSET ' . (($p - 1) * $per));
$st->execute($params);
$logs = $st->fetchAll();
$actions = $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
$qs = fn(int $n) => url('pages/audit-logs.php?' . http_build_query(['q' => $q, 'action' => $action, 'p' => $n]));
$title = 'Audit logs';
include __DIR__ . '/../components/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <div><span class="eyebrow">Administration</span><h1 class="h2 brand-title mb-0">Audit logs</h1></div>
  <span class="text-body-secondary"><?= $total ?> entries</span>
</div>
<form class="row g-2 mb-3" method="get">
  <div class="col-12 col-md-5"><input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search user, details or IP" aria-label="Search logs"></div>
  <div class="col-7 col-md-4"><select class="form-select" name="action" aria-label="Action"><option value="">All actions</option>
    <?php foreach ($actions as $a): ?><option <?= $a === $action ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select></div>
  <div class="col-5 col-md-3 d-flex gap-2"><button class="btn btn-outline-primary flex-fill">Filter</button><a class="btn btn-outline-secondary" href="<?= url('pages/audit-logs.php') ?>">Reset</a></div>
</form>
<div class="card"><div class="table-responsive">
<table class="table table-sm table-hover align-middle mb-0">
  <thead><tr><th>Time</th><th>User</th><th>Action</th><th class="d-none d-md-table-cell">Details</th><th class="d-none d-lg-table-cell">IP</th></tr></thead>
  <tbody>
  <?php foreach ($logs as $l): ?>
    <tr><td class="text-nowrap small"><?= e($l['created_at']) ?></td><td><?= e($l['user_name']) ?></td>
      <td><a href="<?= url('pages/audit-logs.php?action=' . urlencode($l['action'])) ?>"><?= e($l['action']) ?></a><small class="d-md-none d-block text-body-secondary"><?= e($l['details']) ?></small></td>
      <td class="d-none d-md-table-cell"><?= e($l['details']) ?></td><td class="d-none d-lg-table-cell small"><?= e($l['ip']) ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$logs): ?><tr><td colspan="5" class="text-center text-body-secondary py-5">No log entries match these filters.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
<?php if ($pages > 1): ?>
<nav class="mt-3" aria-label="Pages"><ul class="pagination justify-content-center flex-wrap">
  <li class="page-item <?= $p <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $qs($p - 1) ?>">Previous</a></li>
  <li class="page-item disabled"><span class="page-link">Page <?= $p ?> of <?= $pages ?></span></li>
  <li class="page-item <?= $p >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= $qs($p + 1) ?>">Next</a></li>
</ul></nav>
<?php endif; ?>
<?php include __DIR__ . '/../components/footer.php'; ?>
