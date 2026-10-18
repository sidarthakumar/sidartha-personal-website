---
title: A personal space on the web
description: A portfolio built around reusable content, a lightweight static website, and a GitHub publishing workflow.
date: 2026-10-10
updated: 2026-10-18
status: In progress
technologies: [Astro, HTML / CSS, JavaScript, GitHub Actions]
repository: https://github.com/sidarthakumar/sidartha-personal-website
demo: https://sidarthakumar.com
featured: true
draft: false
---

## The problem

My portfolio needs to grow as my work changes. Updating a project should not mean adjusting several pages by hand or uploading files to a hosting panel. I also want the website’s code and content to have a clear history in GitHub.

The earlier version used a custom WordPress theme and a companion portfolio plugin. Its dark background, lavender accents, and personal logbook structure form the visual starting point for this version.

## My direction for the project

The goal is to keep that identity while separating the content from the layout. Projects and articles are stored as Markdown files. Profile details, experience, learning areas, and milestones have a shared structured source. Reusable templates turn those sources into the homepage and individual pages.

That separation matters when working with AI: an article can be added without asking an assistant to rebuild a page or copy the same details into several places.

## Technical choices

- **Astro static output:** the hosting server serves generated pages, styles, and small browser scripts. It does not need a running application database for the portfolio.
- **GitHub as the source:** content and code travel together, so changes can be reviewed and traced to a commit.
- **Automated checks before deployment:** the workflow checks content, builds pages, inspects the output, and scans for common secret patterns before publishing.
- **Hostinger hosting:** the deployment is designed for the existing hosting account and domain.
- **Minimal browser code:** navigation, project filters, and theme-free page reading work without a large client-side framework.

## Content and privacy

The site uses a monogram instead of a generated personal photograph. Contact opens the visitor’s email application. No visitor registration, public comments, advertising pixels, or visitor analytics are included in this version.

Existing public page addresses are retained where possible. Redirects cover supported legacy archive addresses. Neither the WordPress database nor deployment credentials belong in the public repository or generated website.

## What this demonstrates

This project documents the structure and decisions behind my own portfolio. It is a place to make the publishing process more maintainable and to show future work with clear context. The project remains in progress as the migration and hosting setup are completed. I will add measured results and further implementation notes when they are available.
