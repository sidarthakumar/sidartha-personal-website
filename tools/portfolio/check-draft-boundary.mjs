import { writeFile, readFile, rm, readdir } from 'node:fs/promises';
import { spawnSync } from 'node:child_process';
import assert from 'node:assert/strict';
import path from 'node:path';
const marker = 'draft-boundary-private-marker-29641';
const filename = `src/content/articles/${marker}.md`;
const build = () => {
  const result = spawnSync(
    process.execPath,
    ['tools/portfolio/astro.mjs', 'build'],
    { stdio: 'pipe' },
  );
  if (result.status !== 0) throw new Error('Draft-boundary build failed.');
};
try {
  await writeFile(
    filename,
    `---\ntitle: Unpublished boundary check\ndescription: An automated test of website draft exclusion.\ndate: 2026-10-18\ndraft: true\n---\n${marker}\n`,
    { flag: 'wx' },
  );
  build();
  const files = await readdir('site-dist', { recursive: true });
  for (const name of files.filter((name) => /\.(html|xml|txt)$/.test(name))) {
    assert.equal(
      (await readFile(path.join('site-dist', name), 'utf8')).includes(marker),
      false,
      `Draft leaked into ${name}`,
    );
  }
  console.log(
    'Draft exclusion verified in pages, listings, sitemap and feed. Public repository source remains public.',
  );
} finally {
  await rm(filename, { force: true });
  build();
}
