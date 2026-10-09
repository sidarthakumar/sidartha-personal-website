# Task 4 — design and responsive refinement

Refines the existing implementation on `codex/personal-wordpress-website` and PR #2.
All development and browser requests used the isolated loopback sandbox. No production,
Hostinger, authentication-system, credential, or isolation changes were made.

## Changes

- Approved default headline: **Building practical technology. One project at a time.**
  The fuller introduction describes the actual beginning in IT and areas being explored.
  Both remain editable in Settings → Portfolio profile. Saved values take precedence;
  the sandbox had no saved headline/introduction, so no database update was needed.
- Clear primary **View projects** and secondary **About my journey** links lead to the
  existing Projects/About pages. Both have at least 48px height. At 320px they stack
  with equal widths; wider phones show a balanced pair.
- Compact horizontal profile card on phones/tablets retains the monogram, orbit
  illustration, name, description, and learning status. The redundant decorative
  hero footnote and static three-item technology strip were replaced with useful content.
- All six published, WordPress-editable skill groups now appear immediately after
  the hero. Their titles/topics/stages come from native Skills & Technologies records.
  The section label, heading, and introduction are new editable profile fields.
- Learning topics cover frontend/responsive work, backend/APIs/full-stack/databases,
  Python/AI/automation/data, cloud/hosting/DevOps/security, WordPress/ERP/CRM/SaaS/LMS,
  and SEO/performance/research. The editable section introduction also explicitly
  includes data engineering fundamentals. All current groups remain **Learning focus**.
- Phone body copy is generally 16px with comfortable line height; metadata, topic tags,
  profile labels and stage labels are at least 12px. Muted supporting labels have
  stronger contrast. Miniature lettering inside the original decorative browser artwork
  carries no important information and remains hidden from assistive technology.
- More consistent card spacing, heading sizes, wrapping, touch targets and tablet
  columns across existing pages. About's supporting information remains visible on phones.

No existing pages, posts, skill metadata, statuses, menus, or profile settings were
overwritten. No new plugin, tracker, font service, or production dependency was added.

## Measured hero improvement

Measurements are CSS pixels from the actual local browser, with navigation collapsed.
“Next section” is the top of the first content section after the hero, measured from
the top of the document. The expanded introduction is longer than the previous copy.

| Viewport | Card before → after | Next section before → after | Intro size before → after |
| --- | --- | --- | --- |
| 320px | 427 → 226 | 1244 → 981 | 14 → 16px |
| 360px | 427 → 204 | 1165 → 820 | 14 → 16px |
| 390px | 427 → 192 | 1166 → 813 | 14 → 16px |
| 430px | 427 → 192 | 1158 → 770 | 14 → 16px |
| 768px | 395 → 216 | 745 → 775 | 13 → 17px |
| 1440px | 476 → 489 | 853 → 726 | 16 → 17px |

The tablet introduction is now a readable single-column composition; its first
section starts 30px lower despite a much smaller card. Phone cards are 47–55% shorter.
At the narrowest width the fuller introduction and stacked actions still require
scrolling; no important text was hidden to meet an artificial height target.

## Actual verification

- **PASS — 49 page/viewport checks:** all seven public pages at **320, 360, 390, 430,
  768, 1024 and 1440px**. No horizontal overflow; correct page status, headings,
  readable hero sizes, six learning groups, practical action targets and local links.
- **PASS — accessibility:** axe-core 4.10.3 reported zero violations for WCAG 2 A/AA,
  WCAG 2.1 A/AA and best practices in those cases. Mobile/tablet menu keyboard entry,
  Escape, focus indicator and navigation access checked at every relevant width.
  Existing skip link, focus return, reduced motion and no-JavaScript navigation pass.
- **PASS — existing interactions/security:** project search/status filters, malformed
  query arrays, reflected input, detail pages, anonymous draft privacy rejection, real
  404s, and no exposed administrator email. No frontend JavaScript errors or external
  browser requests observed.
- **PASS — 21 regression tests:** final run 0.052 seconds; authentication failure,
  redirects, timeouts, diagnostics and performance classification retained.
