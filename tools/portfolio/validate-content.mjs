import { readFile, readdir } from 'node:fs/promises';
import { pathToFileURL } from 'node:url';
import path from 'node:path';

const reserved = new Set([
  'index',
  'about',
  'skills',
  'projects',
  'project',
  'journey',
  'insights',
  'contact',
  'privacy-policy',
  '404',
  'feed',
  'sitemap',
  'robots',
]);
export function validateMarkdown(source, slug, collection) {
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug))
    throw new Error('Use a lowercase, hyphenated content filename.');
  if (collection === 'articles' && reserved.has(slug))
    throw new Error('Article filename conflicts with a website route.');
  if (!source.startsWith('---\n'))
    throw new Error('Content needs YAML frontmatter.');
  const end = source.indexOf('\n---', 4);
  if (end < 0) throw new Error('Frontmatter is not closed.');
  const body = source.slice(end + 4);
  // Content is prose, never an executable template. Code fences are displayed as code.
  const prose = body
    .replace(/```[^\n]*\n[\s\S]*?```/g, '')
    .replace(/`[^`\n]*`/g, '');
  if (/<\/?[a-z!][^>]*>/i.test(prose))
    throw new Error('Raw HTML is not allowed in portfolio content.');
  if (
    /\]\(\s*(?:javascript|data|vbscript|file|http):/i.test(prose) ||
    /&#(?:x0*(?:6a|64|76)|0*(?:106|100|118));/i.test(prose)
  )
    throw new Error('Use HTTPS or local content links.');
  if (
    /^import\s|^export\s|<script|\{\s*(?:process|require|import)\b/im.test(
      prose,
    )
  )
    throw new Error('Executable content is not allowed.');
  if (!body.trim()) throw new Error('Content body is empty.');
}

export async function validateAll() {
  let count = 0;
  for (const collection of ['articles', 'projects']) {
    const directory = path.join('src/content', collection);
    for (const filename of await readdir(directory)) {
      if (!filename.endsWith('.md'))
        throw new Error(`Unexpected content type in ${collection}.`);
      try {
        validateMarkdown(
          await readFile(path.join(directory, filename), 'utf8'),
          filename.slice(0, -3),
          collection,
        );
      } catch (error) {
        throw new Error(`${collection}/${filename}: ${error.message}`);
      }
      count++;
    }
  }
  const profile = JSON.parse(await readFile('src/data/profile.json', 'utf8'));
  if (
    profile.name !== 'Sidartha Kumar' ||
    profile.email !== 'hello@sidarthakumar.com'
  )
    throw new Error('Owner identity changes need an explicit content update.');
  if (!/^https:\/\/github\.com\/sidarthakumar(?:\/|$)/.test(profile.github))
    throw new Error('Unexpected profile link.');
  const releases = JSON.parse(await readFile('src/data/releases.json', 'utf8'));
  const tags = new Set();
  for (const release of releases) {
    if (
      !release ||
      typeof release.title !== 'string' ||
      !release.title.trim() ||
      release.title.length > 160 ||
      typeof release.summary !== 'string' ||
      release.summary.length > 1200 ||
      typeof release.tag !== 'string' ||
      tags.has(release.tag)
    )
      throw new Error('Invalid or duplicate release entry.');
    const link = new URL(release.url);
    if (
      link.origin !== 'https://github.com' ||
      !link.pathname.startsWith(
        '/sidarthakumar/sidartha-personal-website/releases/tag/',
      ) ||
      link.search ||
      link.hash ||
      !Number.isFinite(Date.parse(release.date))
    )
      throw new Error(
        'Release link must belong to the selected public repository.',
      );
    tags.add(release.tag);
  }
  console.log(
    `Validated ${count} content files and ${releases.length} release entries.`,
  );
}
if (
  process.argv[1] &&
  import.meta.url === pathToFileURL(path.resolve(process.argv[1])).href
)
  await validateAll();
