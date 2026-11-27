<?php
require __DIR__ . '/session.php';
$idle = isset($_GET['idle']);
if (!empty($_SESSION['user'])) audit($idle ? 'Auto logout' : 'Logout', $idle ? 'Session idle for 15 minutes' : '');
session_unset();
session_regenerate_id(true);
if ($idle) flash('warning', 'You were signed out after 15 minutes of inactivity.');
redirect(url('auth/login.php'));
