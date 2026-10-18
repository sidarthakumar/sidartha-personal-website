import { readFile, writeFile } from 'node:fs/promises';
import { pathToFileURL } from 'node:url';
import path from 'node:path';

export function releaseEntry(release) {
  if (
    !release ||
    release.draft ||
    release.prerelease ||
    !Number.isFinite(Date.parse(release.published_at))
  )
    return null;
  const url = new URL(release.html_url);
  if (
    url.origin !== 'https://github.com' ||
    !url.pathname.startsWith(
      '/sidarthakumar/sidartha-personal-website/releases/tag/',
    ) ||
    url.search ||
    url.hash
  )
    throw new Error('Unexpected release destination.');
  const source = String(
    release.body ||
      'A new project milestone. Read the release details on GitHub.',
  );
  const credentialPatterns = [
    /-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/,
    /(?:gh[pousr]_[A-Za-z0-9]{30,}|github_pat_[A-Za-z0-9_]{30,})/,
    /AKIA[0-9A-Z]{16}/,
    /\bsk-(?:proj-)?[A-Za-z0-9_-]{32,}/,
    /https?:\/\/[^\s/:]+:[^\s/@]+@/,
  ];
  if (credentialPatterns.some((pattern) => pattern.test(source)))
    throw new Error(
      'Release notes match a credential pattern; automatic portfolio update stopped before publication.',
    );
  const summary = source
    .replace(/<!--[\s\S]*?-->/g, '')
    .replace(/<[^>]*>/g, '')
    .replace(/!\[[^\]]*\]\([^)]*\)/g, '')
    .replace(/\[([^\]]+)\]\([^)]*\)/g, '$1')
    .replace(/https?:\/\/\S+/g, '')
    .replace(/[\u0000-\u0008\u000b\u000c\u000e-\u001f]/g, '')
    .trim()
    .slice(0, 1200);
  return {
    tag: String(release.tag_name).slice(0, 100),
    title: String(release.name || release.tag_name).slice(0, 160),
    date: release.published_at,
    url: release.html_url,
    summary,
  };
}

async function sync() {
  if (
    process.env.GITHUB_REPOSITORY &&
    process.env.GITHUB_REPOSITORY !== 'sidarthakumar/sidartha-personal-website'
  )
    throw new Error('Repository is not approved for public updates.');
  const base =
    'https://api.github.com/repos/sidarthakumar/sidartha-personal-website';
  const api = async (route) => {
    const response = await fetch(base + route, {
      redirect: 'error',
      signal: AbortSignal.timeout(20000),
      headers: {
        Accept: 'application/vnd.github+json',
        ...(process.env.GITHUB_TOKEN
          ? { Authorization: `Bearer ${process.env.GITHUB_TOKEN}` }
          : {}),
      },
    });
    if (response.status === 404 && route === '') return null;
    if (!response.ok)
      throw new Error(
        `GitHub public release read failed (${response.status}).`,
      );
    return response.json();
  };
  const repo = await api('');
  if (!repo || repo.private || repo.visibility !== 'public') {
    // A private source is never copied into a public website or treated as public progress.
    console.log(
      'Public release synchronization skipped: the selected repository is not public.',
    );
    return;
  }
  const entries = (await api('/releases?per_page=100'))
    .map(releaseEntry)
    .filter(Boolean)
    .slice(0, 30);
  const content = JSON.stringify(entries, null, 2) + '\n';
  if (content !== (await readFile('src/data/releases.json', 'utf8')))
    await writeFile('src/data/releases.json', content);
  console.log(
    `Public release input refreshed: ${entries.length} milestones. No repository writes performed.`,
  );
}
if (
  process.argv[1] &&
  import.meta.url === pathToFileURL(path.resolve(process.argv[1])).href
)
  await sync();
