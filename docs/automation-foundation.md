# Automated website delivery — safe foundation

This branch adds offline verification and a downloadable CI package artifact for the existing Sidartha WordPress theme and portfolio plugin. It does **not** install, change, or access the live website.

## Existing source of truth

- Website source: `wp-content/themes/sidartha/` and `wp-content/plugins/sidartha-portfolio/`.
- Current prepared releases: `dist/sidartha-1.0.3.zip` and `dist/sidartha-portfolio-1.0.1.zip`.
- The checked-in package manifest specifies SHA-256, byte length, and included file count.
- Production website content, the WordPress database, media uploads, users, passwords, and configuration are **not** managed through Git.

## Continuous integration

On the current feature branch or a PR targeting `main`, GitHub Actions runs PHP syntax/security/SEO regression and synthetic WordPress integration. Only after all jobs pass will the release verification job:

1. Cross-check component versions against package names and manifest.
2. Verify every ZIP SHA-256, size and file count.
3. Reject duplicate ZIP entries, symlinks, unexpected source members, path traversal and mismatched file bytes.
4. Upload both verified ZIP files and their manifest as a GitHub Actions artifact (14-day retention).

No hosting secret is needed, and no production deployment is attempted.

## Next deployment boundary

Before any automatic deployment can be activated:

1. Audit Hostinger's actual production and password-protected staging versions, permissions, plugin compatibility and update settings.
2. Establish verified encrypted file/database backups and a tested staging restore.
3. Configure a restricted deploy identity and trusted deployment secrets outside the repository and AI conversation.
4. Deploy **only approved custom theme/plugin packages** to staging; never push the dev database, uploads or wp-config.php.
5. Check staging site origin, homepage/pages, SEO metadata, contact behavior, logs, accessibility and theme/plugin compatibility.
6. Establish a production release policy: approved low-risk code changes may auto-promote only after all tests, verified backups, and health gates pass; permissions changes, database operations and failed tests require an owner decision.
7. If code validation fails, restore the previous custom component files. Never automatically replace the live database with an older backup, as it can erase newer user content.
8. Create recurring uptime/security/backup/SEO reports using authorized integrations. Log no private visitor data or credentials.

The repository's `AGENTS.md` local-only rule continues to apply to any direct site access or deployment until explicitly scoped deployment instructions, hosting access and backup checks are verified.

## Routine release verification

```bash
python3 tools/ci/verify-release.py
```

Check the relevant GitHub Actions run, download the verified artifact and use the documented staging-review process before any live installation. The workflow definition alone is not evidence of a successful CI run or deployed release.
