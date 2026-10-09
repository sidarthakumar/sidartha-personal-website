# Isolated WordPress development

This repository is source and documentation only. The existing private runtime
is `/workspace/local-wordpress`, outside the Git repository. Production access
and deployment are prohibited for this workflow. See `AGENTS.md`.

## Existing sandbox

- Project: `sidartha-wordpress-dev`, using local Docker Compose.
- HTTP: `http://127.0.0.1:8080`; MariaDB has no published host port.
- Saved helpers: `install.sh`, `start.sh`, `compose.yaml`, and `smoke.py` in the
  private runtime directory. Do not copy `.env`, expanded Compose output, or data
  into this repository.
- Existing data: `data/db` and `data/wordpress` bind mounts. Preserve both.
- Credentials: the existing private `.env`, mode `0600`, inside a `0700` directory.
  Never print it, pass passwords on command lines, or change/reset credentials to
  make a smoke test pass. Production credentials must never enter Codex prompts.
- Images are pinned by digest in the saved Compose configuration. The current
  runtime provides PHP 8.3.35, MariaDB 11.4.13, WordPress 7.1.3, and WP-CLI 2.12.0.
  These are observed versions, not a claim that they are the latest secure versions.
- The local `WORDPRESS_CONFIG_EXTRA` retains `WP_ENVIRONMENT_TYPE=local` and adds
  `define('WP_HTTP_BLOCK_EXTERNAL', true);`. There are no `WP_ACCESSIBLE_HOSTS`
  exceptions. The existing `wp-config.php` evaluates this container configuration;
  no core file or database setting is modified. Both the web and CLI services
  inherit it from the saved Compose environment anchor.
- This blocks external calls through the WordPress HTTP API immediately, while
  WordPress permits calls to localhost/its own site host. It is not a general
  firewall for arbitrary PHP/socket code; the managed network policy still applies.
  External update checks/downloads and integrations are unavailable here. Review
  dependencies through a separate authorized development update workflow; never
  present the absence of update notices as proof that software is current.

## Start and verify

Read the Cloud environment runtime instructions and inspect the supported network
policy first. Confirm that the selected Docker daemon is the local managed socket.
Keep inherited proxy, CA trust, and registry settings. Do not use remote contexts.

```sh
cd /workspace/local-wordpress
env -u DOCKER_CONTEXT -u DOCKER_TLS -u DOCKER_TLS_VERIFY -u DOCKER_CERT_PATH \
  DOCKER_HOST=unix:///var/run/docker.sock bash start.sh
```

The saved helper starts the existing database/web services and runs `smoke.py`.
Its legacy installation branch must not be used as recovery for an ambiguous
database error: if installation status fails, diagnose connectivity first. Do not
reinstall or reset an existing database. This task uses an installed database.

The local `smoke.py` is a small adapter to the canonical source at
`/workspace/sidartha-personal-website/tools/dev/smoke.py`. The saved installer's
embedded adapter matches it, so its helper-consistency check remains valid.
Run the suite directly without startup when services are already running:

```sh
python3 /workspace/sidartha-personal-website/tools/dev/smoke.py
```

The suite checks versions, PHP mysqli, a direct `SELECT 1`, local URLs and
environment, external HTTP isolation, the synthetic administrator capability, homepage content, REST
content, administrator cookies and dashboard HTML, and rejection of an incorrect
password and anonymous dashboard access. Its explicit fixture is the existing
`Hello world!` post. The homepage assertion now reads the configured site title,
so activating the personal website does not require retaining synthetic branding.
If intentionally changing fixtures later, update the assertions through review;
never reset content to satisfy the test.

Each check prints PASS/FAIL and elapsed seconds. Commands and ordinary HTTP
operations have 30-second and 10-second limits respectively; each dashboard GET
has a 30-second total deadline including body reads. Responses are size-bounded.
Redirects are never followed automatically, and login redirects must point to the
exact local dashboard. Failures exit nonzero without printing raw responses,
cookies, payloads, secret values, or arbitrary command/exception output. The suite
runs synchronously on Linux (the total HTTP deadline uses POSIX `SIGALRM`).

Both the first and repeat authenticated dashboard GETs are measured separately:

- Both at or below 10 seconds: normal performance.
- First above 10 seconds, repeat at or below 10: explicitly report a recovered
  first-request delay, consistent with a cold start; this is not proof of its cause.
- Repeat above 10 seconds: fail for persistent or intermittent slowness, even if
  authentication succeeded inside 30 seconds.
- Any timeout or unexpected response: fail immediately; do not hide it with retries.

One deliberate wrong-password attempt uses the existing synthetic development
administrator per suite run. No password/user updates are performed. Reassess this
negative test before adding a lockout/security plugin; never disable protections
or repeatedly retry a locked account to get a passing result. Normal login session
metadata and WordPress transients may change as a result of ordinary requests.

## Repeated startup validation

For each of two cycles, restart existing processes without removing containers,
bind mounts, or data; then run the saved startup procedure above. A restart is a
process-cold cycle, not an empty database or cleared WordPress cache.

```sh
cd /workspace/local-wordpress
env -u DOCKER_CONTEXT -u DOCKER_TLS -u DOCKER_TLS_VERIFY -u DOCKER_CERT_PATH \
  DOCKER_HOST=unix:///var/run/docker.sock docker compose restart db web
# Then run the saved start.sh command above; record its full timed test summary.
```

Run tooling regression checks from the source repository:

```sh
cd /workspace/sidartha-personal-website
python3 -m unittest discover -s tools/dev -p 'test_*.py' -v
git diff --check
git status --short
```

The regression tests exercise wrong credentials, missing cookies, unexpected
content/status/redirects, deadline expiry, safe diagnostics, malformed REST data,
and cold-versus-persistent performance classification without contacting any site.

