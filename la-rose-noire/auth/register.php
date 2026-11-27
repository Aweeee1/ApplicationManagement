<?php
require __DIR__ . '/session.php';
if (!empty($_SESSION['user'])) redirect(url('pages/dashboard.php'));
$pdo = db();
$first = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;   // the very first account becomes the administrator
$closed = !SIGNUP_OPEN && !$first;
$needKey = $first && SETUP_KEY !== '';
$errors = [];
$name = trim($_POST['name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$closed) {
    $pw = $_POST['password'] ?? '';
    $ip = client_ip();
    if (!csrf_ok()) $errors[] = 'This page was open for too long. Please try again.';
  
    elseif (!empty($_POST['website']) || time() - (int)($_SESSION['form_shown'] ?? 0) < 3) $errors[] = 'Please take a moment to fill in the form, then try again.'; // hidden trap field + too-fast submit = bot
    elseif (!$first && ($w = throttle_wait('signup-ip', $ip, 3, 60))) $errors[] = 'Too many sign-up requests from this device. Please try again in ' . wait_text($w) . '.';
    if (!$errors && $needKey && !hash_equals(SETUP_KEY, (string)($_POST['setup_key'] ?? ''))) $errors[] = 'The setup key is not right. You can find it in connection/config.php.';
    if (!$errors) {
        if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Please enter your name (up to 100 characters).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) $errors[] = 'Please enter a valid email address, like name@example.com.';
        $errors = array_merge($errors, password_problems($pw, $email, $name));
        if ($pw !== ($_POST['confirm'] ?? '')) $errors[] = 'The two passwords are not the same.';
    }
    if (!$errors) {
        throttle_hit('signup-ip', $ip);
        $hash = hash_pw($pw);                                   // hash first, so timing is the same whether or not the email is taken
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            audit('Register', "Request for an email that already has an account: $email", ['id' => null, 'name' => $name]);
        } else {
            $role = $first ? 'admin' : 'staff';
            $status = $first ? 'active' : 'pending';           // everyone after the first must be approved by an administrator
            $pdo->prepare('INSERT INTO users (name, email, password_hash, role, status) VALUES (?,?,?,?,?)')->execute([$name, $email, $hash, $role, $status]);
            audit('Register', "New $role account ($status): $email", ['id' => (int)$pdo->lastInsertId(), 'name' => $name]);
        }
        // Same message either way, so nobody can use this page to find out which emails have accounts.
        flash('success', $first ? 'Your administrator account is ready. You can sign in now.'
                                : 'Thank you! Your request was received. An administrator will approve it, and then you can sign in.');
        redirect(url('auth/login.php'));
    }
}
$_SESSION['form_shown'] = time();
$title = 'Create account';
$bodyClass = 'auth-page'; $forceDark = true;
include __DIR__ . '/../components/header.php';
?>
<div class="auth-wrap">
  <aside class="auth-side" aria-hidden="true">
    <img src="<?= url('assets/img/logo.jpg') ?>" alt="" width="84" height="84" class="spin-slow">
    <h2 class="brand-title">Join the team</h2>
    <p>Ask for a staff account. It only takes a minute.</p>
    <ul class="auth-points"><li><i class="bi bi-1-circle"></i> Fill in the form</li><li><i class="bi bi-2-circle"></i> An administrator approves it</li><li><i class="bi bi-3-circle"></i> Sign in and get started</li></ul>
  </aside>
  <div class="auth-card card <?= $errors ? 'shake' : '' ?>">
    <div class="card-body p-4 p-md-5">
      <h1 class="brand-title mb-1"><?= $first ? 'Create the administrator account' : 'Ask for an account' ?></h1>
      <?php if ($closed): ?>
        <div class="alert alert-info mt-3" role="status">New accounts are created by an administrator. Please ask them to add you.</div>
        <a class="btn btn-primary btn-lg w-100" href="<?= url('auth/login.php') ?>">Back to sign in</a>
      <?php else: ?>
      <p class="text-body-secondary mb-4">Fields marked <span class="req">*</span> are required.</p>
      <?php if ($errors): ?><div class="alert alert-danger" role="alert"><strong>Please fix this:</strong><ul class="mb-0 mt-1"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <form method="post" class="js-auth js-signup" novalidate>
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <div class="d-none" aria-hidden="true"><label>Leave this empty <input name="website" tabindex="-1" autocomplete="off"></label></div>
        <?php if ($needKey): ?><div class="mb-3"><label class="form-label" for="setup_key">Setup key <span class="req">*</span></label>
          <input class="form-control form-control-lg" id="setup_key" name="setup_key" required autocomplete="off"><div class="form-text">This keeps strangers from taking over a new site. It is set in <code>connection/config.php</code>.</div></div><?php endif; ?>
        <div class="mb-3"><label class="form-label" for="name">Full name <span class="req">*</span></label>
          <input class="form-control form-control-lg" id="name" name="name" required maxlength="100" autocomplete="name" value="<?= e($name) ?>"></div>
        <div class="mb-3"><label class="form-label" for="email">Email address <span class="req">*</span></label>
          <input class="form-control form-control-lg" type="email" id="email" name="email" required autocomplete="username" inputmode="email" autocapitalize="none" spellcheck="false" value="<?= e($email) ?>"></div>
        <div class="mb-3"><label class="form-label" for="password">Password <span class="req">*</span></label>
          <div class="pw-field"><input class="form-control form-control-lg" type="password" id="password" name="password" required minlength="<?= MIN_PASSWORD ?>" maxlength="72" autocomplete="new-password" aria-describedby="pwHelp">
            <button type="button" class="pw-toggle" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i><span>Show</span></button></div>
          <div class="caps-note d-none" role="status"><i class="bi bi-capslock-fill"></i> Caps Lock is on</div>
          <div class="meter" aria-hidden="true"><span></span></div>
          <div id="pwHelp" class="form-text" aria-live="polite">Use at least <?= MIN_PASSWORD ?> characters. A short sentence works well, like <em>"blue tea at five"</em>.</div></div>
        <div class="mb-4"><label class="form-label" for="confirm">Type the password again <span class="req">*</span></label>
          <input class="form-control form-control-lg" type="password" id="confirm" name="confirm" required autocomplete="new-password" maxlength="72">
          <div id="matchNote" class="form-text" aria-live="polite"></div>
          <ul class="pw-rules" aria-hidden="true"><li data-rule="len"><i class="bi bi-circle"></i> At least <?= MIN_PASSWORD ?> characters</li><li data-rule="match"><i class="bi bi-circle"></i> Both passwords match</li></ul></div>
        <button class="btn btn-primary btn-lg w-100 js-submit"><span class="label"><?= $first ? 'Create administrator account' : 'Send my request' ?></span></button>
      </form>
      <p class="text-center mt-4 mb-0">Already have an account? <a href="<?= url('auth/login.php') ?>">Sign in</a></p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../components/footer.php'; ?>
