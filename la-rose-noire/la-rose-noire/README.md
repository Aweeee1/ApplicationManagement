# La Rose Noire – Application Management System

PHP 8+, MySQL/MariaDB, Bootstrap 5.3, vanilla JavaScript.

## Run it (XAMPP/Laragon)
1. Copy this folder into `htdocs` (or `www`).
2. Start Apache and MySQL.
3. Edit `connection/config.php` if your MySQL user/password differ (default: root / no password).
4. Open `http://localhost/la-rose-noire/`. The database and tables are created automatically.
5. Open **Sign up** and create the first account. The first account becomes the **admin**.

## Folder structure
| Folder | Purpose |
|---|---|
| `index.php` | Public page (open roles + public application form) |
| `auth/` | `login.php`, `register.php`, `logout.php`, `session.php` (session, idle timeout, access guards) |
| `pages/` | `dashboard.php`, `applications.php`, `users.php`, `audit-logs.php` |
| `api/` | JSON endpoints: `applications.php`, `users.php`, `apply.php` (public), `ping.php` |
| `components/` | `header.php`, `footer.php` |
| `connection/` | `config.php` (settings), `db.php` (PDO + auto-created tables) |
| `services/` | `helpers.php`, `audit.php`, `applications.php` (validation) |
| `scripts/` | `app.js` (theme + idle logout), `crud.js` (modals, fetch, deep links) |
| `styles/` | `style.css` |
| `assets/` | `img/logo.svg` |

## System requirements → where they live
| Requirement | Files |
|---|---|
| Public page | `index.php`, `api/apply.php` |
| Login / Signup | `auth/login.php`, `auth/register.php`, `auth/logout.php` |
| Dashboard | `pages/dashboard.php` (clickable cards, bars, lists) |
| CRUD | `pages/applications.php`, `api/applications.php` |
| Audit logs | `services/audit.php`, `pages/audit-logs.php` |
| User management | `pages/users.php`, `api/users.php` |

## Roles
- **Admin:** everything, including Users, Audit logs, and deleting applications.
- **Staff:** dashboard, create/edit applications.

## Security (sign in and sign up)
| Protection | How it works |
|---|---|
| Lockout | 5 wrong passwords for one email pauses sign-in for 15 minutes (a wider limit applies per device). Stored in the database, so clearing cookies does not reset it. Change `MAX_ATTEMPTS` / `LOCK_MINUTES` in `connection/config.php`. |
| Account approval | After the first administrator, new sign-ups are **pending** until an admin approves them on the Users page. Set `SIGNUP_OPEN` to `false` to turn public sign-up off. |
| Passwords | At least 10 characters (`MIN_PASSWORD`), common and guessable passwords refused, no name or email inside the password, bcrypt cost 12, old hashes upgraded at sign-in. A show/hide button and strength meter help people type them correctly. |
| No clues for attackers | Same error for a wrong email or a wrong password, same thank-you message at sign-up whether or not the email exists, and the same response time. |
| Sessions | New session id after sign-in, `HttpOnly` + `SameSite` + `Secure` (on HTTPS) cookies, 15-minute idle limit, 8-hour absolute limit (`SESSION_MAX_HOURS`). |
| Setup key (optional) | Set `SETUP_KEY` in `connection/config.php` so only you can create the first administrator account on a new install. |
| Warnings | The sign-in page says how many tries are left before a pause. The dashboard shows your last sign-in time and warns if wrong passwords were tried on your account. |
| Bot protection | Hidden trap field, a minimum fill-in time, and a per-device limit on sign-up requests and public applications. |
| Browser headers | Content-Security-Policy with a per-page nonce, no framing, no MIME sniffing, no-store caching on private pages, HSTS on HTTPS. |

**Before going live:** serve the site over HTTPS, change the MySQL root password in `connection/config.php`, and give the database user only the rights it needs.

## Easy-to-use design
Larger text (with an A / A / A size switch), big buttons and fields, strong contrast, labels on every field, a skip link, visible focus outlines, and icons next to words. Animations are gentle and switch off automatically for people who ask their device for reduced motion.


## Round 2 changes
- Status badges now show an icon and a word, so color is never the only clue.
- Floating petals on the public page, sections that fade in as you scroll, an animated tick on success messages, and a live checklist under the new-password field.
- Cookie `SameSite` is now `Strict`; the password blocklist is larger.
- The display button is now labelled **Text size**, which is easier to find.

## Round 3 changes
- **Fixed:** the create/edit pop-ups froze because of a page fade-in animation added in round 2. It is removed, and pop-ups now sit directly under `<body>` so nothing can cover them.
- **Customize panel (signed-in users only):** text size (4 steps), light/dark/match device, high contrast, lettering, spacing, corner style, fewer animations, and 8 accent colors. Settings are saved to the user's account (`users.prefs`, added automatically) and follow them to any device. Visitors and the sign-in/sign-up pages always use the default design.
- **Spacing:** more room in cards, tables, forms, pop-ups and the menu bar, with an Extra roomy option.

## Round 4: careers page
The public page now shows a dark careers banner, search, department filters, a job list and a detail panel, with **Apply now** opening the application form for that role. Edit the roles in `connection/jobs.php` (the Quality Control and Supply Chain roles are placeholders). On phones, choosing a role opens its details full-screen with a Back button.

## Round 5: admin look
The dashboard, applications, users and audit pages now use the same dark panels, blush-pink accent and small uppercase labels as the careers page. The default font is **Inter** (a clean, highly legible interface font); **Easy-read** (Atkinson Hyperlegible) is still available in Customize. New default: dark theme with the Blush accent. Sign-in and sign-up pages are dark too. Anyone who already saved their own settings keeps them, except that a saved font choice resets to Clean.

## Round 6: CV / resume upload
- The public application form asks for a **CV or resume** (PDF, .doc or .docx, up to `MAX_CV_MB`, default 2 MB). Set `CV_REQUIRED` to `false` to make it optional.
- Each file is checked by size, name and its real file contents, then saved under a random name in `storage/cvs/`. `storage/.htaccess` blocks direct web access (on Nginx, add a rule denying `/storage/`). Staff download files through **api/cv.php**, which needs sign-in and is recorded in the audit log.
- Staff see a **CV** button on each application. Deleting an application deletes its file.
- To allow bigger files, raise `upload_max_filesize` and `post_max_size` in `php.ini`, then `MAX_CV_MB`.

## Round 7: secret key + Request help
- When someone applies on the careers page, they are shown a **secret key** (like `ABCD-EFGH-JKLM-NPQR`) **once**, with a warning to save it. They can copy it or save it as a text file, and must tick "I have saved my secret key" before closing the window.
- The careers page has a **Request help** button. The applicant enters their key, sees their application, and can change their name, email, phone, role, notes and CV. Changes are saved to the `applications` table, so they show on the admin Applications page straight away with an **Edited by applicant** badge, and an **Applicant updated details** entry is added to the audit log. Applicants can never change their status.
- The key is valid for `SECRET_KEY_DAYS` (7) days after applying, and can be used as many times as needed until then. After that it is refused with a clear message.
- Authentication: the database stores only a SHA-256 hash of the key (`applications.secret_hash`) and its expiry (`secret_expires_at`); the key itself is never stored, so it cannot be shown again or recovered by anyone, including admins. Wrong keys are limited to 10 per device per 15 minutes. The hash is never loaded into the admin pages.
- Only applications made through the public form get a key. Applications added by staff, or made before this update, have none.
- Existing databases upgrade themselves on the next page load (two new columns on `applications`). If you tried the earlier email-link version, you can remove its leftover table with `DROP TABLE IF EXISTS application_tokens;`.