## Timeout investigation

The previous test combined login and a redirected dashboard GET under a 10-second
timeout. Authentication succeeded, but a separately measured request took 10.2
seconds. Before changing the test for Task 2, two authenticated requests measured
0.182 and 0.148 seconds; homepage, REST, and login page were 0.153, 0.053, and
0.034 seconds. Persistent slowness was not reproduced at that time.

WordPress dashboard setup invokes optional browser/PHP version checks against
`api.wordpress.org/core/browse-happy/1.1/` and `/core/serve-happy/1.0/`, each with
a 5-second default HTTP timeout when results are unavailable. A process-local
diagnostic intercepted both requests **before network I/O** and confirmed these
budgets. The environment policy does not allow `api.wordpress.org`.

Repeated startup testing then reproduced the problem: a first cycle passed with
a 9.236-second first dashboard request and 0.069-second repeat; the next cycle
took 10.145 and 10.160 seconds and correctly failed the new performance check.
A subsequent trace in the existing web container measured the browser check
timing out in 5.005 seconds and the PHP check in 5.003 seconds. This confirms two
sequential optional network timeouts as the reproduced cause of the delay.

The fix enables `WP_HTTP_BLOCK_EXTERNAL` in the private Compose configuration
and its saved installer template. Both checks now return immediately under the
sandbox's explicit isolation policy. The 30-second dashboard deadline is retained
as a bound, and slow repeat requests still fail. No proxy bypass, network-policy
expansion, core edits, cache clearing, or data reset is used.

## Source, backups, and rollback

The environment foundation consists of `.gitignore`, `AGENTS.md`, this documentation,
the canonical smoke script and its regression tests. Website components and their
reviewed installable packages are documented below. Runtime data and private
configuration remain outside Git. `.gitignore` covers secrets, local runtime,
databases, logs, and sensitive backups while allowing theme/plugin source and
ordinary installable ZIPs in `dist/` or `releases/`. It is a guardrail, not a secret
scanner: inspect tracked files, diffs, and archive contents before committing.

Before adapting the private helpers, preserve `smoke.py` and `install.sh` in a
timestamped directory under `/workspace/local-wordpress/maintenance-backups/`, with
directory mode `0700` and files `0600`. These are helper backups, not database
exports, and must stay outside Git and the document root. Before adding HTTP
isolation, separately preserve `compose.yaml` and `install.sh` in another private
timestamped backup. Preserve `.env`, the startup helper, and `wp-config.php` unchanged.

When restoring the documented isolation setting in an existing sandbox, append
`define('WP_HTTP_BLOCK_EXTERNAL', true);` to its existing `WORDPRESS_CONFIG_EXTRA`
value without dropping any other configuration. Make the same change to the
installer's embedded Compose template; verify each `write_helper` template matches
the actual helper before rerunning the installer. Do not overwrite an unfamiliar
configuration. Apply the reviewed change with the saved startup command, which
recreates affected service containers while retaining the existing bind mounts.
Only this non-secret setting is documented in Git; private Compose files stay out.

To roll back this task, review the source diff and restore only its intended
source changes. Compare and restore the private helpers/configuration from the
selected backups if they have not subsequently changed. To reverse isolation,
restore the Compose/installer pair, then apply the saved startup command; this
reintroduces the known network delay and will fail the new isolation assertion
unless the smoke source is also rolled back. Do not reset Git, delete data,
restore a database, rotate credentials, or overwrite newer user work. The previous
smoke test will regain its known 10-second dashboard limit when restored.

If services cannot start, check local daemon access, Compose service health and
redacted diagnostics, then verify existing bind mounts and installation status.
Do not interpret a connection timeout as permission to initialize a new site.
Report a blocked or ambiguous state instead of performing destructive recovery.

## Website source and local activation

The website source lives under `wp-content/themes/sidartha` and
`wp-content/plugins/sidartha-portfolio` in the repository. The following workflow
targets only the existing loopback sandbox. Preserve a private database/settings
rollback snapshot before activation or importing initial content.

```sh
cd /workspace/sidartha-personal-website
python3 tools/dev/sync-sandbox.py
cd /workspace/local-wordpress
export DOCKER_HOST=unix:///var/run/docker.sock
unset DOCKER_CONTEXT DOCKER_TLS DOCKER_TLS_VERIFY DOCKER_CERT_PATH
docker compose run --rm -T cli plugin activate sidartha-portfolio
docker compose run --rm -T cli theme activate sidartha
docker compose run --rm -T cli --user=developer skp seed --publish-local
```

The source sync normalizes only copied source files to `0644`, never secret files,
and performs no deletion. The importer adds truthful starter content once, leaves
Privacy as a draft, and preserves all original content. Its explicit local mode
sets the site title, reading options, menu and permalinks for the preview. Outside
`WP_ENVIRONMENT_TYPE=local`, the publishing mode refuses to run; the administrator
UI instead creates drafts for owner review. Read the deployment guide before any
future non-local workflow; production access is not authorized here.

Run the website suites after activating:

```sh
cd /workspace/sidartha-personal-website
python3 tools/dev/check-website.py
python3 tools/dev/check-http-security.py
python3 tools/dev/check-browser.py \
  --axe /tmp/skp-browser-a11y/node_modules/axe-core/axe.min.js \
  --output /tmp/skp-browser-report
```

For browser QA, this cloud environment provides Python Playwright and
`/usr/bin/chromium`. The pinned temporary checker can be installed with
`npm install --prefix /tmp/skp-browser-a11y --ignore-scripts --no-audit --no-fund axe-core@4.10.3`
using the managed proxy/network policy. It is injected only during local tests,
not loaded by the theme. A `--no-sandbox` browser flag is confined to headless QA
inside the isolated container; it does not change WordPress or production settings.
