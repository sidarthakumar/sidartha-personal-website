# Hostinger deployment and recovery

Target: `sidarthakumar.com`, existing Premium Web Hosting. Preserve Hostinger DNS, mail records, email accounts and `hello@sidarthakumar.com`.

**Delivery is gated.** Record actual connection, backup, recovery and publication results before enabling unattended deployment. The workflow alone does not establish those results.

## Before the initial switch

1. Use the authorized signed-in dashboard. Passwords and verification codes stay out of chat and source.
2. Verify the domain document root, current WordPress files, server and caching behavior.
3. Make fresh, access-controlled backups of WordPress files and its database outside Git and all public directories. Test file extraction in a private location. Preserve the original database; any database restoration is a separate decision.
4. Preserve the original WordPress directory outside the public root. Prepare the exact reversal of the directory move. Do not delete the installation or use a database reset during migration.
5. Check the GitHub artifact branch contains only the built public files and matches the reviewed source revision.
6. Deploy the first static version and verify actual HTTPS pages, headers, canonical redirects, former URLs, 404 responses, protected paths and mobile behavior. Restore the preserved WordPress files if the initial rollout fails.
7. Test a second harmless static revision and a recovery to the preceding public tree before enabling unattended publishing.

## GitHub to Hostinger

Hostinger’s GitHub app is authorized for only `sidarthakumar/sidartha-personal-website`. The app requests read/write administration, code and pull-request access, plus read access to Actions, metadata and repository hooks. Its authorization is separate from the GitHub workflow token. Do not extend it to other repositories without the owner's permission.

Hostinger must deploy branch **hostinger-live** to the verified document root, normally **public_html**. Never deploy main or the source branch directly: source files, WordPress history, Markdown drafts and development tools are not website output.

The validation job has read-only GitHub access. It installs locked dependencies without lifecycle scripts, tests failure paths, scans source, generates the site, verifies draft exclusion and inspects the public files. A published release builds current main and reads only selected public releases through GET requests. No release-feed process writes to main.

Only the publishing job has `contents: write`, using GitHub's short-lived workflow token. It receives the already-checked `site-dist/` artifact and creates a complete Git tree containing those public files. It checks the exact source revision is still current main before updating hostinger-live. It never changes main, force-pushes a branch or publishes a PR artifact. Git history on the deployment branch records every publication and removes old pages from the current tree when they leave the artifact.

No hosting password, private SSH key or Hostinger API token is stored in the source or GitHub secrets for this integration. Third-party actions are pinned to commit SHAs. Keep applicable repository and production environment protections; do not weaken them to make a failed deployment pass.

Set `HOSTINGER_DEPLOY_ENABLED=true` only after initial deployment, backup/recovery and real server verification succeed. Hostinger auto-deployment must be enabled for hostinger-live. Initial branch creation and initial migration are separate from the disabled unattended job.

## Verification and recovery

After publication, the workflow waits up to five minutes for the exact revision.txt marker, then checks live HTTPS pages, security headers, metadata, sitemap, feed and protected paths. A failed check keeps the workflow failed. It requests a code-only rollback by creating a new deployment-branch commit with the previous complete tree, only if the failed publication still owns that branch. It never force-writes over a newer publication. The initial artifact has no previous static tree and cannot automatically restore WordPress.

Native Hostinger deployment is asynchronous. A rollback request does not prove that Hostinger has applied it; inspect deployment history and the live revision. This method does not promise an atomic server switch or zero downtime. Previous Git trees remain available. Restore a known good public tree with a new commit and redeploy if provider delivery fails. Never restore the old database automatically.

WordPress recovery moves the preserved original directory back to the document root and disconnects/pauses native Git deployment first. The unchanged WordPress database remains available. Keep the private file archive and SQL backup as additional recovery copies. Record exact source and deployment commits, CI results, backup evidence, actual headers/redirects and any remaining limitations.

The older SSH helper in tools/portfolio/deploy.py is not used by the native GitHub delivery workflow. Do not initialize it or add SSH credentials for the current deployment.
