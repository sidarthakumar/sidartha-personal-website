import { spawnSync } from 'node:child_process';
import { setTimeout } from 'node:timers/promises';

const revision = process.env.SITE_REVISION;
if (!/^[a-f0-9]{40}$/.test(revision || ''))
  throw new Error('Invalid expected revision.');
const deadline = Date.now() + 5 * 60 * 1000;
let delivered = false;
async function matchesRevision(response) {
  if (!response.ok) {
    await response.body?.cancel();
    return false;
  }
  const reader = response.body.getReader();
  const chunks = [];
  let size = 0;
  while (true) {
    const { done, value } = await reader.read();
    if (done) break;
    size += value.length;
    if (size > 1024) {
      await reader.cancel();
      return false;
    }
    chunks.push(Buffer.from(value));
  }
  return Buffer.concat(chunks).toString().trim() === revision;
}
while (Date.now() < deadline) {
  try {
    const response = await fetch(
      `https://sidarthakumar.com/revision.txt?delivery=${revision}`,
      {
        redirect: 'error',
        signal: AbortSignal.timeout(10000),
        cache: 'no-store',
      },
    );
    if (await matchesRevision(response)) {
      delivered = true;
      break;
    }
  } catch {
    /* Hostinger may still be deploying; retry within the fixed deadline. */
  }
  await setTimeout(10000);
}
if (!delivered)
  throw new Error(
    'Hostinger did not publish the expected revision within five minutes. Inspect deployment history; no success is assumed.',
  );
const check = spawnSync(process.execPath, ['tools/portfolio/check-live.mjs'], {
  stdio: 'inherit',
  timeout: 180000,
});
if (check.error || check.status !== 0)
  throw new Error(
    'Published website verification failed. Review Hostinger and restore a known good revision if needed.',
  );
