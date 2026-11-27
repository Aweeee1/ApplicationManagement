<?php
// Saves the signed-in person's display settings. Every value is checked against a fixed list.
define('IS_API', true);
require __DIR__ . '/../auth/session.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);
if (!csrf_ok()) json_out(['ok' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);
db()->prepare('UPDATE users SET prefs = ? WHERE id = ?')->execute([json_encode(prefs_clean($_POST)), $_SESSION['user']['id']]);
json_out(['ok' => true]);
