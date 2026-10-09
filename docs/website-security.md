# Website security review

Scope: the custom theme, companion plugin, development tooling, and isolated local
runtime. This is a code and local behavior review, not a production penetration test
or a guarantee about future third-party plugins/hosting configurations.

## Trust boundaries and controls

- **Production boundary:** no Hostinger, production WordPress/database, or live-domain
  access. Development requests target loopback; browser QA blocks other origins.
  No deployment code or production credentials are part of the deliverables.
- **Authentication:** standard WordPress authentication and cookies. No custom login
  system. Profile settings/imports require `manage_options`; content editing requires
  the native object-specific `edit_post` capability. WordPress role mapping is retained.
- **CSRF:** the Settings API protects profile changes; meta boxes use action-specific
  nonces plus capabilities and skip revisions/autosaves. Starter import checks both
  `manage_options` and its nonce. Cookie-authenticated REST writes use native REST
  nonce and permission checks. A nonce alone never grants permission.
- **Input/output:** scalar guards reject array-shaped input. Dates are calendar-checked;
  statuses use allowlisted REST enums; text/URLs/emails use WordPress sanitizers.
  URL fields permit only HTTP(S), require a host, and reject embedded credentials.
  Theme titles, metadata, attributes, URLs, and excerpts are escaped for context.
  Native post content uses the standard WordPress content/KSES permission model.
- **Database:** frontend filtering and content operations use native WordPress query,
  taxonomy, option, and metadata APIs. No application SQL is built from request data.
  Local tests use fixed read-only queries with the trusted WordPress table prefix.
- **REST:** only core WordPress routes used for native editing; no custom public
  endpoint. Drafts and edits retain core access checks. Project verification notes
  are restricted to authorized `edit` context rather than public REST data.
- **Contact:** explicit public email setting, never an administrator-email fallback.
  The plugin rejects matching addresses on save and suppresses them on render if
  the administrator address changes later. No contact-message storage or mail service.
- **Media/files:** native media library/image IDs; non-image profile IDs are rejected.
  No custom upload processor, arbitrary file access endpoint, or SVG upload bypass.
  Dynamic imagery uses WordPress image APIs, responsive sizes, and alt text.
- **Outbound/privacy:** no third-party frontend JavaScript, fonts, tracking, analytics,
  remote images, embedded videos, or invented external links. Accurate minimal Person
  structured data contains only the supplied public name and current site URL.
  The local WordPress HTTP API remains externally blocked; no network controls bypassed.
- **Data retention:** activation registers types only. Starter content is explicit,
  idempotent, and draft-first outside local mode. Deactivation/uninstall deletes no
  content. Existing content, accounts, passwords, and local private files are preserved.
- **Archives:** exact component-root allowlists, deterministic member paths, normal
  `0644` source permissions, integrity/source comparisons, and secret scans. No `.env`,
  configuration, database, backups, test fixtures, or local runtime in installable ZIPs.

## Verified failure cases

The local suites check missing/invalid meta nonces, anonymous editing/imports,
authenticated read-only editing/import rejection (without changing account roles),
anonymous REST creation/update/draft reads, invalid REST status values, unsafe URL
schemes, credential-bearing URLs, malformed media/category inputs, invalid/null-byte dates,
administrator-email rejection, and unverified completion labels. Actual HTTP checks
cover missing/invalid REST nonces and a rejected starter-import action nonce.
Browser checks exercise reflected filter input, malformed query arrays, draft URLs,
real 404 responses, keyboard navigation, and no-JavaScript fallbacks.

## Operational limits

- The site's actual external hosting, TLS, backups, email providers, plugin mix,
  update policy, and production permissions remain untested by design.
- `WP_HTTP_BLOCK_EXTERNAL` protects WordPress HTTP API calls in development; it is
  not a firewall for arbitrary PHP extensions/socket code. Managed network policy
  remains the outer boundary. Future integrations require review.
- Profile/social links are owner-managed publication choices. Validation prevents
  executable schemes and embedded credentials; it does not independently prove
  ownership or privacy of a URL. Only publish authorized public links.
- A verification note prevents an empty Completed claim; editorial evidence still
  requires a human review. No automated claim of professional credentials is made.
- An unpublished QA draft may remain in the local database. It is not shipped in
  ZIPs or rendered publicly. Ordinary session/transient metadata changes during tests.
- Accessibility automation and local security tests cannot replace screen-reader,
  real-device, cross-browser, and actual-host testing before public launch.
