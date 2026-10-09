=== Sidartha Portfolio ===
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Portable WordPress content types for projects, learning milestones, and skill
groups. Native posts remain the blog. Settings > Portfolio profile controls
the public identity, photograph, public email, social links, footer sentence,
and article collection. No custom login, public custom API, external service,
or production build step is required.
Version 1.0.1 adds editable homepage learning-section copy and refines the default
introduction. Saved owner content takes precedence; no records are migrated.

== Installation ==
1. Upload the plugin ZIP in Plugins > Add New > Upload Plugin; activate it.
2. Install/activate the Sidartha theme for the included presentation.
3. Use Settings > Portfolio profile > Create starter drafts once. It is safe to
   rerun: existing content and edited starter records are preserved.
4. Review the drafts and publish only truthful, approved content. Never publish
   the privacy draft without reviewing actual hosting, cookies, logs, and services.
5. Set the homepage/posts page in Settings > Reading and create a Primary menu.

== Editing ==
Projects have a status, technology terms, optional source/demo URLs, optional
featured image, and a verification note. A Completed status without a note is
presented as In Progress by the companion theme. Verification notes are exposed
through REST only in an authorized edit context, not public responses.
Learning Journey entries have a date and kind. The theme displays month/year.
Skills have a learning stage, comma-separated topics, and optional evidence URL.
Demonstrated skills without evidence are presented as learning focuses.

== Data retention ==
Activation registers content types; it does not generate or reset content.
Deactivation and uninstall do not delete posts, metadata, options, or media.
Use a reviewed migration/cleanup plan if you later decide to remove this data.

== Development ==
wp --user=developer skp seed --publish-local
This explicit publishing mode requires an administrator and WordPress environment
type local. Public installations receive starter drafts through the admin UI.
Keep local credentials and data outside the repository and ZIP packages.
