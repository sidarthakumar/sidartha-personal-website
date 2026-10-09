# Install the Sidartha website

This is an owner-run installation guide for a later, explicitly approved release.
No production or Hostinger connection was made during development. Never send
production credentials to Codex or store them in this repository.

## Deliverables and compatibility

- `dist/sidartha-1.0.0.zip`: the complete custom theme.
- `dist/sidartha-portfolio-1.0.0.zip`: projects, milestones, skill groups, public
  profile controls, and optional starter drafts.
- `dist/manifest.json`: package SHA-256 checksums, sizes, and file counts.
- All initial copy is included in the plugin's non-destructive starter importer.
  No development database export is part of either ZIP.

Requires WordPress 6.6+ and PHP 8.1+. Tested locally on WordPress 7.1.3, PHP 8.3.35,
and MariaDB 11.4.13. Confirm supported WordPress/PHP versions in the actual Hostinger
plan before installation. Hostinger compatibility is architectural, not a claim
that its account/server has been accessed or tested. Production runs ordinary
WordPress PHP and static CSS/JavaScript; no Node.js, Docker, build service, or
continuous application server is required. There are no runtime library dependencies.

## Install on a private staging copy first

1. Obtain explicit authorization for the target environment. Back up its database,
   uploads, active theme/plugin files, and important settings through the owner's
   trusted hosting/WordPress interface. Keep backups access-controlled and outside
   Git and the public web root. Verify how to restore them before changing anything.
2. In WordPress, use **Plugins → Add New → Upload Plugin** to upload the companion
   ZIP, then activate it. Activation registers content types but creates no content.
3. Use **Appearance → Themes → Add New → Upload Theme** to upload the theme ZIP.
   Preview it, then activate it on staging. Existing posts, pages, users, and media
   are retained. WordPress may require resaving **Settings → Permalinks** once.
4. Open **Settings → Portfolio profile → Create starter drafts**. This creates
   drafts only on public/staging installations. It is idempotent: existing and
   edited starter records are not overwritten. Do not use the developer's
   `--publish-local` mode on staging/production; that mode refuses non-local sites.
5. Review every draft. Publish the approved Home, About, Skills, Projects, Journey,
   Insights, and Contact pages. Review/publish the six Skills entries, two learning
   milestones, in-progress personal project, and two introductory articles as
   appropriate. Keep the Privacy Policy draft unpublished until owner/legal review.
6. In **Settings → General**, set the site title and tagline intentionally. In
   **Settings → Reading**, choose a static homepage: **Home**, with **Insights** as
   the posts page. The homepage must be a published page.
7. In **Appearance → Menus**, create a menu with those seven pages and assign it to
   **Primary navigation**. An optional **Footer links** menu can hold approved links.
   The local demo creates a menu automatically; a real installation is deliberately
   configured by the owner instead of overwriting an existing menu/reading setup.
8. Edit **Settings → Portfolio profile**. Supply an approved public contact email
   distinct from the WordPress administrator email, optional public social links,
   and an authorized photograph with appropriate media-library alt text. Empty
   contact details show an honest pending message; no administrator fallback exists.
9. Select the desired **Articles collection** category in the same settings screen.
   The starter importer selects its Insights parent category, including its children.
   Existing articles outside that collection remain intact and accessible through
   their URLs/categories. Choose **All published posts** if that is the desired scope.
10. Complete the launch checklist in `website-deployment.md`; test the target host
    independently before any public switch.

## Theme without the plugin

The theme fails gracefully if the plugin is inactive: ordinary pages/posts still
work and missing portfolio collections show empty states. Projects and metadata
remain in the database, but their editing menus/routes need the plugin restored.
Deactivate rather than deleting data while diagnosing an issue. No uninstall
handler removes content. The companion theme/plugin are designed to be used together.

## Rebuild the ZIPs

From the repository, run `python3 tools/build-packages.py`. The packager includes
only approved source/asset types beneath the two exact component roots. It checks
archive integrity, root paths, and byte-for-byte equality to source. It never
archives `.env`, uploads, a database, local configuration, tests, or the workspace.
Inspect the resulting ZIP member lists and scan them before publishing a release.

The theme screenshot is a real local frontend capture. The decorative monogram,
browser illustration, CSS, and SVG icons are original source, not a fabricated
portrait or a client screenshot. Licenses are included in each package.
