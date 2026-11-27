<?php
// Public careers page: search and filter open roles, read the details, and apply in a pop-up (no login needed).
require __DIR__ . '/auth/session.php';
require __DIR__ . '/connection/jobs.php';
$jobs = jobs();
$depts = array_values(array_unique(array_column($jobs, 'dept')));
$n = count($jobs);
$title = 'Careers'; $fullWidth = true; $bodyClass = 'careers-page'; $forceDark = empty($_SESSION['user']);
include __DIR__ . '/components/header.php';
?>
<div class="careers"><div class="container-xl">

  <section class="career-hero anim-up" aria-labelledby="heroTitle">
    <?php foreach ([8, 30, 52, 70, 88] as $k => $x): ?><span class="petal" aria-hidden="true" style="--x:<?= $x ?>%;--d:<?= 12 + $k * 2 ?>s;--w:<?= $k * 1.7 ?>s;--s:<?= 14 + ($k % 3) * 6 ?>px"></span><?php endforeach; ?>
    <div class="hero-text">
      <p class="kicker">Find your next role at</p>
      <h1 class="wordmark" id="heroTitle">LA ROSE NOIRE <small>Careers</small></h1>
      <p class="hero-copy">Explore our current openings and apply for the role that matches your skills and experience.</p>
      <p class="open-count"><strong><?= $n ?></strong> open <?= $n === 1 ? 'role' : 'roles' ?></p>
    </div>
    <img class="hero-rose spin-slow" src="<?= url('assets/img/logo.svg') ?>" alt="" width="260" height="260">
  </section>

  <div class="career-tools">
    <div class="search-box"><i class="bi bi-search" aria-hidden="true"></i>
      <label class="visually-hidden" for="jobSearch">Search roles</label>
      <input id="jobSearch" type="search" placeholder="Search by role or department" autocomplete="off"></div>
    <div class="chips" role="group" aria-label="Filter by department">
      <button type="button" class="chip active" data-dept="All" aria-pressed="true">All</button>
      <?php foreach ($depts as $d): ?><button type="button" class="chip" data-dept="<?= e($d) ?>" aria-pressed="false"><?= e($d) ?></button><?php endforeach; ?>
    </div>
    <span id="showing" class="showing" role="status" aria-live="polite"></span>
  </div>

  <div class="career-grid">
    <ul class="job-list" aria-label="Open roles">
      <?php foreach ($jobs as $i => $j): ?>
        <li class="anim-up" style="--i:<?= $i ?>"><a class="job-card <?= $i === 0 ? 'active' : '' ?>" href="#job-<?= e($j['id']) ?>" data-job="<?= e($j['id']) ?>" data-dept="<?= e($j['dept']) ?>" data-search="<?= e(mb_strtolower($j['title'] . ' ' . $j['dept'] . ' ' . $j['type'] . ' ' . $j['loc'] . ' ' . $j['summary'])) ?>">
          <span class="eyebrow"><?= e($j['dept']) ?><i class="sep"></i><?= e($j['type']) ?></span>
          <h2 class="job-title"><?= e($j['title']) ?></h2>
          <span class="job-meta"><span><i class="bi bi-geo-alt"></i> <?= e($j['loc']) ?></span><span><i class="bi bi-clock"></i> <?= e($j['shift']) ?></span><span>Posted <?= ago($j['days']) ?></span></span>
          <span class="job-sum"><?= e($j['summary']) ?></span>
        </a></li>
      <?php endforeach; ?>
      <li id="noJobs" class="no-jobs" hidden>No roles match your search. Try a different word, or choose <strong>All</strong>.</li>
    </ul>

    <aside class="job-panel" aria-label="Role details" tabindex="-1">
      <button type="button" class="panel-back btn btn-outline-light"><i class="bi bi-arrow-left"></i> Back to all roles</button>
      <?php foreach ($jobs as $i => $j): ?>
        <article class="job-detail" id="detail-<?= e($j['id']) ?>" <?= $i === 0 ? '' : 'hidden' ?>>
          <header class="detail-head">
            <span class="eyebrow"><?= e($j['dept']) ?></span>
            <h2><?= e($j['title']) ?></h2>
            <p class="detail-meta"><span><i class="bi bi-geo-alt"></i> <?= e($j['loc']) ?></span><span><i class="bi bi-clock"></i> <?= e($j['type']) ?></span><span><i class="bi bi-brightness-high"></i> <?= e($j['shift']) ?></span></p>
            <?php if (!empty($j['edu'])): ?><p class="detail-edu"><i class="bi bi-mortarboard"></i> <?= e($j['edu']) ?></p><?php endif; ?>
            <div class="d-flex flex-wrap align-items-center gap-3 mt-3">
              <button type="button" class="btn btn-pink btn-lg" data-bs-toggle="modal" data-bs-target="#applyModal" data-title="<?= e($j['title']) ?>">Apply now</button>
              <span class="posted">Posted <?= ago($j['days']) ?></span></div>
          </header>
          <div class="detail-body">
            <h3>About the role</h3><p><?= e($j['about']) ?></p>
            <h3>Responsibilities</h3><ul><?php foreach ($j['resp'] as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul>
            <h3>Qualifications</h3><ul><?php foreach ($j['qual'] as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul>
          </div>
        </article>
      <?php endforeach; ?>
    </aside>
  </div>
</div></div>

<div class="modal fade" id="applyModal" tabindex="-1" aria-labelledby="applyTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
  <form class="modal-content" data-api="<?= url('api/apply.php') ?>" data-reload="0">
    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="position" value="">
    <div class="d-none" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
    <div class="modal-header"><div><h2 class="modal-title h4 brand-title" id="applyTitle">Apply to La Rose Noire</h2><p class="mb-0 text-body-secondary">You are applying for <strong class="js-job-title"></strong></p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body"><p class="text-body-secondary">Fields marked <span class="req">*</span> are required. We will reply to your email.</p><div class="row g-3">
      <div class="col-md-6"><label class="form-label" for="pub-applicant_name">Full name <span class="req">*</span></label><input class="form-control" id="pub-applicant_name" name="applicant_name" required maxlength="120" autocomplete="name"></div>
      <div class="col-md-6"><label class="form-label" for="pub-email">Email <span class="req">*</span></label><input class="form-control" type="email" id="pub-email" name="email" required autocomplete="email"></div>
      <div class="col-md-6"><label class="form-label" for="pub-phone">Phone</label><input class="form-control" type="tel" id="pub-phone" name="phone" maxlength="40" autocomplete="tel"></div>
      <div class="col-12"><div class="form-label" id="cvLabel">Your CV or resume<?= CV_REQUIRED ? ' <span class="req">*</span>' : ' (optional)' ?></div>
        <input class="visually-hidden" type="file" id="pub-cv" name="cv" accept=".pdf,.doc,.docx" aria-labelledby="cvLabel" data-max="<?= MAX_CV_MB ?>" <?= CV_REQUIRED ? 'required' : '' ?>>
        <label class="file-drop" for="pub-cv"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i><span><strong>Choose a file</strong><small>PDF or Word, up to <?= MAX_CV_MB ?> MB</small></span></label>
        <div class="file-error bad-note mt-2" role="alert" hidden></div></div>
      <div class="col-12"><label class="form-label" for="pub-notes">Tell us about yourself</label><textarea class="form-control" id="pub-notes" name="notes" rows="4" maxlength="2000"></textarea></div>
      <div class="col-12"><div class="alert form-msg d-none mb-0" aria-live="polite"></div></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Send application</button></div>
  </form></div></div>
<script src="<?= url('scripts/careers.js') ?>"></script>
<?php include __DIR__ . '/components/footer.php'; ?>
