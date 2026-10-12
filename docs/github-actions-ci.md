# Private cloud CI

The workflow `.github/workflows/wordpress-ci.yml` runs on GitHub-hosted Ubuntu
24.04 runners for PRs targeting `main` and pushes to
`codex/personal-wordpress-website`. It also declares manual dispatch, which GitHub
normally exposes only after the workflow exists on the default branch. The first
run is triggered by the authorized branch push; merging is not needed for that.
No Mac installation or local website is required.

## Checks

- PHP 8.1 and 8.3 syntax for all theme/plugin/test PHP files.
- Actual theme callback harness `tools/dev/test-seo.php`, including all three
  detected SEO-plugin modes: Yoast, Rank Math and All in One SEO.
- Existing Python authentication/security/timeout regression suite, Python
  compilation, existing JavaScript syntax, shell syntax and Git whitespace.
- After those jobs pass, disposable native WordPress 6.6.2 / PHP 8.3 / MariaDB
  11.4 integration. Official WordPress file checksums are verified before setup.
  The existing 51 integration/security assertions cover capabilities, nonces,
  REST authorization/validation, preservation and footer policy behavior. New
  native SEO checks use the real WordPress queries, options, content and escaping
  APIs, including separate processes for all three SEO-plugin detection modes.

## Security boundary

Only `contents: read` is granted; all unspecified token permissions are disabled.
Checkout does not retain authentication. External actions are pinned to immutable
commit SHAs. The workflow uses ordinary `pull_request`, never
`pull_request_target`, and no pull-request text is interpolated into commands.
No repository/environment secrets, hosting credentials or deployment identities
are used. No artifacts, releases, comments or deployments are published.
GitHub logs remain subject to the existing private repository's access controls;
repository visibility is not modified.

The integration script refuses execution outside GitHub-hosted Actions. It creates
its own temporary fixture under `RUNNER_TEMP`, generates and masks random database
and administrator values, uses a dedicated database principal, and creates only
synthetic content/accounts (email uses `example.invalid`, without email delivery).
Configuration and raw setup/test diagnostics stay in private temporary files.
Database containers publish no ports. After dependency retrieval, all WordPress
execution uses a new internal-only Docker network. External WordPress HTTP is
blocked, cron and updates are disabled. Staging, production, existing databases,
accounts and the saved Codex sandbox are never accessed.

Cleanup is restricted to this run's newly created container, anonymous database
volume, network and temporary directory. No existing data is deleted. The runner
itself is disposable. Failures return nonzero; raw error bodies/configuration are
not printed. Job timeouts bound dependency downloads and setup failures.

## Compatibility and limits

PHP 8.1 is the theme's declared minimum, included for regression compatibility;
it is beyond upstream security support and is not recommended for deployment.
PHP 8.3 is the previously used development runtime. Native integration currently
covers PHP 8.3 with the WordPress 6.6 baseline only, not the full WordPress/PHP
matrix. WordPress 6.6.2 is an explicitly pinned synthetic compatibility fixture,
not a production-version recommendation. Actual plugins are not installed for
ownership tests; their established version constants are simulated.

The WordPress CLI and MariaDB container tags are version-family selectors; upstream
patches may change. They must be reviewed/pinned to digests when establishing a
fully immutable dependency baseline. WordPress core is versioned and verified
against official checksums; this is not an independent supply-chain audit.

Browser/accessibility, actual HTTP login/CSRF checks, full WPCS auditing, email
delivery and hosting compatibility are outside this CI's current coverage. Existing
HTTP/browser tools depend on the prior local sandbox and are not invoked against
any real site. The native suite does exercise REST and nonce/capability boundaries.

Actions availability, allowed-action policy, private-repository runner minutes and
the connected app's workflow-write permission may block runs. Report the actual
run status and URL; a workflow definition or local static pass is not proof that
GitHub tests passed. This task does not generate theme 1.0.3 or authorize any merge,
staging install or production deployment. Later delivery is CI → separately
authorized staging verification → explicitly approved production deployment.

Rollback: revert only the reviewed CI source commit. No content/configuration
restore is needed because the fixture has no connection to an existing website.
