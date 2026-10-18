---
title: Why this portfolio keeps content in GitHub
description: The practical reasons for keeping portfolio content beside its code, and the boundaries that make AI-assisted updates manageable.
date: 2026-10-18
category: Development
draft: false
---

The main requirement for rebuilding this portfolio was simple: adding work should be easy. I wanted to describe a project or provide an article, then have the website update without manually editing page layouts or uploading files.

That requirement shaped how this version is organised. The design stays close to the earlier WordPress site, but projects, articles, and profile details now have file-based sources alongside the application code.

## A small content model

A project has a title, description, date, status, technology list, optional repository and demo links, and a body. An article has its title, description, dates, category, and body. Both can be marked as drafts.

The important part is that the model is shared. The homepage and project listing read from the same project collection. An article’s description is also used when preparing its search and social metadata. This removes several places where a manual update could drift out of sync.

For an AI assistant, a predictable structure is easier to work with than a request to edit arbitrary HTML. The instructions can say which file to change, which fields are required, and what checks must pass.

## Drafts need a real boundary

A draft is excluded during the build. It should not appear in navigation, search listings, feeds, or the generated page files.

There is an important distinction, however: a public GitHub repository can still expose the source of a draft. A draft flag controls website publication; it is not a way to store confidential writing. Private material needs to stay outside the public repository until it is ready to share.

## Automation needs useful stopping points

The workflow is designed to validate content, generate the website, and inspect the built files before deployment. Missing metadata, invalid links, unsafe content patterns, and common secret patterns should prevent a release from progressing.

These checks reduce avoidable mistakes. They do not prove every sentence is true or every security risk has been eliminated. The source material still matters: personal experience comes from my own details, and project updates come from explicitly selected repositories.

## Release notes are source material

For automatic project updates, a GitHub release provides a defined milestone with a title, version, date, and description. The portfolio can show that information with a link back to the release. It does not need to infer a finished project from a stream of commits.

For this portfolio, only its own repository is selected initially. Other repositories can be added later through a deliberate configuration change. Private repositories are excluded from the public feed.

## The hosting boundary

Astro generates static HTML, styles, and the small scripts used by the interface. The deployment sends the built public files to Hostinger. Source files, Git history, WordPress configuration, databases, backups, and deployment credentials are not part of that output.

This is the main benefit of the approach for my portfolio: a consistent path from a piece of writing or a project update to a versioned website, with fewer manual publishing steps and a clearer record of what changed.
