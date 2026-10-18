import test from 'node:test';
import assert from 'node:assert/strict';
import { mkdtemp, writeFile, symlink, rm } from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import {
  collectPublicFiles,
  publishArtifact,
  rollbackArtifact,
} from './publish-artifact.mjs';

const revision = 'a'.repeat(40);
const delivery = '123456.1';
const previous = 'b'.repeat(40);
const uploaded = 'c'.repeat(40);
async function artifact(t) {
  const directory = await mkdtemp(
    path.join(os.tmpdir(), 'portfolio-artifact-'),
  );
  t.after(() => rm(directory, { recursive: true, force: true }));
  for (const name of [
    'index.html',
    '.htaccess',
    'robots.txt',
    'sitemap.xml',
    'feed.xml',
  ])
    await writeFile(path.join(directory, name), 'public fixture');
  await writeFile(path.join(directory, 'revision.txt'), revision);
  await writeFile(path.join(directory, 'delivery.txt'), delivery);
  return directory;
}

test('publication rejects secrets, executable files, symlinks and mismatched revisions before any API call', async (t) => {
  const directory = await artifact(t);
  for (const name of ['.env', 'wp-config.php']) {
    await writeFile(path.join(directory, name), 'fixture');
    await assert.rejects(
      collectPublicFiles(directory, revision, delivery),
      /Unexpected artifact/,
    );
    await rm(path.join(directory, name));
  }
  await symlink(
    path.join(directory, 'index.html'),
    path.join(directory, 'linked.html'),
  );
  await assert.rejects(
    collectPublicFiles(directory, revision, delivery),
    /symlinks/,
  );
  await rm(path.join(directory, 'linked.html'));
  await assert.rejects(
    collectPublicFiles(directory, revision, '999.1'),
    /publication marker mismatch/,
  );
  await assert.rejects(
    collectPublicFiles(directory, revision, 'invalid'),
    /Invalid publication marker/,
  );
  await assert.rejects(
    collectPublicFiles(directory, previous, delivery),
    /revision mismatch/,
  );
});

test('outdated source cannot start any publication writes', async (t) => {
  const directory = await artifact(t);
  const calls = [];
  await assert.rejects(
    publishArtifact({
      directory,
      revision,
      delivery,
      request: async (method, endpoint) => {
        calls.push({ method, endpoint });
        return { object: { sha: previous } };
      },
    }),
    /outdated main/,
  );
  assert.deepEqual(calls, [{ method: 'GET', endpoint: '/git/ref/heads/main' }]);
});

test('publication replaces the complete public tree and fast-forwards only the deployment branch', async (t) => {
  const directory = await artifact(t);
  const calls = [];
  const request = async (method, endpoint, body) => {
    calls.push({ method, endpoint, body });
    if (endpoint === '/git/ref/heads/main')
      return { object: { sha: revision } };
    if (endpoint === '/git/ref/heads/hostinger-live')
      return { object: { sha: previous } };
    return { sha: uploaded };
  };
  const result = await publishArtifact({
    directory,
    revision,
    delivery,
    request,
  });
  const tree = calls.find((call) => call.endpoint === '/git/trees').body;
  assert.equal('base_tree' in tree, false);
  assert.equal(tree.tree.length, 7);
  assert.deepEqual(
    calls.find((call) => call.endpoint === '/git/commits').body.parents,
    [previous],
  );
  assert.deepEqual(calls.at(-1), {
    method: 'PATCH',
    endpoint: '/git/refs/heads/hostinger-live',
    body: { sha: uploaded, force: false },
  });
  assert.equal(result.previous, previous);
  assert.equal(
    calls.some(
      (call) => call.method !== 'GET' && call.endpoint.includes('/heads/main'),
    ),
    false,
  );
});

test('a source update during upload leaves the deployment branch untouched', async (t) => {
  const directory = await artifact(t);
  let reads = 0;
  const calls = [];
  await assert.rejects(
    publishArtifact({
      directory,
      revision,
      delivery,
      request: async (method, endpoint, body) => {
        calls.push({ method, endpoint, body });
        if (endpoint === '/git/ref/heads/main')
          return { object: { sha: ++reads === 1 ? revision : previous } };
        if (endpoint === '/git/ref/heads/hostinger-live') return null;
        return { sha: uploaded };
      },
    }),
    /Main changed/,
  );
  assert.equal(
    calls.some(
      (call) =>
        call.endpoint === '/git/commits' || call.endpoint.includes('/git/refs'),
    ),
    false,
  );
});

test('rollback refuses to overwrite a deployment it no longer owns', async () => {
  const calls = [];
  await assert.rejects(
    rollbackArtifact({
      publication: { commit: uploaded, previous },
      request: async (method) => {
        calls.push(method);
        return { object: { sha: revision } };
      },
    }),
    /newer deployment/,
  );
  assert.deepEqual(calls, ['GET']);
  await assert.rejects(
    rollbackArtifact({
      publication: { commit: uploaded, previous: null },
      request: () => assert.fail('No API request expected'),
    }),
    /No previous deployment/,
  );
});

test('rollback creates a new commit using the previous complete tree without rewriting history', async () => {
  const calls = [];
  await rollbackArtifact({
    publication: { commit: uploaded, previous },
    request: async (method, endpoint, body) => {
      calls.push({ method, endpoint, body });
      if (endpoint === '/git/ref/heads/hostinger-live')
        return { object: { sha: uploaded } };
      if (endpoint === `/git/commits/${previous}`)
        return { tree: { sha: revision } };
      return { sha: previous };
    },
  });
  assert.deepEqual(calls[2].body.parents, [uploaded]);
  assert.equal(calls[2].body.tree, revision);
  assert.deepEqual(calls.at(-1).body, { sha: previous, force: false });
});
