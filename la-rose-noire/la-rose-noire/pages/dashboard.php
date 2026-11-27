<?php
require __DIR__ . '/../auth/session.php';
require_login();
$pdo = db();
$counts = array_fill_keys(STATUSES, 0);
foreach ($pdo->query('SELECT status, COUNT(*) c FROM applications GROUP BY status') as $r) $counts[$r['status']] = (int)$r['c'];
$total = array_sum($counts);
$fromWeb = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE source='public'")->fetchColumn();
$recent = $pdo->query('SELECT id, applicant_name, email, phone, position, status, notes, source, cv_file, cv_name, self_updated_at, secret_expires_at, created_by, created_at, updated_at FROM applications ORDER BY created_at DESC, id DESC LIMIT 6')->fetchAll();
if (is_admin()) {
    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $pending = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
    $logs = $pdo->query('SELECT * FROM audit_logs ORDER BY id DESC LIMIT 5')->fetchAll();
}
$prev = $pdo->prepare("SELECT created_at FROM audit_logs WHERE user_id = ? AND action = 'Login' ORDER BY id DESC LIMIT 1 OFFSET 1");
$prev->execute([$_SESSION['user']['id']]);
$lastLogin = $prev->fetchColumn();
$fails = 0;
if ($lastLogin) { $f = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE user_id = ? AND action = 'Failed login' AND created_at > ?"); $f->execute([$_SESSION['user']['id'], $lastLogin]); $fails = (int)$f->fetchColumn(); }
$title = 'Dashboard';
include __DIR__ . '/../components/header.php';
?>
<?php if (is_admin() && $pending): ?>
<div class="alert alert-warning d-flex flex-wrap align-items-center gap-3 flash-in" role="status"><i class="bi bi-person-exclamation fs-3"></i><div class="flex-grow-1"><strong><?= $pending ?> <?= $pending === 1 ? 'person is' : 'people are' ?> waiting for account approval.</strong></div><a class="btn btn-dark" href="<?= url('pages/users.php?status=pending') ?>">Review now</a></div>
<?php endif; ?>
<?php if ($fails): ?><div class="alert alert-warning flash-in" role="status"><i class="bi bi-shield-exclamation"></i> <strong><?= $fails ?> wrong password <?= $fails === 1 ? 'try was' : 'tries were' ?> made on your account</strong> since you last signed in. If that was not you, ask an administrator to reset your password.</div><?php endif; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2 anim-up">
  <div><span class="eyebrow">Overview</span><h1 class="h2 brand-title mb-0">Welcome, <?= e(explode(' ', $_SESSION['user']['name'])[0]) ?></h1>
    <p class="text-body-secondary mb-0">Select any card to see those records.<?php if ($lastLogin): ?> <span class="d-block small"><i class="bi bi-clock-history"></i> Last signed in <?= e(date('j M Y, g:i a', strtotime($lastLogin))) ?></span><?php endif; ?></p></div>
  <a class="btn btn-primary btn-lg" href="<?= url('pages/applications.php?new=1') ?>"><i class="bi bi-plus-lg"></i> New application</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3 anim-up" style="--i:0"><a class="stat-card total" href="<?= url('pages/applications.php') ?>"><i class="bi bi-inboxes"></i><span class="stat-num" data-count="<?= $total ?>"><?= $total ?></span><span>All applications</span></a></div>
  <?php $ic = ["Pending" => "hourglass-split", "Reviewing" => "search", "Interview" => "chat-dots", "Approved" => "check2-circle", "Rejected" => "x-circle"]; $k = 0; foreach ($counts as $s => $n): $k++; ?>
    <div class="col-6 col-lg-3 anim-up" style="--i:<?= $k ?>"><a class="stat-card" href="<?= url('pages/applications.php?status=' . urlencode($s)) ?>"><i class="bi bi-<?= $ic[$s] ?? 'circle' ?>"></i><span class="stat-num" data-count="<?= $n ?>"><?= $n ?></span><span><?= e($s) ?></span></a></div>
  <?php endforeach; ?>
  <div class="col-6 col-lg-3 anim-up" style="--i:6"><a class="stat-card" href="<?= url('pages/applications.php?source=public') ?>"><i class="bi bi-globe2"></i><span class="stat-num" data-count="<?= $fromWeb ?>"><?= $fromWeb ?></span><span>From the public page</span></a></div>
  <?php if (is_admin()): ?>
    <div class="col-6 col-lg-3 anim-up" style="--i:7"><a class="stat-card" href="<?= url('pages/users.php') ?>"><i class="bi bi-people"></i><span class="stat-num" data-count="<?= $userCount ?>"><?= $userCount ?></span><span>System users</span></a></div>
  <?php endif; ?>
</div>

<div class="row g-4">
  <div class="col-lg-7 anim-up" style="--i:8">
    <div class="card h-100"><div class="card-header d-flex justify-content-between"><strong>Pipeline</strong><span class="text-body-secondary small">select a bar to filter</span></div>
      <div class="card-body">
        <?php foreach ($counts as $s => $n): $pct = $total ? round($n / $total * 100) : 0; ?>
          <a class="pipe-row" href="<?= url('pages/applications.php?status=' . urlencode($s)) ?>">
            <span class="pipe-label"><?= e($s) ?></span>
            <span class="progress flex-grow-1" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"><span class="progress-bar" style="width:<?= $pct ?>%"></span></span>
            <span class="pipe-val"><?= $n ?></span>
          </a>
        <?php endforeach; ?>
        <?php if (!$total): ?><p class="text-body-secondary mt-3 mb-0">No applications yet. Add one, or share your public page, to see the pipeline fill up.</p><?php endif; ?>
      </div></div>
  </div>
  <div class="col-lg-5 anim-up" style="--i:9">
    <div class="card h-100"><div class="card-header"><strong>Latest applications</strong></div>
      <div class="list-group list-group-flush">
        <?php foreach ($recent as $r): ?>
          <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?= url('pages/applications.php?open=' . $r['id']) ?>">
            <span><span class="d-block fw-medium"><?= e($r['applicant_name']) ?></span><small class="text-body-secondary"><?= e($r['position']) ?></small></span>
            <?= badge($r['status']) ?>
          </a>
        <?php endforeach; ?>
        <?php if (!$recent): ?><div class="list-group-item text-body-secondary">Nothing here yet.</div><?php endif; ?>
      </div></div>
  </div>
  <?php if (is_admin()): ?>
  <div class="col-12 anim-up" style="--i:10">
    <div class="card"><div class="card-header d-flex justify-content-between"><strong>Recent activity</strong><a href="<?= url('pages/audit-logs.php') ?>">View all logs</a></div>
      <div class="list-group list-group-flush">
        <?php foreach ($logs as $l): ?>
          <a class="list-group-item list-group-item-action small" href="<?= url('pages/audit-logs.php?action=' . urlencode($l['action'])) ?>">
            <strong><?= e($l['user_name']) ?></strong> · <?= e($l['action']) ?> <span class="text-body-secondary"><?= e($l['details']) ?></span>
            <span class="float-md-end text-body-secondary"><?= e($l['created_at']) ?></span></a>
        <?php endforeach; ?>
      </div></div>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../components/footer.php'; ?>