- **PASS — 45 native integration/security assertions:** existing 41 assertions plus
  owner-copy precedence, editable/sanitized learning settings, no settings writes from
  validation/default reads, and successful test-write rollback.
- **PASS — HTTP security/native controls:** unauthorized REST creation, missing/invalid
  REST nonce, invalid import nonce, native profile/media UI and editable introduction/
  learning fields. No settings were saved by browser inspection.
- **PASS — syntax:** all 17 theme/plugin PHP files; both JavaScript files; Python tools.
  Git whitespace checks, secret scans, ignore-rule checks and source-only ZIP checks pass.

### Smoke timings

The saved startup procedure passed before refinement; the complete smoke suite passed
again on the final implementation. These are a startup run and a final verification run,
not two new restart cycles. No runtime configuration or environment change was needed.

| Check | Saved startup | Final suite |
| --- | ---: | ---: |
| PHP / mysqli | 0.176s | 0.257s |
| MariaDB | 0.148s | 0.254s |
| WP-CLI | 1.506s | 1.881s |
| WordPress | 0.816s | 1.880s |
| Direct database query and local boundary | 1.178s | 1.795s |
| Homepage (includes WP-CLI title lookup) | 1.147s | 1.381s |
| REST | 0.039s | 0.028s |
| Administrator authentication | 0.117s | 0.108s |
| First dashboard | 0.052s | 0.046s |
| Repeat dashboard | 0.059s | 0.043s |
| Invalid authentication rejection | 0.138s | 0.118s |
| **Complete suite** | **5.384s** | **7.801s** |

### Preservation and security

Private before/after hashes match for all rows in the nine posts/metadata/users/
comments/taxonomy tables and the saved profile option. The existing `.env`, Compose
configuration, startup helper and `wp-config.php` hashes also match. No existing
content or account records were overwritten. Normal authentication may update session
metadata and transients, as documented by the smoke suite.

Integration writes now run inside a transaction with explicit rollback and a shutdown
fallback. The suite refuses non-transactional tables or persistent object caches.
This protection is development tooling only; it is not shipped in theme/plugin ZIPs.
No production database backup or connection was involved.

Secret checks cover tracked/new files and decompressed ZIP members, exact known local
secret matching without printing values, token/private-key/dump patterns, sensitive
paths, and `.gitignore` behavior. No sensitive material was found. The synthetic
negative-input examples in tests are intentional and never sent externally.

## New screenshots and packages

All images below are newly captured from the actual refined local website, with no
administrator session. The older Task 3 screenshots are historical, not evidence here.

- [320px homepage](previews/task4/home-320-viewport.png)
- [360px homepage](previews/task4/home-360-viewport.png)
- [390px homepage](previews/task4/home-390-viewport.png) · [full page](previews/task4/home-390.png)
- [430px homepage](previews/task4/home-430-viewport.png)
- [768px tablet](previews/task4/home-768-viewport.png)
- [1024px desktop](previews/task4/home-1024-viewport.png)
- [1440px desktop](previews/task4/home-1440-viewport.png) · [full page](previews/task4/home-1440.png)
- [About](previews/task4/about-desktop.png) · [Projects](previews/task4/projects-desktop.png)
- [Raw final measurements/results](previews/task4/results.json) · [before measurements](previews/task4/before-layout.json)

Current packages: `dist/sidartha-1.0.1.zip` and `dist/sidartha-portfolio-1.0.1.zip`.
The theme includes a newly captured 1200 × 900 screenshot. The manifest contains current
SHA-256 checksums. ZIP contents match the reviewed source; old 1.0.0 archives are
retained unchanged. No database, runtime configuration, credentials or backups are shipped.

## Review limits and rollback

Ready for owner design review; PR #2 remains unmerged. No Hostinger or production
verification/deployment, email delivery, physical-device testing, Safari/Firefox,
screen-reader testing, minimum-version matrix or full automated WPCS audit is claimed.
The existing owner launch requirements and privacy draft still apply.

Rollback needs only the prior reviewed theme/plugin code or retained 1.0.0 packages;
no database restoration is required because this refinement makes no content migration.
Review newer changes before replacing files. Do not reset/delete runtime data.
