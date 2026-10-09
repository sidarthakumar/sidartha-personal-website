# Mandatory development and security instructions

These instructions apply to every file in this repository and all associated
development work. Read this file and relevant documentation before editing.
Project: `sidarthakumar/sidartha-personal-website`.

## Scope and production boundary

- Work only in the isolated development sandbox using synthetic local data.
- Do not connect to, crawl, test, administer, or modify the production website
  `sidarthakumar.com`, its database, hosting account, or production services.
- Do not request or use real Hostinger or production WordPress credentials.
  All production credentials must remain outside this repository and Codex
  prompts, chat messages, tool arguments, and logs.
- No production deployment, migration, DNS change, or other production action
  without explicit user approval for that action. Such approval must explicitly
  supersede the local-only boundary; a development request is not authorization.
- Do not begin design, theme/plugin implementation, or business features until
  the user authorizes that scope. Do not publish, push, or merge merely because
  local verification passes. Leave reviewable changes unless instructed otherwise.

## Preserve data and use least privilege

- Never delete or reset existing content, users, passwords, databases, volumes,
  configuration, repository changes, or backups as a troubleshooting shortcut.
  Do not run database reset/drop, `docker compose down -v`, destructive cleanup,
  or a fresh WordPress install over an existing installation.
- Diagnose errors before changing configuration. Check installation status
  successfully before considering any initialization; a connection error is not
  evidence of an empty database. Stop on ambiguous state.
- Bind development HTTP to loopback; do not publish a database port or create
  a public tunnel. Verify WordPress home/site URLs before HTTP smoke tests.
- Keep `WP_HTTP_BLOCK_EXTERNAL` enabled for this isolated sandbox, with no external
  host exceptions. External integrations and update downloads need a separately
  reviewed development workflow; never silently disable isolation for a test.
- Use the local Docker socket explicitly. Preserve the managed proxy, TLS trust,
  and credential bindings; never bypass network policy or disable TLS checks.
- Use a dedicated database account and minimum capabilities. Do not use database
  root or an administrator account in application code. Use a local administrator
  only for development administration and its authentication smoke test.
- Enforce role-based access through WordPress capabilities, not role-name or
  username comparisons. Check object-specific permissions where applicable.

## WordPress architecture and maintainability

- Follow WordPress PHP Coding Standards, WordPress JavaScript/CSS/HTML standards,
  and the PHP versions explicitly supported by the project. Prefer WordPress APIs.
- Never edit WordPress core, vendor code, or third-party themes/plugins to add
  project behavior. Keep presentation/templates/styles in the theme and portable
  domain functionality, content types, integrations, and REST endpoints in plugins.
- Prefix or namespace project symbols, hooks, options, script handles, and routes.
  Use hooks and documented extension points rather than fragile overrides.
- Enqueue assets through WordPress APIs. Use translation functions and a stable
  text domain for user-facing strings. Keep templates accessible and semantic.
- Keep functions focused, dependencies explicit, and changes small and reviewable.
  Avoid duplicated logic and speculative abstractions; document non-obvious choices.
- Version database migrations, make them idempotent, and preserve existing data.
  Activation, deactivation, and uninstall must not remove user data by default.

## Secure coding

- Treat request data, stored data, remote responses, filenames, and URLs as
  untrusted. Validate types, lengths, ranges, formats, and allowlisted values
  before use; reject invalid values rather than silently coercing them.
- Use appropriate WordPress sanitizers, and `wp_unslash()` where needed for
  WordPress request input. Sanitization does not replace validation or authorization.
- Escape at output for the destination context: `esc_html()`, `esc_attr()`,
  `esc_url()`, `wp_kses_post()`/an explicit KSES allowlist, and `wp_json_encode()`
  for JSON. Never interpolate untrusted text into executable code or shell commands.
- Require both a valid action-specific nonce and a capability check for
  cookie-authenticated state-changing actions. Nonces are not authentication or
  authorization. Check capability and ownership before reading private data.
