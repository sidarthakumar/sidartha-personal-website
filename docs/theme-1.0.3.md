# Sidartha 1.0.3 — staging candidate

Prepared from the verified CI/SEO source on PR #2. Only theme/release metadata,
packaging and documentation change in this preparation; runtime PHP logic, assets,
design, URLs, SEO/privacy settings and the companion plugin are preserved.
The package includes the Insights description fix and existing official-policy
footer correction. No content migration, import or settings write is needed.

## Package

- File: `dist/sidartha-1.0.3.zip`
- SHA-256: `b529f6f48a1bc1fee24297728b8b844325881185ab188748420eac062ba016df`
- Size: 231,468 bytes
- Members: 22, all under the installable `sidartha/` root
- Companion plugin: unchanged 1.0.1, with its existing ZIP/checksum preserved

Built with `python3 tools/build-packages.py`. CRC integrity, safe archive paths,
source-byte equality, manifest checksum/size/count, sensitive-path/token/private-key
review and all previous ZIP/source preservation checks pass. Repeated builds keep
identical existing ZIPs untouched; the builder now refuses to overwrite an existing
version with different bytes. Version a new package when changing shipped source.

PHP syntax and SEO regression verification use GitHub Actions on PHP 8.1 and 8.3;
native WordPress integration uses the disposable PHP 8.3 fixture. Final run results
are reported with the delivered commit/run URL. See `github-actions-ci.md` for the
CI boundary and compatibility limitations. No production compatibility, real-site
browser checks or owner staging installation is claimed.

## Approved future staging checks and rollback

Installation requires the owner's separate approval. Install only this theme;
retain the active companion plugin and do not rerun the importer. Check Insights
has one escaped description (or is owned by an active detected SEO plugin), and
homepage/singular descriptions, privacy link, URLs, indexing and phone/desktop
layout remain correct. See `website-deployment.md` for the full owner procedure.

For code rollback restore `dist/sidartha-1.0.2.zip` only. That removes the Insights
description correction while retaining the previous privacy-footer behavior.
Preserve all content/settings; do not reset/restore a database or disable/change
the plugin for this patch. Earlier 1.0.0, 1.0.1 and 1.0.2 packages remain byte-for-byte
unchanged. Preparation is not a GitHub release, staging install or deployment.
The repository remains private and PR #2 must remain unmerged pending approval.
