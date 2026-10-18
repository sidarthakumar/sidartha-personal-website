# Sidartha Kumar — personal portfolio

Astro generates this portfolio from Markdown content, structured profile data and reusable templates. GitHub tracks content and code; the delivery workflow checks the public output before Hostinger publishing.

**Migration status:** prepared on the Astro branch. Hostinger access, backup/recovery, repository visibility and initial rollout require actual verification. The original AGENTS.md remains unchanged; its persistent instruction amendment is pending approval.

## Local development

Use Node 24 (.nvmrc).

```sh
npm ci --ignore-scripts
npm run dev
npm test
npm run check:secrets
npm run build
node tools/portfolio/check-draft-boundary.mjs
python3 tools/portfolio/check-output.py
```

The preview binds to 127.0.0.1. The Astro wrapper disables telemetry. Build output is **site-dist/**; only that directory belongs on the public server.

## Updating content

| Change | Source |
| --- | --- |
| Profile, experience, learning, milestones | src/data/profile.json |
| Articles | src/content/articles/*.md |
| Projects | src/content/projects/*.md |
| Public release milestones | src/data/releases.json, generated from this repository |
| Design | src/layouts/, src/components/, src/styles/ |

See [content instructions](docs/portfolio-content.md) and [deployment/recovery](docs/portfolio-deployment.md). Drafts stay off the website, lists, sitemap and RSS, but **draft source in a public repository is public**. Confidential material belongs outside it.

## Delivery

[Portfolio checks and delivery](.github/workflows/portfolio.yml) runs on PRs, main pushes and published releases. Only this repository supplies release updates. Drafts, prereleases and private repositories are excluded. Each trusted build reads published releases using read-only GitHub access. Release notes remain sourced in GitHub; their derived summary input is retained with the build artifact. No feed process writes to main or bypasses its checks.

Hostinger deployment is gated by HOSTINGER_DEPLOY_ENABLED and protected production secrets. The workflow’s existence is not evidence of connection or successful deployment.

## Historical WordPress source

wp-content/, dist/, tools/dev/, tools/ci/ and earlier guides remain for historical reference and recovery. They do not generate or serve the Astro version. Previous workflows are retained in .github/legacy/. [Original overview](docs/legacy-wordpress-readme.md).

The existing GPL license remains. No visitor analytics, paid services, external fonts or contact-form backend are added.
