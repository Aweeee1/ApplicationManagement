<?php
require __DIR__ . '/session.php';
if (!empty($_SESSION['user'])) redirect(url('pages/dashboard.php'));
$error = '';
$locked = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $ip = client_ip();
    // Limits: MAX_ATTEMPTS wrong tries per email, and a wider limit per device address.
    $locked = max(throttle_wait('login-email', $email, MAX_ATTEMPTS, LOCK_MINUTES), throttle_wait('login-ip', $ip, MAX_ATTEMPTS * 4, LOCK_MINUTES));
    if (!csrf_ok()) {
        $error = 'This page was open for too long. Please try again.';
    } elseif ($locked) {
        audit('Blocked login', "Too many attempts: $email", ['id' => null, 'name' => 'Guest']);
        $error = 'Too many wrong attempts. For your safety, sign-in is paused. Please try again in ' . wait_text($locked) . '.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();
        // Always run one password check, even for unknown emails, so response time does not reveal which emails exist.
        $dummy = '$2y$12$aBh8ZiDXQxv.ZRk9IUieieKULxUR.e.REDQXi5MkPBoBGT2dLTQXO';
        $ok = password_verify($_POST['password'] ?? '', $u['password_hash'] ?? $dummy) && $u;
        if ($ok && $u['status'] === 'pending') {
            $error = 'Your account is waiting for approval. An administrator will activate it soon.';
        } elseif ($ok && $u['status'] !== 'active') {
            audit('Failed login', "Disabled account: $email", $u);
            $error = 'This account is turned off. Please contact an administrator.';
        } elseif ($ok) {
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT, ['cost' => 12]))
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([hash_pw($_POST['password']), $u['id']]);
            throttle_clear('login-email', $email);
            session_regenerate_id(true);               // new session id after sign-in (stops session fixation)
            unset($_SESSION['csrf']);                  // fresh form token for the new session
            $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
            $_SESSION['last'] = $_SESSION['started'] = time();
            audit('Login', 'Signed in');
            redirect(url('pages/dashboard.php'));
        } else {
            throttle_hit('login-email', $email);
            throttle_hit('login-ip', $ip);
            audit('Failed login', "Email: $email", ['id' => $u['id'] ?? null, 'name' => $u['name'] ?? 'Guest']);
            $error = 'The email or password is not right. Please check them and try again.';
            $w = throttle_wait('login-email', $email, MAX_ATTEMPTS, LOCK_MINUTES);
            if ($w) $error = 'Too many wrong attempts. For your safety, sign-in is paused. Please try again in ' . wait_text($w) . '.';
            elseif (($left = MAX_ATTEMPTS - throttle_count('login-email', $email, LOCK_MINUTES)) <= 2 && $left > 0)
                $error .= " You have $left " . ($left === 1 ? 'try' : 'tries') . ' left before a ' . LOCK_MINUTES . '-minute safety pause.';
        }
    }
}
$title = 'Sign in';
$bodyClass = 'auth-page'; $forceDark = true;
include __DIR__ . '/../components/header.php';
?>
<div class="auth-wrap">
  <aside class="auth-side" aria-hidden="true">
    <img src="<?= url('assets/img/logo.jpg') ?>" alt="" width="84" height="84" class="spin-slow">
    <h2 class="brand-title">Welcome back</h2>
    <p>Sign in to review applications and keep the team on track.</p>
    <ul class="auth-points"><li><i class="bi bi-shield-lock"></i> Your sign-in is protected</li><li><i class="bi bi-clock-history"></i> Signs you out when you are away</li><li><i class="bi bi-eye"></i> Show your password while typing</li></ul>
  </aside>
  <div class="auth-card card <?= $error ? 'shake' : '' ?>">
    <div class="card-body p-4 p-md-5">
      <h1 class="brand-title mb-1">Sign in</h1>
      <p class="text-body-secondary mb-4">Enter your email and password.</p>
      <?php if ($error): ?><div class="alert alert-danger d-flex gap-2" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><div><?= e($error) ?></div></div><?php endif; ?>
      <form method="post" class="js-auth" novalidate>
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <div class="mb-3"><label class="form-label" for="email">Email address</label>
          <input class="form-control form-control-lg" type="email" id="email" name="email" required autofocus autocomplete="username" inputmode="email" autocapitalize="none" spellcheck="false" value="<?= e($_POST['email'] ?? '') ?>"></div>
        <div class="mb-2"><label class="form-label" for="password">Password</label>
          <div class="pw-field"><input class="form-control form-control-lg" type="password" id="password" name="password" required autocomplete="current-password">
            <button type="button" class="pw-toggle" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i><span>Show</span></button></div>
          <div class="caps-note d-none" role="status"><i class="bi bi-capslock-fill"></i> Caps Lock is on</div></div>
        <button class="btn btn-primary btn-lg w-100 mt-4 js-submit"><span class="label">Sign in</span></button>
      </form>
      <?php if (SIGNUP_OPEN): ?><p class="text-center mt-4 mb-0">New here? <a href="<?= url('auth/register.php') ?>">Ask for an account</a></p><?php endif; ?>
      <p class="text-center small text-body-secondary mt-3 mb-0">Forgot your password? Ask an administrator to reset it for you.</p>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../components/footer.php'; ?>
