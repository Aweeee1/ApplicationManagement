<?php
// ---- Edit these for your server ----
const DB_HOST = '127.0.0.1';
const DB_NAME = 'la_rose_noire';
const DB_USER = 'root';
const DB_PASS = '';

const APP_NAME   = 'La Rose Noire';
const IDLE_LIMIT = 900; // 15 minutes (seconds)
// ---- Security settings ----
const SIGNUP_OPEN       = true; // true = anyone can ask for an account (an admin must approve it). false = admins create accounts.
const MAX_ATTEMPTS      = 5;    // wrong sign-ins for one email before it is locked for a while
const LOCK_MINUTES      = 15;   // how long the lock lasts
const SESSION_MAX_HOURS = 8;    // after this long everyone signs in again, even if active
const SETUP_KEY         = '';   // optional: if you type a secret here, the very first (administrator) sign-up must enter it. Stops strangers claiming a new install.
const MIN_PASSWORD      = 10;   // minimum password length (length matters more than symbols)

const STATUSES   = ['Pending', 'Reviewing', 'Interview', 'Approved', 'Rejected'];
const POSITIONS  = ['Front of House', 'Kitchen Team', 'Events Coordinator', 'Marketing Associate']; // shown on the public page

// URL path of this project, so links work in any sub-folder (e.g. /la-rose-noire)
$__doc  = str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$__root = str_replace('\\', '/', (string)realpath(dirname(__DIR__)));
define('BASE_URL', $__doc !== '' && str_starts_with($__root, $__doc) ? rtrim(substr($__root, strlen($__doc)), '/') : '');
