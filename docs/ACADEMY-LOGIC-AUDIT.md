# Academy logic audit (2026-10-30)

Scope: the Laravel management system in a separate review branch based on 2f097e4. No production deployment, database reset, or branding redesign. This is not an audit of the separate public marketing/booking website. The local source snapshot is partial; repository tree and selected upstream files are also inspected. This document does not claim every legacy school module has been audited.

## Architecture and operation

PHP 8.4, Laravel 12, MySQL; Blade server-rendered academy pages alongside legacy Vue 3 components, Laravel Mix/Webpack and Tailwind styles. `app/Services` contains access policy, student journey, enrollment, checklists and teacher-workspace logic; `app/Http/Controllers/Academy` handles requests; `routes/academy.php` contains academy routes; `resources/views/academy` contains academy views; `database/migrations` contains additive tables; tests use isolated SQLite/MySQL fixtures.

Install with Composer, copy `.env.example` to a private `.env`, configure an isolated database, generate a key for a NEW installation only, run migrations, and serve `public/`. Frontend scripts are `npm run development` and `npm run production` (there is no `npm run build`, JavaScript linter or TypeScript checker). Existing Composer setup/dev scripts incorrectly assume a Vite/build setup. Existing preview uses Codespaces/Docker and PHP's development server; that is not permanent production hosting. Production hosting configuration and actual deployed code cannot be inferred from repository files alone.

## Flow inventory

Authentication/logout/reset password; admin registration of student/parent/teacher; placement and six-level/two-unit progress; parent linking/unlinking; group creation/edit/archive and teacher assignment; enrollment/transfer; teacher group roster; eight-session attendance/homework and archived sets; dated lesson registers; unit feedback and advancement; personal recordings/homework/report-card URLs; teacher leave requests/review/withdrawal; teacher attendance; demo-account preparation/test-only mode; activity audit; notices; legacy school modules and optional integrations. Native chat is explicitly being retired from the academy workflow at the owner's request, with existing data preserved. Public booking/contact/WhatsApp flows belong to a separate project and are not verified here.

## Findings recorded before fixes

| # | Severity | Location at baseline | Problem and impact |
|---|---|---|---|
| 1 | High | `app/Http/Controllers/Teacher/NoticeBoardController.php:46`, `Student/NoticeBoardController.php:27` | Ungrouped OR branches bypass academy/publication/audience boundaries; a family notice-board replacement must not reuse these queries. |
| 2 | High | `resources/views/academy/menu.blade.php:28-45` | Global child selector is forced on teachers and parents; private resources can be confusingly attached to the first/remembered child instead of the record visibly being read. |
| 3 | Medium | `resources/views/academy/dashboard.blade.php:20-25` | Family dashboard tells users to select a child elsewhere and omits child resources/progress context, creating an unnecessary cross-page dependency. |
| 4 | Medium | `resources/views/academy/menu.blade.php:4,14` | Parent has no notice-board destination; role map excludes parent. |
| 5 | Medium | `routes/academy.php:9-11`, academy dashboard/menu/group checklist | Retired chat remains a prominent action in all roles and contradicts the requested group/notice-board workflow. |
| 6 | Medium | `app/Http/Controllers/Academy/AcademyDashboardController.php:28` | Group schedule query omits timezone and inactive-group filtering; roster preview does not constrain current academic-year enrollment. |
| 7 | High | `routes/web.php:54` | Unauthenticated GET cache-clear mutates global application cache. |
| 8 | Medium | `composer.json:scripts.setup/dev` | Setup invokes nonexistent npm build; dev assumes Vite and a missing concurrently dependency instead of the actual Mix toolchain. |
| 9 | Medium | `app/Services/AcademyStudents.php:33,78` | New registration rejects ages 4–5 despite the current 4–15 requirement; profile edits apply a different age policy. |
| 10 | Medium | `resources/views/academy/group-checklist.blade.php:14` | Group checklist contains a separate select-student-in-sidebar action rather than directly opening that student's learning record. |

| 11 | High | `app/Http/Controllers/Admin/NoticeBoardController.php:update` | Notice update looks up only the notice ID, enabling modification across academies. |
| 12 | High | `app/Http/Requests/NoticeRequest.php`, `NoticeUpdateRequest.php` | Notice audience/group is not tenant-validated; non-English titles are rejected; invalid dates/types and oversize descriptions can reach storage. |
| 13 | Medium | `app/Http/Controllers/Admin/NoticeBoardController.php:store/update` | Exceptions are swallowed into an empty success-looking response; changing a class notice to a school notice leaves the old class ID and can make it disappear. |

| 14 | Medium | `resources/views/layouts/{teacher,student}/navigation.blade.php` | Missing optional school-logo/profile records cause null-property errors instead of rendering the dashboard fallback. |
| 15 | Medium | `app/Services/AcademyNavigation.php` | Legacy `?student=` links/session selection can break the dashboard and add unrelated student lookups to every teacher menu render. |

| 16 | High | `routes/admin.php:notice/delete`, `resources/assets/js/components/noticeboard/List.vue` | Notice deletion is a GET mutation and bypasses CSRF protection. |

Potential issues require evidence before changes: large legacy JavaScript bundle, optional addon routes, live URL/proxy configuration, SMTP credentials and real parent-account layout. No assumption that a development-server HTTP 200 proves the public tunnel or authenticated dashboard works.

## Verification boundaries

No credentials are printed or committed. Tests must run on isolated fixtures; no production mail, public notices or enrollment writes are authorized by this audit. Browser viewport/console and live SMTP verification require a runnable authenticated preview, and must be explicitly reported as unverified if unavailable. Existing CI's 86 passing tests are baseline evidence, not proof of the fixes in this branch.
