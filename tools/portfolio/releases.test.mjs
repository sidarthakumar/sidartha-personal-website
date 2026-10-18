import test from 'node:test';
import assert from 'node:assert/strict';
import { releaseEntry } from './sync-releases.mjs';
const release = {
  tag_name: 'v2.0.0',
  name: 'Portfolio milestone',
  published_at: '2026-10-18T10:00:00Z',
  html_url:
    'https://github.com/sidarthakumar/sidartha-personal-website/releases/tag/v2.0.0',
  body: 'Content and code now share a history.',
};
test('drafts and prereleases are not published as achievements', () => {
  assert.equal(releaseEntry({ ...release, draft: true }), null);
  assert.equal(releaseEntry({ ...release, prerelease: true }), null);
});
test('release URLs must belong to the explicitly selected repository', () => {
  assert.throws(() =>
    releaseEntry({
      ...release,
      html_url: 'https://example.com/releases/tag/v2',
    }),
  );
  assert.throws(() =>
    releaseEntry({
      ...release,
      html_url: 'https://github.com/other/repository/releases/tag/v2',
    }),
  );
});
test('release descriptions remain bounded text rather than executable content', () => {
  const entry = releaseEntry({
    ...release,
    body:
      '<script>untrusted()</script>\n[Source](https://example.com)\n' +
      'x'.repeat(2000),
  });
  assert.equal(entry.summary.includes('<script>'), false);
  assert.equal(entry.summary.includes('https://'), false);
  assert.ok(entry.summary.length <= 1200);
});
test('suspected credentials stop release propagation before publication', () => {
  assert.throws(
    () => releaseEntry({ ...release, body: 'ghp_' + 'A'.repeat(36) }),
    /stopped before publication/,
  );
});
