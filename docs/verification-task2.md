# Task 2 verification — 9 October 2026

This report records the state at Task 2 completion, before the subsequent
commit and pull-request review.

Scope: the existing isolated Codex Cloud sandbox only. No production access,
deployment, theme/plugin implementation, commits, or pushes were performed.

## Result

Ready for local theme/plugin development, subject to the next user instruction.
Two complete final suites passed after separate process restart/startup cycles.
All nine checked content/user tables retained their original row counts and hashes.
Normal authentication sessions and WordPress transient metadata are outside that
comparison and may change during ordinary requests. No reset or data restore occurred.

## Cause and correction

The original 10-second test limit was too tight for two sequential optional
WordPress.org calls. The delay was initially intermittent, then reproduced at
10.145 seconds on the first dashboard GET and 10.160 seconds on repeat. The new
performance check correctly failed that run rather than masking it with a larger
timeout. A trace inside the web container confirmed:

| Dashboard dependency | Observed duration | Outcome |
| --- | ---: | --- |
| Browser-version API check | 5.005s | Transport timeout |
| PHP-version API check | 5.003s | Transport timeout |

The private local Compose configuration now sets `WP_HTTP_BLOCK_EXTERNAL=true`,
with no external host exceptions, and the saved installer template matches it.
The checks fail immediately under that policy instead of exhausting network
timeouts. Local WordPress HTTP requests remain permitted. This setting deliberately
disables external update checks/downloads and integrations in this sandbox; it
does not certify installed software as current or replace the managed firewall.

The canonical smoke script separately verifies authentication, then two dashboard
GETs. Each dashboard operation has a 30-second total deadline; a repeat above
10 seconds fails the performance gate. Wrong credentials, missing cookies,
unexpected redirects/status/content, transport failures, and malformed REST data
also fail. There are no silent retries or credential resets.

## Final measured results

Each cycle used `docker compose restart db web` followed by the saved `start.sh`,
with the local Docker socket selected explicitly. Cycle 1 also applied the reviewed
Compose change by recreating the web container with its existing bind mount.
Durations below are measured check times, not total container restart time.

| Check | Final cycle 1 | Final cycle 2 | Result |
| --- | ---: | ---: | --- |
| PHP + mysqli | 0.175s | 0.183s | PASS |
| MariaDB client | 0.174s | 0.141s | PASS |
| WP-CLI | 2.406s | 0.979s | PASS |
| WordPress | 1.234s | 0.943s | PASS |
| Database, local URLs, HTTP isolation | 1.427s | 1.211s | PASS |
| Homepage | 0.781s | 0.475s | PASS |
| REST API | 0.035s | 0.031s | PASS |
| Administrator authentication | 0.112s | 0.113s | PASS |
| Dashboard first GET | 0.129s | 0.122s | PASS |
| Dashboard repeat GET | 0.063s | 0.063s | PASS |
| Dashboard performance gate | <0.001s | <0.001s | PASS |
| Wrong credentials + anonymous access rejected | 0.153s | 0.127s | PASS |
| Complete suite | **6.705s** | **4.401s** | **PASS** |

Observed versions: PHP 8.3.35, MariaDB client/server 11.4.13, WordPress 7.1.3,
WP-CLI 2.12.0. The database check executes `SELECT 1` and confirms the existing
synthetic administrator retains `manage_options`.

Tooling validation: 20 regression tests passed. Python compilation, shell syntax
checks for the saved helpers, and Git whitespace checks passed. The regression
tests cover authentication failures, unsafe redirects, stalled operations,
missing HTTP isolation, redacted errors, and performance classification.

## Preservation and security checks

- Existing credentials, `start.sh`, and `wp-config.php` are byte-for-byte unchanged.
  Compose differs only by the intended external HTTP blocking setting.
- The original helper/configuration versions were copied to private timestamped
  backups outside Git and outside the web document root. Installer templates and
  the actual Compose/start/smoke helpers agree.
- Existing posts, post metadata, users (including password hashes), comments,
  comment metadata, terms, term metadata, taxonomy, and term relationships retained
  identical hashes and row counts. No database dump was created.
- WordPress is published only on `127.0.0.1:8080`; MariaDB has no published host port.
  Runtime/data/backup directories use `0700`; `.env` uses `0600`.
- `.gitignore` passed 37 sensitive-path/allowed-source cases, including database
  exports, credential directories, runtime data, and backups. Theme/plugin source,
  placeholder examples, and ordinary installable theme/plugin ZIPs remain trackable.
- Source files and available Git-history blobs were checked for exact known local
  secret values and common token/private-key markers without displaying values.
  No matches were found. No private runtime/configuration/data was added to Git.

## Review state and limitations

Intended repository changes: `.gitignore`, `AGENTS.md`, `README.md`,
`docs/development.md`, this report, `tools/dev/smoke.py`, and
`tools/dev/test_smoke.py`. At Task 2 completion, these seven files were saved
locally, pending Codex change review, and had not been committed or pushed.

Private runtime changes: `smoke.py` delegates to the canonical repository source;
`compose.yaml` enables external HTTP isolation; `install.sh` embeds the matching
adapter and Compose setting. These private files are not repository deliverables.
Rollback and restoration instructions are in `docs/development.md`.

No remaining blocker was found for local development. External integrations and
software-update downloads need a separately reviewed development workflow because
they are intentionally blocked. Production access/deployment remains prohibited.
