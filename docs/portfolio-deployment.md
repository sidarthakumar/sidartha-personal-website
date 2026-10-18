# Hostinger deployment and recovery

Target: `sidarthakumar.com`, existing Premium Web Hosting. Preserve Hostinger DNS, mail records, email accounts and `hello@sidarthakumar.com`.

**Delivery is gated.** Workflow source does not establish hosting access, backups, SSH, recovery or successful publication. Record actual results before enabling deployment.

## Before the initial switch

1. Access the authorized dashboard through a supported connection; passwords and verification codes stay out of chat.
2. Verify the actual document root, current WordPress installation, SSH/key support, web server and cache.
3. Verify a fresh, completed, access-controlled backup of actual WordPress files and database. Keep it outside Git and every public directory. Verify restoration in a protected test location.
4. Preserve the original installation, database and settings. Record a private recovery location and tested restoration steps. Do not delete WordPress or change its database during the static migration.
5. Test the static artifact on the actual server: `.htaccess`, HTTPS/canonical redirects, headers, legacy addresses, 404 status and responsive pages. Local preview does not test LiteSpeed/Apache behavior.
6. Verify release-directory/symlink support. If unavailable, test a supported alternative and its recovery before unattended use.

## Expected deployment layout

The helper refuses to convert an existing WordPress root. It requires an initialized, tested static layout:

```text
<domain-root>/public_html -> .portfolio/releases/<40-character-source-commit>
<domain-root>/.portfolio/initialized       # exactly: sidarthakumar.com
<domain-root>/.portfolio/releases/<commit>/index.html
```

The root is `/home/<hosting-user>/domains/sidarthakumar.com`; verify its actual value. `.portfolio/` stays outside the served root, with only necessary traversal/read access. Backups stay separately private. The first artifact needs `revision.txt` containing its reviewed commit. Verify switching between two harmless static releases before unattended use; keep WordPress recovery separately available.

## Protected configuration

Store `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_PORT`, `HOSTINGER_SSH_USER`, `HOSTINGER_SSH_KEY`, `HOSTINGER_KNOWN_HOSTS` and `HOSTINGER_SITE_ROOT` in protected GitHub production deployment secrets through a secure, supported flow. Never paste secret values into chat or commit them.

Verify host public keys through a trusted provider/account source. Do not disable TLS or host-key verification. Prefer a site-scoped identity; verify actual account access scope before granting it. The helper feeds the private key to ssh-agent over stdin without a key file. PR checks have no hosting secrets. The GitHub token is read-only. Third-party actions are pinned to commit SHAs.

Only set `HOSTINGER_DEPLOY_ENABLED=true` after connection, backup/recovery, initial migration and actual server tests pass. Keep appropriate production branch/tag restrictions. Resolve repository instruction/protection changes separately where required; do not remove protections to make a failed update pass.

## Workflow and recovery

Checks install locked dependencies without lifecycle scripts, test rejection paths, scan source, build pages, test draft exclusion and inspect output. Only `site-dist/`, including `.htaccess`, is uploaded as the public artifact.

A published release starts a build of the current main revision. All trusted builds refresh only public releases through GET requests; the derived input is retained as a separate build artifact. No content-write token or direct main-branch commit is needed for the feed.

The deployment helper verifies source/artifact revisions, host identity, initialized layout and the public-file boundary. It uploads a new release outside the served root, switches the static symlink and verifies HTTPS pages. Failed verification triggers code-only rollback if the failed run still owns that symlink. Previous releases remain. Actual Hostinger behavior must be tested before activation.

WordPress recovery restores the preserved installation and document-root settings. Its database should be unchanged; any database restore is a separate decision that can overwrite newer data. Record deployed commit, CI result, public URL, backup/recovery evidence, headers/redirects and remaining limits. Never report successful deployment or absolute security without evidence.
