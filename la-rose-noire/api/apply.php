<?php
// Public endpoint: anyone can submit an application from the landing page. No login required.
define('IS_API', true);
require __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../services/applications.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);
if (!csrf_ok()) json_out(['ok' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);
if (!empty($_POST['website'])) json_out(['ok' => true, 'message' => 'Thank you. We received your application.']); // honeypot: bots fill this
if ($w = throttle_wait('apply-ip', client_ip(), 5, 60)) json_out(['ok' => false, 'message' => 'Too many applications from this device. Please try again in ' . wait_text($w) . '.'], 429);
if (time() - ($_SESSION['last_apply'] ?? 0) < 30) json_out(['ok' => false, 'message' => 'Please wait a few seconds before sending another application.'], 429);

[$f, $err] = clean_application($_POST + []);
if ($err) json_out(['ok' => false, 'message' => implode(' ', $err)], 422);

$pdo = db();
$pdo->prepare("INSERT INTO applications (applicant_name, email, phone, position, status, notes, source) VALUES (?,?,?,?, 'Pending', ?, 'public')")
    ->execute([$f['applicant_name'], $f['email'], $f['phone'], $f['position'], $f['notes']]);
audit('Public application', '#' . $pdo->lastInsertId() . " {$f['applicant_name']} ({$f['position']})", ['id' => null, 'name' => $f['applicant_name']]);
$_SESSION['last_apply'] = time();
throttle_hit('apply-ip', client_ip());
json_out(['ok' => true, 'message' => 'Thank you. We received your application and will be in touch.']);
