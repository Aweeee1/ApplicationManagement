<?php
// Staff-only download of an applicant's CV. Files live in storage/cvs, which the web server refuses to serve directly.
require __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../services/applications.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT applicant_name, cv_file, cv_name FROM applications WHERE id = ?');
$st->execute([$id]);
$r = $st->fetch();
$path = $r && $r['cv_file'] ? cv_path($r['cv_file']) : '';
if ($path === '' || !is_file($path)) { http_response_code(404); exit('No CV is on file for this application.'); }
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$types = ['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
audit('Downloaded CV', "#$id {$r['applicant_name']}");
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="resume.' . $ext . '"; filename*=UTF-8\'\'' . rawurlencode($r['cv_name'] ?: "resume.$ext"));
header('X-Content-Type-Options: nosniff');
readfile($path);
