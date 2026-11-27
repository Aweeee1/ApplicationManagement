<?php
// Shared validation for staff CRUD and the public application form.
function clean_application(array $in): array {
    $f = [
        'applicant_name' => trim($in['applicant_name'] ?? ''),
        'email'          => trim($in['email'] ?? ''),
        'phone'          => trim($in['phone'] ?? ''),
        'position'       => trim($in['position'] ?? ''),
        'status'         => $in['status'] ?? 'Pending',
        'notes'          => trim($in['notes'] ?? ''),
    ];
    $err = [];
    if ($f['applicant_name'] === '' || mb_strlen($f['applicant_name']) > 120) $err[] = 'Enter the applicant name (up to 120 characters).';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $err[] = 'Enter a valid email address.';
    if ($f['position'] === '' || mb_strlen($f['position']) > 120) $err[] = 'Enter the position (up to 120 characters).';
    if (mb_strlen($f['phone']) > 40) $err[] = 'Phone number is too long.';
    if (mb_strlen($f['notes']) > 2000) $err[] = 'Notes are too long (2000 characters max).';
    if (!in_array($f['status'], STATUSES, true)) $err[] = 'Choose a valid status.';
    return [$f, $err];
}

// ---- CV / resume uploads ----
function cv_path(string $file): string { return dirname(__DIR__) . '/storage/cvs/' . basename($file); }

// Checks an uploaded file and moves it into storage/cvs under a random name.
// Returns [stored name, original name, error message or null].
function store_cv(array $up): array {
    $big = 'That file is too big. Please send a file under ' . MAX_CV_MB . ' MB.';
    if (in_array($up['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $up['size'] > MAX_CV_MB * 1048576) return [null, null, $big];
    if ($up['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($up['tmp_name'])) return [null, null, 'The file did not upload. Please try again.'];
    $orig = trim(mb_substr(preg_replace('/[^\p{L}\p{N}._ -]/u', '', basename($up['name'])), 0, 120)) ?: 'resume';
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) return [null, null, 'Please send a PDF or Word file (.pdf, .doc or .docx).'];
    $head = (string)file_get_contents($up['tmp_name'], false, null, 0, 1024);   // the file's first bytes must match its type, whatever its name says
    $real = match ($ext) { 'pdf' => str_contains($head, '%PDF'), 'docx' => str_starts_with($head, "PK\x03\x04"), 'doc' => str_starts_with($head, "\xD0\xCF\x11\xE0") };
    if (!$real) return [null, null, 'That file does not look like a real PDF or Word document.'];
    $dir = dirname(__DIR__) . '/storage/cvs';
    if (!is_dir($dir) && !mkdir($dir, 0750, true)) return [null, null, 'We could not save your file. Please try again later.'];
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($up['tmp_name'], $dir . '/' . $name)) return [null, null, 'We could not save your file. Please try again later.'];
    return [$name, $orig, null];
}