- Prefer WordPress query/data APIs. For custom SQL, use `$wpdb->prepare()` with
  correct placeholders; allowlist identifiers and sort directions. Never concatenate
  untrusted SQL fragments. Use transactions where supported and appropriate.
- Register REST routes with an explicit `permission_callback`, a defined schema,
  argument validation/sanitization, correct HTTP methods, and bounded pagination.
  Explicitly justify public read endpoints. Authenticate private endpoints; use
  REST nonces for cookie authentication and capability checks for each operation.
- Do not expose internal exceptions, credentials, personal data, or stack traces
  in REST errors. Use `WP_Error` and appropriate status codes. Test anonymous,
  insufficient-permission, malformed, and valid requests.
- Use WordPress HTTP APIs with bounded timeouts, safe URL validation, and TLS
  verification. Guard redirects and SSRF; allowlist destinations where practical.
  Development checks must never follow a redirect to production.
- Validate uploads by type and size, prevent path traversal and executable uploads,
  and use WordPress filesystem/upload APIs. Do not deserialize untrusted input.

## Secrets, Git, and logging

- Never hardcode credentials, authentication cookies, salts, tokens, or private
  keys. Keep development secrets outside the repository and document root, with
  restrictive permissions; use placeholder-only example configuration.
- Never print `.env`, full process environments, expanded Compose configuration,
  Docker inspection environment fields, database contents, authentication headers,
  login payloads, or secret-bearing error bodies. Log safe summaries and timings.
- Respect `.gitignore`, but remember it does not protect already tracked files.
  Inspect tracked paths, intended diffs, staging, and relevant history before
  committing. Secret scanning must report locations/types, not secret values.
- If a secret is found, stop its propagation and report it privately. Do not
  rewrite history or rotate production credentials without explicit authorization.
- Keep database/runtime directories, private configuration, logs, and backups out
  of Git and deliverables. Theme/plugin source and reviewed installable ZIPs must
  remain trackable. Inspect ZIP contents before release; do not archive the workspace.

## Verification and change review

- Read `docs/development.md` before starting or restoring this sandbox. Use saved
  startup helpers and preserve existing data. Do not invent replacement credentials.
- Run the full smoke suite twice across repeated startup cycles after environment
  changes. Verify PHP, MariaDB, WordPress, WP-CLI, homepage, direct DB query, REST,
  valid administrator authentication, and rejection of invalid authentication.
- Record per-test durations. Keep explicit bounded timeouts and nonzero exits for
  failures. Distinguish a slow first request that recovers from persistent/repeat
  slowness. Never conceal errors by unconditional retries or only raising timeouts.
- Test meaningful failure paths for test tooling: invalid credentials, unexpected
  redirects/content/status, deadline expiry, and performance classification.
- For future PHP changes, run PHP syntax checks and WordPress Coding Standards
  tooling when configured; test changed behavior and authorization boundaries.
  For UI changes, check accessibility, responsiveness, and relevant browser behavior.
- Do not claim unrun checks passed. Report failures, limitations, exact commands,
  and remaining risks. Verify Git status contains only intended source/docs changes.
- Use Codex change review. Do not commit, push, deploy, or create release artifacts
  unless the user has requested that step. Never automatically merge changes.

## Documentation, backup, and rollback

- Document setup/start/test commands, dependencies, supported versions, safe
  recovery steps, assumptions, failure modes, and changes to developer workflows.
- Before modifying a local helper/configuration, keep a private versioned copy
  outside the repository and document how to restore it. Do not include `.env`
  values or secret-bearing configuration in source documentation or public artifacts.
- Before any authorized data migration or destructive operation, establish an
  access-controlled backup and a tested rollback plan. Obtain explicit approval
  for destructive scope. Do not export a live database just to test connectivity.
- Roll back code through reviewed diffs; avoid blanket reset/clean commands.
  Restore configuration without overwriting newer user changes. Database restore
  is a separate authorized operation, never an automatic smoke-test action.
- Keep verification reports free of secrets and identify whether changes are
  committed, pending review, or blocked. Stop at the user's requested scope.
