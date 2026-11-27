<?php
// Public endpoint: "Request help". The applicant enters the secret key they were given when they applied.
// If it is correct and still valid, their current details are returned so they can be edited.
define('IS_API', true);
require __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../services/secret.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);
if (!csrf_ok()) json_out(['ok' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);
if (!empty($_POST['website'])) json_out(['ok' => false, 'message' => 'That secret key is not right.'], 403);   // honeypot: bots fill this

$app = secret_require((string)($_POST['secret_key'] ?? ''));
json_out(['ok' => true, 'expires' => $app['key_expires'], 'application' => [
    'applicant_name' => $app['applicant_name'], 'email' => $app['email'], 'phone' => $app['phone'],
    'position' => $app['position'], 'notes' => $app['notes'], 'cv_name' => $app['cv_name'],
]]);
