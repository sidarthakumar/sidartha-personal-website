import { readdir, readFile, lstat } from 'node:fs/promises';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

export const repository = 'sidarthakumar/sidartha-personal-website';
export const deploymentBranch = 'hostinger-live';
const shaPattern = /^[a-f0-9]{40}$/;
const extensions = new Set([
  '.html',
  '.css',
  '.js',
  '.svg',
  '.xml',
  '.txt',
  '.png',
  '.jpg',
  '.webp',
  '.woff2',
]);

export async function collectPublicFiles(directory, revision, delivery) {
  if (!shaPattern.test(revision || ''))
    throw new Error('Invalid source revision.');
  if (!/^[0-9]+\.[0-9]+$/.test(delivery || ''))
    throw new Error('Invalid publication marker.');
  const files = [];
  let bytes = 0;
  async function visit(relative = '') {
    for (const entry of await readdir(path.join(directory, relative))) {
      const name = relative ? `${relative}/${entry}` : entry;
      if (
        (entry.startsWith('.') && name !== '.htaccess') ||
        !/^[A-Za-z0-9_.\/-]+$/.test(name)
      )
        throw new Error('Unexpected artifact path.');
      const file = path.join(directory, name);
      const info = await lstat(file);
      if (info.isSymbolicLink())
        throw new Error('Artifact symlinks are forbidden.');
      if (info.isDirectory()) {
        await visit(name);
        continue;
      }
      if (
        !info.isFile() ||
        (name !== '.htaccess' && !extensions.has(path.extname(name)))
      )
        throw new Error('Unexpected artifact file type.');
      const content = await readFile(file);
      bytes += content.length;
      if (
        content.length > 5 * 1024 * 1024 ||
        bytes > 20 * 1024 * 1024 ||
        files.length >= 2000
      )
        throw new Error('Artifact exceeds the publication limit.');
      files.push({ path: name, content });
    }
  }
  await visit();
  for (const required of [
    'index.html',
    '.htaccess',
    'robots.txt',
    'sitemap.xml',
    'feed.xml',
    'revision.txt',
    'delivery.txt',
  ]) {
    if (!files.some((file) => file.path === required))
      throw new Error('Required public artifact file is missing.');
  }
  if (
    files
      .find((file) => file.path === 'revision.txt')
      .content.toString()
      .trim() !== revision
  )
    throw new Error('Artifact revision mismatch.');
  if (
    files
      .find((file) => file.path === 'delivery.txt')
      .content.toString()
      .trim() !== delivery
  )
    throw new Error('Artifact publication marker mismatch.');
  return files.sort((a, b) => a.path.localeCompare(b.path));
}

export function githubClient(token) {
  if (!token) throw new Error('A temporary GitHub workflow token is required.');
  return async function request(method, endpoint, body, allowMissing = false) {
    const response = await fetch(
      `https://api.github.com/repos/${repository}${endpoint}`,
      {
        method,
        redirect: 'error',
        signal: AbortSignal.timeout(20000),
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/vnd.github+json',
          'X-GitHub-Api-Version': '2022-11-28',
          'Content-Type': 'application/json',
        },
        ...(body ? { body: JSON.stringify(body) } : {}),
      },
    );
    if (allowMissing && response.status === 404) {
      await response.body?.cancel();
      return null;
    }
    if (!response.ok) {
      await response.body?.cancel();
      throw new Error(
        `GitHub publication request failed (${response.status}).`,
      );
    }
    return response.json();
  };
}

export async function publishArtifact({
  directory,
  revision,
  delivery,
  request,
}) {
  const files = await collectPublicFiles(directory, revision, delivery);
  const currentMain = await request('GET', '/git/ref/heads/main');
  if (currentMain.object?.sha !== revision)
    throw new Error('Refusing publication of an outdated main revision.');
  const previous = await request(
    'GET',
    `/git/ref/heads/${deploymentBranch}`,
    null,
    true,
  );
  if (previous && !shaPattern.test(previous.object?.sha || ''))
    throw new Error('Invalid deployment branch revision.');
  const tree = [];
  for (const file of files) {
    const blob = await request('POST', '/git/blobs', {
      encoding: 'base64',
      content: file.content.toString('base64'),
    });
    if (!shaPattern.test(blob.sha || ''))
      throw new Error('Invalid uploaded blob revision.');
    tree.push({ path: file.path, mode: '100644', type: 'blob', sha: blob.sha });
  }
  // No base_tree: removed pages disappear from the new Git tree as well.
  const uploadedTree = await request('POST', '/git/trees', { tree });
  if (!shaPattern.test(uploadedTree.sha || ''))
    throw new Error('Invalid artifact tree revision.');
  if ((await request('GET', '/git/ref/heads/main')).object?.sha !== revision)
    throw new Error(
      'Main changed during publication; leaving the deployment branch unchanged.',
    );
  const commit = await request('POST', '/git/commits', {
    message: `Publish checked portfolio ${revision}`,
    tree: uploadedTree.sha,
    parents: previous ? [previous.object.sha] : [],
  });
  if (!shaPattern.test(commit.sha || ''))
    throw new Error('Invalid deployment commit revision.');
  if (previous)
    await request('PATCH', `/git/refs/heads/${deploymentBranch}`, {
      sha: commit.sha,
      force: false,
    });
  else
    await request('POST', '/git/refs', {
      ref: `refs/heads/${deploymentBranch}`,
      sha: commit.sha,
    });
  return {
    commit: commit.sha,
    source: revision,
    files: files.length,
    delivery,
    previous: previous?.object.sha || null,
  };
}

export async function rollbackArtifact({ publication, request }) {
  if (
    !shaPattern.test(publication.commit || '') ||
    !shaPattern.test(publication.previous || '')
  )
    throw new Error(
      'No previous deployment is available for automatic rollback.',
    );
  const head = await request('GET', `/git/ref/heads/${deploymentBranch}`);
  if (head.object?.sha !== publication.commit)
    throw new Error(
      'Deployment branch changed; refusing to overwrite a newer deployment.',
    );
  const previous = await request('GET', `/git/commits/${publication.previous}`);
  if (!shaPattern.test(previous.tree?.sha || ''))
    throw new Error('Invalid recovery tree.');
  const restored = await request('POST', '/git/commits', {
    message: `Restore checked portfolio after failed deployment ${publication.commit}`,
    tree: previous.tree.sha,
    parents: [publication.commit],
  });
  if (!shaPattern.test(restored.sha || ''))
    throw new Error('Invalid recovery commit.');
  await request('PATCH', `/git/refs/heads/${deploymentBranch}`, {
    sha: restored.sha,
    force: false,
  });
  return restored.sha;
}

if (
  process.argv[1] &&
  import.meta.url === pathToFileURL(process.argv[1]).href
) {
  try {
    if (process.env.GITHUB_REPOSITORY !== repository)
      throw new Error('Unexpected source repository.');
    const result = await publishArtifact({
      directory: 'site-dist',
      revision: process.env.SITE_REVISION,
      delivery: process.env.SITE_DELIVERY,
      request: githubClient(process.env.GITHUB_TOKEN),
    });
    const { writeFile } = await import('node:fs/promises');
    await writeFile('portfolio-publication.json', JSON.stringify(result));
    console.log(
      `Published ${result.files} public files to ${deploymentBranch}; source ${result.source}.`,
    );
  } catch (error) {
    console.error(error.message);
    process.exitCode = 1;
  }
}
