# Editing the personal website

All content is editable through WordPress. No page builder or custom login is used.
Use **Pages/Posts** for prose and the companion plugin's native administration menus
for structured collections. The block editor works with the provided editor styles.

| What to change | Where |
| --- | --- |
| Public name, homepage label/headline/introduction | Settings → Portfolio profile |
| Homepage learning section label, heading, introduction | Settings → Portfolio profile |
| Profile photograph | Settings → Portfolio profile → Choose from media library |
| Photograph alt text/crop/caption | Media → select the image |
| Homepage editorial note next to featured project | Pages → Home |
| About text and page headings | Pages → the relevant page |
| Skill groups, topics, stage, evidence | Skills & Technologies |
| Projects, status, screenshots, technical write-up | Projects |
| Project technology tags | Projects → Technologies, or the project editor |
| Dated learning entries | Learning Journey |
| Articles and technical categories | Posts → All Posts / Categories |
| Public email/social URLs/footer sentence | Settings → Portfolio profile |
| Contact explanation | Pages → Contact |
| Navigation and optional footer links | Appearance → Menus |
| Which published articles appear in Insights/Home | Settings → Portfolio profile → Articles collection |
| Site title/tagline and homepage/posts-page selection | Settings → General / Reading |
| Privacy notice | Pages → Privacy Policy — Draft for owner review |

The homepage feature sections and their navigational labels are presentation
components of the theme. Their content comes from the editable settings, Home
page, and published collections. Template changes belong in source review; no
business biography, contact address, or proficiency score needs a template edit.

The refined homepage presents **all published skill groups**, including their native
titles, topics, summaries, and stages, immediately after the introduction. Edit those
specializations under Skills & Technologies; there is no separate hardcoded list to
keep in sync. The learning section's surrounding copy is editable under Portfolio
profile. Current groups are learning focuses, not proficiency claims.

Version 1.0.1 changes only the fallback homepage introduction and adds fallback
learning-section copy. It does not migrate or overwrite saved options or records.
Any saved owner headline/introduction takes precedence. In this sandbox those fields
were unset, so the approved new defaults appear without a database write.

## Projects

Use the title, excerpt (card summary), main editor (technical explanation), featured
image (optional screenshot), and Technologies taxonomy. **Portfolio details** below
the editor supplies status, public source/demo URLs, and the verification note.

- Planned: an idea or proposed piece of work, not a finished case study.
- In Progress: active work that is not being presented as finished.
- Completed: select only after verifying the actual work and adding a verification
  note. Without that note the theme displays In Progress and excludes it from the
  Completed filter. A note is editorial evidence, not an automatic proof of quality.
- Verification notes are private to authorized REST edit contexts and administration;
  they are not printed publicly. Never put secrets or confidential client material
  in them anyway. The public write-up should contain only approved information.
- Source/demo links stay blank unless a real, public URL is supplied. The project's
  private GitHub development repository is deliberately not exposed on the website.

The collection has server-rendered search, status/technology filters, pagination,
and an honest empty state. It works without JavaScript. A missing screenshot uses
clearly decorative theme artwork, not a fictional product screenshot.

## Skills and the learning journey

Each Skills entry is a group with a title, summary/excerpt, topics separated by
commas, and a stage. All starter groups are **Learning focus**. Only choose
**Demonstrated in linked work** when a real evidence URL exists; otherwise the theme
labels it as learning. There are no proficiency percentages or certifications.
Use the native Order field to arrange groups, with lower values appearing first.

Each Learning Journey entry has a title, body, date, and kind. The date controls
chronological ordering; the theme intentionally displays month/year. The first
entry records October 2026 at month granularity, not an invented exact career-start
day. Add achievements only when they actually happen; do not pre-date future work.

## Articles and native blocks

Use standard Posts and categories such as Frontend, Backend, AI, Cloud, Security,
Development, SEO, and Learning. The starter categories sit under Insights. Publish
only reviewed copy; starter articles reflect the stated beginning and intentions,
not invented professional experience. The owner should approve their first-person
voice before public launch.

Use one descriptive page/post title; start body sections at Heading 2, then Heading
3 where needed. Add meaningful link text and appropriate alt text to content images.
WordPress supplies responsive image sizes. Reading time is an **estimate** calculated
from the article text at 200 words per minute, rounded up with a one-minute minimum;
it is not a claim about any particular reader's speed.

## Contact and privacy

Supply only a deliberately public contact address, different from the WordPress
administrator address. The plugin rejects the administrator email and the frontend
also checks it at render time. Optional social URLs must be actual public profiles.
No contact form, message database, or delivery integration is installed. The mail
link opens the visitor's email application; delivery has not been tested or promised.

The privacy page is intentionally a draft. Review actual hosting logs, retention,
cookies, enabled WordPress features, email arrangements, and any future integrations
before publishing it. Once that specific starter page is published, the footer links
to it automatically. Also select the approved notice in WordPress Settings → Privacy.

## Safe editing and preservation

Editors/authors retain WordPress's native object-level capabilities; only administrators
can manage the profile settings or import starter drafts. Nonces do not replace
permissions. The plugin uses WordPress revisions for content and never deletes data
on deactivation/uninstall. Back up before bulk changes or migrations.

The local sandbox retains the original synthetic WordPress starter content and an
unpublished QA fixture. Those records are not included in installation ZIPs. The
curated Insights collection keeps the legacy starter article out of the new portfolio
index without deleting or changing it. Do not reset data to make tests pass.
