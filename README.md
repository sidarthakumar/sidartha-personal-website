# sidartha-personal-website
Personal WordPress portfolio and learning journey website.

The **Sidartha** custom theme and **Sidartha Portfolio** companion plugin provide
an editable personal portfolio, learning journey, and technical notebook. Development
is local only; starter content reflects the beginning of an independent career in
October 2026 without invented credentials, commercial work, or testimonials.

Read [AGENTS.md](AGENTS.md) for mandatory development/security rules and
[the development guide](docs/development.md) for the isolated WordPress sandbox,
startup procedure, timed smoke tests, and rollback instructions.

See [Task 2 verification](docs/verification-task2.md) for the timeout diagnosis,
repeated startup results, preservation checks, and the review status recorded then.

This repository contains source and documentation. Development secrets, WordPress
runtime files, and databases stay outside it. No production access or deployment
is part of the current workflow.

## Website deliverables

- Theme source: `wp-content/themes/sidartha/`
- Companion plugin: `wp-content/plugins/sidartha-portfolio/`
- Installable ZIPs and checksums: `dist/`
- [Installation and compatibility](docs/website-installation.md)
- [Content editing guide](docs/content-editing.md)
- [Security review](docs/website-security.md)
- [Verification and visual QA](docs/website-test-report.md)
- [Task 4 responsive refinement and new screenshots](docs/design-refinement-task4.md)
- [Future deployment, owner inputs, and rollback](docs/website-deployment.md)

Production uses standard WordPress/PHP and static assets. Node.js, Docker, and
browser-testing tools are development tools only; they are not production requirements.
The theme/plugin ZIPs contain no development configuration, credentials, or database.
The current review packages are theme **1.0.3** and companion plugin **1.0.1**;
prior packages are retained. The theme prefers the published policy selected in
Settings → Privacy. See [privacy footer verification](docs/privacy-footer-fix.md).

## Local development checks

```sh
python3 tools/dev/sync-sandbox.py
python3 -m unittest discover -s tools/dev -p 'test_*.py' -v
python3 tools/dev/smoke.py
python3 tools/dev/check-website.py
python3 tools/dev/check-http-security.py
python3 tools/build-packages.py
git diff --check
```

These commands assume the saved local runtime has been started and the theme/plugin
activated as described in the development guide. Browser checks use local Chromium
and a temporary copy of axe-core; neither is delivered to website visitors.

Theme 1.0.3 is prepared for separately approved staging testing. See
[package and rollback notes](docs/theme-1.0.3.md). No installation is automatic.
