# Website verification — October 9, 2026

Scope: the existing isolated WordPress sandbox at `http://127.0.0.1:8080`.
No production/Hostinger access, deployment, merge, account reset, or data deletion.
The feature branch starts from `main` including merged environment PR #1.

## Environment and repeated startup

Both cycles restarted only the existing `db` and `web` processes, then ran the saved
`start.sh` procedure and complete smoke suite. Values below are actual seconds,
not synthetic benchmarks. The final plugin date-validation change subsequently
passed integration tests; it does not change startup or authentication behavior.

| Check | Cycle 1 | Cycle 2 | Result |
| --- | ---: | ---: | --- |
| PHP 8.3.35 / mysqli | 0.170 | 0.195 | PASS |
| MariaDB client 11.4.13 | 0.134 | 0.231 | PASS |
| WP-CLI 2.12.0 | 1.265 | 1.735 | PASS |
| WordPress 7.1.3 | 1.100 | 1.255 | PASS |
| Direct database query / local boundary | 1.453 | 1.628 | PASS |
| Homepage / current site title | 1.498 | 1.512 | PASS |
| REST API | 0.033 | 0.035 | PASS |
| Administrator authentication | 0.111 | 0.112 | PASS |
| First authenticated dashboard | 0.107 | 0.114 | PASS |
| Repeated authenticated dashboard | 0.094 | 0.047 | PASS |
| Incorrect credentials / anonymous rejection | 0.117 | 0.129 | PASS |
| Complete suite | **6.093** | **7.007** | **PASS** |

Homepage timing includes querying the configured title through WP-CLI; it is not a
standalone HTTP latency measurement. Both dashboard requests were below the
10-second performance budget. The earlier optional outbound-check timeout remains
resolved by the existing local HTTP isolation documented in `development.md`.
The suite retains its bounded deadlines and failure behavior.

## Application and security checks

- **PASS:** PHP syntax checks for all 17 theme/plugin PHP files; JavaScript syntax
  checks for frontend/admin scripts; Python tooling compilation.
- **PASS:** 21 smoke-tool regression tests, final run 0.046 seconds. Includes
  bad credentials, unexpected responses/redirects, timeout failures, safe diagnostics,
  and first-request-versus-persistent performance classification.
- **PASS:** 41 native WordPress integration/security assertions. Covers active theme
  and plugin, seven published local pages, draft privacy page, idempotent starter
  import, native content creation/editing, anonymous and authenticated read-only
  permission rejection, nonces, REST schemas, private verification notes, unsafe
  URLs, invalid/null-byte dates, media/category input, administrator-email rejection,
  honest completion labels, and corresponding status-filter results. The read-only principal is simulated in memory;
  no account role is changed. The reusable QA project remains an unpublished draft.
- **PASS:** actual HTTP anonymous REST creation, missing/invalid REST nonces, and
  invalid starter-import nonce are rejected. Authenticated native profile settings
  and the media chooser open correctly without changing settings.
- **PASS:** 21 page/viewport checks: Home, About, Skills, Projects, Journey, Insights,
  Contact at 1440, 820, and 390 pixels. All returned 200, with one main H1, consistent
  heading levels, no horizontal overflow, and no administrator email or filler text.
- **PASS:** axe-core 4.10.3 checks for WCAG 2 A/AA, WCAG 2.1 A/AA and best practices
  reported zero violations across those 21 cases. This is automated evidence, not
  a certification or proof of full accessibility conformance.
- **PASS:** keyboard skip link, mobile menu open/close/Escape/focus restoration,
  reduced motion, no-JavaScript navigation, search/status filters, empty results,
  malformed query arrays, reflected-input handling, article/project detail pages,
  draft privacy rejection, and true 404 responses. No frontend JavaScript errors
  or non-loopback requests were observed.

## Preservation, Git, and packaging

Before activation/import, a private database backup and settings/content snapshot
were saved outside Git and the document root. Original post/page IDs and their
title/content/type/status hashes remain unchanged. Original user/password records,
comments and comment metadata are unchanged. Existing `.env`, `start.sh`, and
`wp-config.php` hashes are unchanged. New portfolio records and intentional local
Reading/title/permalink/menu settings are documented in the rollback guide.

Git review includes whitespace checks, explicit staging, suspicious-path checks,
known local-secret matching without printing values, credential/token/private-key
patterns, database-dump signatures, and inspection of ZIP members. `.gitignore`
protects local environment, database, private configuration, logs, keys and backups
while retaining legitimate theme/plugin source and installable packages.
Negative tests contain visibly synthetic `example.test` / `example.invalid`
fixtures; these are not actual credentials or external requests.

The ZIP builder verifies archive integrity, safe component roots, and byte-for-byte
source equality. The theme contains 22 files, including its real local screenshot;
the plugin contains 6 files. Checksums and sizes are in `dist/manifest.json`.
Packages contain no runtime configuration, databases, uploads, test fixtures, or
backups. Activation was tested using those same source files in the existing
sandbox; uploading ZIPs through a real hosting account was not performed.

## Visual previews

Actual local Chromium screenshots, without an administrator session:

- [Desktop homepage](previews/home-desktop-viewport.png)
- [Mobile homepage](previews/home-390.png)
- [Projects](previews/projects-desktop.png)
- [About](previews/about-desktop.png)

Desktop and mobile screenshots were visually reviewed in addition to automated
layout checks. The installable theme includes a 1200 × 900 native theme screenshot.

## Limits and launch requirements

The implementation is ready for owner review and continued local development.
Public launch still requires approved copy, a separate public contact address,
optional portrait/public profile links, and a reviewed privacy notice. No real
email delivery, Hostinger account, production TLS, production plugin combination,
real-device Safari/Firefox behavior, screen reader, or backup restore was tested.
The declared minimum WordPress/PHP versions were not exercised in a version matrix.
No WordPress Coding Standards runner is configured; PHP syntax and behavior checks
are not represented as a complete automated coding-standards audit.

See `website-security.md` for trust boundaries and `website-deployment.md` for
owner inputs and future authorized staging/rollback procedures. No production
readiness claim or automatic release is implied by these local results.
