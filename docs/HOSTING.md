# Hosting your academy

Repository: https://github.com/aymanmagdy12/gegok12

## What is ready
This repository contains GegoK12, a Laravel application, not a static website.
GitHub stores the source. A PHP host runs the application.
The setup helper is preparation only; it has not been validated on a live host.

## Choose hosting
For simple administration, request managed PHP hosting with:
- PHP 8.4 or later, including the extensions listed in readme.md.
- MySQL, SSH, Composer 2, and Node/npm (upstream recommends Node 24).
- Ability to point the domain's document root to the repository's public directory.
- Persistent writable storage, including public/uploads and public/avatars.
- HTTPS, scheduled tasks, and off-server database/file backups.

Ask the provider to confirm every requirement before paying. A 4 GB VPS is
another starting option, but requires server administration. Registered user count
does not determine capacity; measure simultaneous activity before opening to everyone.
Use external video/live-class hosting.

## First installation: empty database only
1. Create an empty MySQL database and a dedicated database user.
2. Clone this repository in a directory outside the public web root.
3. Run `bash scripts/prepare-host.sh` as the deployment account, not root.
4. In the private .env set APP_ENV=production, APP_DEBUG=false, APP_URL to the
   HTTPS domain, TIMEZONE=Africa/Cairo, SESSION_SECURE_COOKIE=true, and the DB_*
   settings supplied by the host. Do not commit .env.
5. Give only the deployment/web-server accounts the access they need. The
   installer needs to write .env and run Composer/PHP; never use chmod 777.
6. Protect the entire staging website with hosting-level password protection
   or an IP allowlist before opening /installer/. Use the visual wizard to set
   the administrator account and complete installation.
7. The wizard runs fresh migrations and seeds. Never run it against an existing
   academy database. The default seed includes demo students and teachers:
   review/remove demo accounts and change credentials before admitting users.
8. Verify login, logout, password reset with configured SMTP, student/teacher
   permissions, uploads/downloads, and HTTPS. Confirm files survive a redeployment.
9. Remove public/installer after success and keep storage/installed.
   Exclude installer files from subsequent deployments so a git update does not
   reintroduce them. Do not expose the repository root, .env, or MySQL publicly.
10. Configure daily database AND file backups stored outside the server and
    test restoring them. Only then remove the staging access restriction.

## Editing
Use GitHub's editor for small text changes or Code > Codespaces for browser
development. Codespaces compute may incur charges. No production credentials or
real student data belong in a development workspace.
Use a branch and pull request; test on staging before updating the live website.

## Updating an installed application
Back up the database, .env, storage, public/uploads, public/avatars, and any other
runtime upload locations first. Never delete them when replacing code.
Deploy the reviewed commit through your host's Git deployment feature.
Run dependency installation and npm run production as in the helper.
If schema changes require it, run php artisan migrate --force after reviewing
the migrations. Never run migrate:fresh or the installer on a live database.
Clear/rebuild Laravel caches as required and verify login/uploads after updating.
Retain the previous code release and a matching database backup for rollback.

## Dependency reproducibility
Upstream ignores composer.lock and contains floating dependency versions.
After a successful installation in staging, retain the generated lockfile and
commit it intentionally (adjust .gitignore or use git add -f composer.lock).
Also commit the generated package-lock.json. Then use composer install and npm ci
for later deployments. Do not treat a successful asset build as a full app test.

## Remaining account setup
A hosting account, billing, and a domain/temporary host URL are still needed.
No live hosting resources or paid subscriptions are created by these files.
