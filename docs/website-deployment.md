# Future deployment, launch review, and rollback

**No deployment is authorized or performed by this task.** These instructions are
for a future owner-approved release. Keep all production credentials in the owner's
trusted credential manager/hosting interface, outside Git, chat, and Codex prompts.

## Owner inputs required before public launch

- Approve the introduction, About page, and first-person starter articles.
- Supply a separate **public contact email**, or explicitly decide to retain a
  contact-details-pending page. No actual mail delivery has been verified.
- Optionally provide an authorized portrait with accurate alt text. The current
  monogram is intentional and contains no invented photograph.
- Optionally provide actual public GitHub/LinkedIn/demo links; none are invented.
- Review project statuses and evidence. The personal website is In Progress until
  the owner verifies completion; no clients, testimonials, years of experience,
  awards, certifications, commercial results, or completed client projects are seeded.
- Review/publish an accurate privacy notice; confirm hosting/email log handling,
  retention, enabled comments/embeds, and any applicable legal obligations.
- Confirm the actual host's PHP/WordPress versions, HTTPS, file permissions, backup
  and restore capability, mail handling, domain configuration, and access controls.
- Decide whether future analytics, external embeds, SEO tools, or integrations are
  needed. None are silently included. Review privacy and network implications first.

## Release procedure after explicit approval

### Privacy footer patch: theme 1.0.2

The current theme-only patch is `dist/sidartha-1.0.2.zip`; the companion plugin
remains 1.0.1. After separately approving an installation, verify the new theme
checksum in `dist/manifest.json`, back up the existing theme, and update only the
theme on the approved staging site. Preserve all content and existing settings;
no database migration or starter import is needed.

Confirm the policy chosen in Settings → Privacy is published, then check the
footer's **Privacy Policy** link against that page. The official WordPress policy
takes priority over the plugin's original page. If the selected page is unpublished
or unset, only a published starter policy can act as the fallback; otherwise the
footer displays **Privacy notice under review** without a policy link. Check desktop
and phone layouts and clear only the approved site's asset/page cache if needed.

Rollback for this patch means restoring the prior reviewed theme 1.0.1. It requires
no database restore and does not change privacy settings. The prior theme may again
ignore an independently selected official policy. No installation, deployment,
publishing, or production connection is performed by the development task.

### Full release checklist

1. Verify the PR/release commit, checksums in `dist/manifest.json`, package contents,
   and a clean secret scan. Merge/release only with the owner's separate approval.
2. Take and verify a private backup of the actual target database, uploads, active
   theme/plugin code, and relevant configuration. Record current theme, menu
   locations, Reading settings, permalink structure, and public URLs.
3. Install and test the ZIPs on a private staging copy following
   `website-installation.md`. Restrict staging access and indexing appropriately.
4. Review and publish content selectively. Do not import the local development
   database or `.env`, copy synthetic accounts, or replace production `wp-config.php`.
5. Test all pages, draft visibility, native editing/media, authentication, capabilities,
   project filters, keyboard navigation, responsive layouts, metadata, and redirects
   on that staging host. Test delivery separately if a future mail service is added.
6. Inspect the actual host for plugins, caches, security policies, and rewrite rules
   that could affect the theme. This task did not verify Hostinger-specific behavior.
7. After approval of staging, schedule the public switch with a rollback owner and
   restore window. Change only the approved theme/plugin and content/settings.
8. Verify HTTPS, canonical URLs, page titles, status codes, WordPress sitemap,
   indexing choice, privacy notice, contact link, and all critical flows on the
   approved target. Do not promise search rankings.

The development `WP_HTTP_BLOCK_EXTERNAL` setting is deliberate sandbox isolation.
It blocks external WordPress HTTP calls, including update downloads. It is **not**
packaged into the production theme/plugin and must not be copied as an unexplained
production setting. A real site's outbound/update policy requires owner/security
review; never disable security controls simply to make a feature work.

## Rollback

Code rollback: activate the previously recorded theme and disable the companion
plugin only if needed. Content and metadata remain stored; reinstalling/reactivating
the plugin restores their native editing menus and routes. Restore menu locations,
Reading settings, title/tagline, and permalinks from the pre-release record. Restore
the previous reviewed code packages without overwriting newer unrelated changes.

Data rollback is a **separate explicit decision**. Prefer reversing only the approved
settings/content changes. A full database restore can lose newer posts, comments,
users, or other writes; obtain authorization and preserve those newer records before
any restore. Never use a reset, drop, volume removal, or blanket Git cleanup as rollback.

## Local recovery record

Before this implementation, a database rollback copy and a settings/content-hash
snapshot were created under the private runtime's timestamped
`maintenance-backups/task3-*` directory, outside Git and the web root. No backup is
included in either ZIP or PR. A local pointer exists at `/tmp/task3-backup-path` for
the current session only. Do not print or upload the database backup.

New starter records use `skp_starter_map` and `skp_pages` option mappings. Re-running
the importer preserves edited records and does not duplicate them. The local-only
initial configuration changes the site title/tagline, Reading options, permalink
structure, and active theme/menu; the original values are in the private snapshot.
The original development credentials and user records are not replaced.
