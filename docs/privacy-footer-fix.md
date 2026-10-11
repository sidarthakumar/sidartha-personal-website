# Privacy footer fix — theme 1.0.2

The former footer checked only the companion plugin's original privacy-page mapping.
A different published page selected in WordPress Settings → Privacy was ignored.

The footer now calls `get_privacy_policy_url()` first. WordPress checks that the
selected policy is published. The footer renders an escaped, clickable **Privacy
Policy** link for that URL. If WordPress has no published selected policy, the
previously mapped starter page is considered only when its status is `publish`.
If neither qualifies, the existing **Privacy notice under review** text remains
non-clickable. No policy is published or selected by the theme.

Footer classes, markup structure, CSS, JavaScript and responsive design are retained.
The companion plugin source and version are unchanged. The theme version is 1.0.2.

## Changed files

- `wp-content/themes/sidartha/footer.php`: official API, published-only fallback,
  escaped URL and Privacy Policy label.
- `wp-content/themes/sidartha/style.css`: patch version 1.0.2.
- `wp-content/themes/sidartha/readme.txt`: version and privacy behavior notes.
- `tools/dev/test-website.php`: six actual-footer regression cases.
- `tools/build-packages.py`: builds the new theme filename with unchanged plugin 1.0.1.
- `dist/sidartha-1.0.2.zip`: new installable theme; 22 approved source/asset files.
- `dist/manifest.json`: updated theme checksum/size; plugin entry unchanged.
- `docs/website-installation.md`: current package versions and theme-only update guidance.
- `docs/website-deployment.md`: approved future staging verification and code rollback.
- `docs/content-editing.md`: correct Settings → Privacy selection instructions.
- `README.md`: package versions and link to this report.
- `docs/privacy-footer-fix.md`: this verification record.

## Verification on October 11, 2026

All checks used the isolated development WordPress sandbox. No Hostinger, production
website or owner staging connection was made.

| Check | Result |
| --- | --- |
| Smoke-tool regression suite | PASS — 21 tests, 0.081s |
| Native WordPress integration/security suite | PASS — 51 assertions |
| New footer-specific cases | PASS — all 6 |
| Actual HTTP security and native editor controls | PASS |
| Responsive/axe checks, 7 pages × 7 widths | PASS — 49 cases, zero reported violations |
| Navigation, keyboard focus, reduced motion and existing interactions | PASS |
| Theme/plugin PHP syntax | PASS — all 17 component PHP files |
| Existing JavaScript syntax and Python compilation | PASS |
| Git whitespace check | PASS |
| Package integrity, source equality and manifest checksums | PASS |
| Known-secret/token/private-key/dump scans and ignore rules | PASS |
| Existing content/account/settings/private configuration preservation | PASS |

The six footer cases render the actual theme template inside the existing database
rollback transaction:

1. Official published policy wins over a different published starter policy.
2. Official published policy links correctly while the starter policy is a draft.
3. Selected draft plus starter draft produces review text and no policy link.
4. No official selection plus starter draft also keeps the safe text fallback.
5. A published starter policy remains a clickable legacy fallback.
6. The official published policy works without any plugin privacy mapping.

Synthetic fixture creation/status/option changes are rolled back, including on test
failure. Before/after hashes match all existing posts, metadata, users, comments,
taxonomies and the relevant settings, including `wp_page_for_privacy_policy` and
`skp_pages`. Private `.env`, Compose, startup helper and `wp-config.php` hashes match.
Ordinary login/session/transient metadata may change during authentication checks.

### Smoke timings

The saved startup suite passed in **7.782s**. After the footer change, the complete
suite passed in **9.593s**:

| Final check | Seconds |
| --- | ---: |
| PHP / mysqli | 0.216 |
| MariaDB | 0.145 |
| WP-CLI | 2.099 |
| WordPress | 2.342 |
| Database and local boundary | 2.209 |
| Homepage, including WP-CLI title lookup | 1.955 |
| REST API | 0.043 |
| Administrator authentication | 0.282 |
| First dashboard | 0.063 |
| Repeat dashboard | 0.057 |
| Invalid authentication rejection | 0.167 |

No runtime configuration changes or new restart stress cycles were required.
Browser checks covered 320, 360, 390, 430, 768, 1024 and 1440px. The published-policy
cases were verified through real WordPress template rendering in the transaction;
normal browser checks retained the sandbox's original unpublished policy state.
Automated accessibility is evidence, not a full screen-reader/conformance audit.

## Package and review status

Exact theme ZIP: **`sidartha-1.0.2.zip`**, under `dist/`.

SHA-256: `c6ec393c8da9375c952d277ee20c585961bd78d0acdf27ce1b9e9d42b1f178f4`

Size: 231,118 bytes. All archive members match source. No database, development
configuration, credential, backup or regression fixture is shipped. Older theme
ZIPs and the companion plugin ZIP remain unchanged.

Review branch: `codex/personal-wordpress-website`, associated with existing
PR #2. The owner authorized committing and pushing the reviewed source and theme
package to that branch. No deployment or merge is authorized. Installing on the owner's staging site is a separate
authorized action; see the deployment guide. This report does not claim the actual
staging link was tested. No database migration or policy/content/settings change
is needed to apply the theme patch.
