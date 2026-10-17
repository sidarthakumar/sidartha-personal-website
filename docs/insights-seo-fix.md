# Insights meta description — pending verification

Based on PR #2 commit `82052cb2b5164d8f663d48e31fe4d483a6987351`, theme 1.0.2.
The posts index uses `is_home()`, which the existing theme SEO callback did not
handle. The proposed change reads the configured published, unprotected Posts
page's excerpt, then stripped content, then the requested Insights fallback.
The existing attribute escaping, 30-word limit, front-page description, singular
excerpt behavior, Person schema and SEO-plugin detection are retained. No canonical,
robots, sitemap, indexing, URL, layout, stylesheet or companion plugin changes.

`tools/dev/test-seo.php` runs the real registered theme callback with database-free
WordPress API doubles. It covers excerpt/content/fallback precedence, missing and
unset Posts pages, unpublished/protected content, attribute escaping, one description,
the length bound, homepage/singular behavior, excluded contexts and each detected
SEO plugin. This harness does not substitute for native WordPress integration.

Run when PHP is available:

```sh
php -l wp-content/themes/sidartha/functions.php
php -l tools/dev/test-seo.php
php tools/dev/test-seo.php
php tools/dev/test-seo.php WPSEO_VERSION
php tools/dev/test-seo.php RANK_MATH_VERSION
php tools/dev/test-seo.php AIOSEO_VERSION
```

Verification in this executor:

- PASS: 21 existing database-free smoke-tool/security regression tests, 0.051s.
- PASS: `git diff --check` and changed-file sensitive-content/path review.
- BLOCKED: PHP is not installed in the executor; local Docker socket access is
  denied (`connect: operation not permitted`). No PHP execution is claimed.
- NOT RUN: native WordPress integration/security and HTTP smoke suites. The
  former writes database fixtures and the latter authenticates an administrator;
  this task forbids database/account access. Neither was attempted.

The 1.0.3 version bump and ZIP build are intentionally gated on successful PHP and
native verification. No new package or manifest change has been made; existing
1.0.2 artifacts are preserved. The owner authorized committing and pushing these three files to the existing
PR #2 branch; native verification remains pending. No deployment,
merge, hosting, credentials, database, DNS or account access occurred.

Rollback is limited to reversing the reviewed `functions.php` change; no data or
configuration restoration is required. Once verification is possible, bump the
theme header/readme and package builder to 1.0.3, build the source-only ZIP and
update the checksum manifest and installation/deployment package references.
