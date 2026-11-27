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
