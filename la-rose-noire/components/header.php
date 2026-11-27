<?php
$user = $_SESSION['user'] ?? null;
$page = basename($_SERVER['SCRIPT_NAME']);
$nav = [];
$prefs = $user ? ($user['prefs'] ?? prefs_clean(null)) : null;   // display settings exist for signed-in users only
if ($user) {
    $nav = ['pages/dashboard.php' => ['Dashboard', 'speedometer2'], 'pages/applications.php' => ['Applications', 'journal-text']];
    if (is_admin()) { $nav['pages/users.php'] = ['Users', 'people']; $nav['pages/audit-logs.php'] = ['Audit logs', 'clipboard-data']; }
}
?>
<!DOCTYPE html>
<html lang="en"<?php if ($prefs): ?> data-pref-theme="<?= e($prefs['theme']) ?>" data-font="<?= e($prefs['font']) ?>" data-space="<?= e($prefs['space']) ?>" data-corners="<?= e($prefs['corners']) ?>" data-contrast="<?= e($prefs['contrast']) ?>" data-motion="<?= e($prefs['motion']) ?>" style="--accent:<?= e($prefs['accent']) ?>;--on-accent:<?= e(pref_on($prefs['accent'])) ?>;font-size:<?= e($prefs['size']) ?>%"<?php endif; ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<title><?= e($title ?? APP_NAME) ?> · <?= APP_NAME ?></title>
<link rel="icon" href="<?= url('assets/img/logo.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= url('styles/style.css') ?>" rel="stylesheet">
<script nonce="<?= nonce() ?>">
(function(){var d=document.documentElement,p=d.dataset.prefTheme||'<?= !empty($forceDark) ? 'dark' : 'auto' ?>';
d.setAttribute('data-bs-theme',p==='dark'||(p==='auto'&&matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light');d.classList.add('js');})();
</script>
</head>
<body class="<?= e($bodyClass ?? '') ?>" data-base="<?= e(BASE_URL) ?>" data-csrf="<?= $user ? csrf() : '' ?>" <?= $user ? 'data-auth="1"' : '' ?>>
<a class="skip-link" href="#main">Skip to main content</a>
<nav class="navbar navbar-expand-lg app-nav sticky-top" aria-label="Main">
  <div class="container-xl">
    <a class="navbar-brand brand-title d-flex align-items-center gap-2" href="<?= $user ? url('pages/dashboard.php') : url('index.php') ?>">
      <img src="<?= url('assets/img/logo.svg') ?>" alt="" width="34" height="34"><?= APP_NAME ?></a>
    <div class="d-flex align-items-center gap-2 ms-auto order-lg-2">
      <?php if ($user): ?><button class="btn btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#prefsPanel" aria-controls="prefsPanel"><i class="bi bi-palette"></i><span class="d-none d-sm-inline"> Customize</span></button><?php endif; ?>
      <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-label="Open menu"><span class="navbar-toggler-icon"></span></button>
    </div>
    <div class="collapse navbar-collapse order-lg-1" id="nav">
      <ul class="navbar-nav me-auto">
        <?php foreach ($nav as $f => [$l, $ic]): $cur = $page === basename($f); ?>
          <li class="nav-item"><a class="nav-link <?= $cur ? 'active' : '' ?>" <?= $cur ? 'aria-current="page"' : '' ?> href="<?= url($f) ?>"><i class="bi bi-<?= $ic ?>"></i> <?= $l ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="d-flex align-items-center gap-2 py-2 py-lg-0">
        <?php if ($user): ?>
          <span class="user-chip"><i class="bi bi-person-circle"></i> <?= e($user['name']) ?> <small>(<?= e($user['role']) ?>)</small></span>
          <a class="btn btn-outline-primary" href="<?= url('auth/logout.php') ?>"><i class="bi bi-box-arrow-right"></i> Sign out</a>
        <?php else: ?>
          <a class="btn btn-outline-primary" href="<?= url('auth/login.php') ?>">Sign in</a>
          <?php if (SIGNUP_OPEN): ?><a class="btn btn-primary" href="<?= url('auth/register.php') ?>">Sign up</a><?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<?php if ($user): $def = prefs_clean(null);
$group = function (string $key, string $label, array $opts) use ($prefs, $def) {
    echo '<fieldset class="pref-group"><legend>' . $label . '</legend><div class="pref-opts">';
    foreach ($opts as $val => $text) {
        $val = (string)$val; $id = 'pf-' . $key . '-' . substr(md5($val), 0, 6);
        echo '<input type="radio" class="btn-check" name="pf-' . $key . '" id="' . $id . '" data-pref="' . $key . '" value="' . e($val) . '"' . ($prefs[$key] === $val ? ' checked' : '') . ($def[$key] === $val ? ' data-default' : '') . '><label class="btn btn-outline-secondary" for="' . $id . '">' . $text . '</label>';
    }
    echo '</div></fieldset>';
};
?>
<div class="offcanvas offcanvas-end" tabindex="-1" id="prefsPanel" aria-labelledby="prefsTitle">
  <div class="offcanvas-header"><h2 class="offcanvas-title h4 brand-title" id="prefsTitle">Make it yours</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
  <div class="offcanvas-body">
    <p class="text-body-secondary">Changes show straight away and are saved to your account, so they follow you to any device.</p>
    <?php
    $group('size', '<i class="bi bi-fonts"></i> Text size', ['100' => 'Normal', '115' => 'Large', '130' => 'Larger', '150' => 'Largest']);
    $group('theme', '<i class="bi bi-brightness-high"></i> Brightness', ['light' => 'Light', 'dark' => 'Dark', 'auto' => 'Match device']);
    $group('contrast', '<i class="bi bi-circle-half"></i> Contrast', ['0' => 'Standard', '1' => 'High contrast']);
    $group('font', '<i class="bi bi-type"></i> Lettering', ['inter' => 'Clean', 'atkinson' => 'Easy-read', 'system' => 'Standard', 'serif' => 'Classic']);
    $group('space', '<i class="bi bi-arrows-expand"></i> Spacing', ['cozy' => 'Comfortable', 'roomy' => 'Extra roomy']);
    $group('corners', '<i class="bi bi-bounding-box-circles"></i> Corners', ['round' => 'Rounded', 'square' => 'Square']);
    $group('motion', '<i class="bi bi-activity"></i> Movement', ['0' => 'Animations on', '1' => 'Fewer animations']);
    ?>
    <fieldset class="pref-group"><legend><i class="bi bi-palette"></i> Accent color</legend><div class="pref-opts swatches">
      <?php foreach (PREF_ACCENTS as $n => $c): $id = 'pf-accent-' . strtolower($n); ?>
        <input type="radio" class="btn-check" name="pf-accent" id="<?= $id ?>" data-pref="accent" data-on="<?= pref_on($c) ?>" value="<?= $c ?>" <?= $prefs['accent'] === $c ? 'checked' : '' ?> <?= $def['accent'] === $c ? 'data-default' : '' ?>><label class="swatch" for="<?= $id ?>" style="background:<?= $c ?>" title="<?= $n ?>"><span class="visually-hidden"><?= $n ?></span></label>
      <?php endforeach; ?></div></fieldset>
    <div class="d-flex align-items-center flex-wrap gap-3 mt-4"><button type="button" class="btn btn-outline-secondary" id="prefReset"><i class="bi bi-arrow-counterclockwise"></i> Reset to default</button><span id="prefStatus" class="small text-body-secondary" role="status" aria-live="polite"></span></div>
  </div>
</div>
<?php endif; ?>
<main id="main" tabindex="-1" class="<?= !empty($fullWidth) ? '' : 'container-xl py-4' ?>">
<?php if (!empty($_SESSION['flash'])): [$ft, $fm] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="<?= !empty($fullWidth) ? 'container-xl pt-3' : '' ?>"><div class="alert alert-<?= e($ft) ?> alert-dismissible fade show flash-in" role="alert"><?= e($fm) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close message"></button></div></div>
<?php endif; ?>
