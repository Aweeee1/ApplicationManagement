<?php
// Called by scripts/app.js while the user is active, so the server-side idle timer matches the browser's.
define('IS_API', true);
require __DIR__ . '/../auth/session.php';
require_login();
http_response_code(204);
