import assert from 'node:assert/strict';
const origin = 'https://sidarthakumar.com';
const read = async (route, body = true) => {
  const response = await fetch(origin + route, {
    redirect: 'manual',
    signal: AbortSignal.timeout(20000),
    headers: { 'User-Agent': 'SidarthaPortfolio-Verification/2.0' },
  });
  if (!body) {
    await response.body?.cancel();
    return { status: response.status };
  }
  assert.equal(response.status, 200, `Unexpected status on ${route}`);
  const reader = response.body.getReader();
  let size = 0;
  const chunks = [];
  while (true) {
    const { done, value } = await reader.read();
    if (done) break;
    size += value.length;
    if (size > 1024 * 1024) {
      await reader.cancel();
      throw new Error('Response exceeded the size limit.');
    }
    chunks.push(Buffer.from(value));
  }
  return { text: Buffer.concat(chunks).toString(), headers: response.headers };
};
const home = await read('/');
assert.match(home.text, /<title>Sidartha Kumar/);
assert.ok(home.text.includes('https://sidarthakumar.com/'));
assert.equal(home.headers.get('x-content-type-options'), 'nosniff');
assert.ok(
  home.headers.get('content-security-policy')?.includes("script-src 'self'"),
);
assert.ok(home.headers.get('strict-transport-security')?.includes('max-age='));
for (const route of [
  '/about/',
  '/skills/',
  '/projects/',
  '/journey/',
  '/insights/',
  '/contact/',
  '/privacy-policy/',
  '/project/a-personal-space-on-the-web/',
  '/why-this-portfolio-keeps-content-in-github/',
])
  await read(route);
assert.ok(
  (await read('/contact/')).text.includes('mailto:hello@sidarthakumar.com'),
);
assert.ok(
  (await read('/robots.txt')).text.includes(
    'Sitemap: https://sidarthakumar.com/sitemap.xml',
  ),
);
assert.ok((await read('/sitemap.xml')).text.includes('<urlset'));
assert.ok((await read('/feed.xml')).text.includes('<rss'));
for (const route of [
  '/.git/config',
  '/.env',
  '/wp-login.php',
  '/a-page-that-does-not-exist-29641/',
]) {
  const result = await read(route, false);
  assert.ok(
    [403, 404].includes(result.status),
    `Unexpected public resource at ${route}`,
  );
}
if (process.env.SITE_REVISION)
  assert.equal(
    (await read('/revision.txt')).text.trim(),
    process.env.SITE_REVISION,
  );
console.log(
  'Published HTTPS pages, metadata, headers, contact link, sitemap, feed and protected-path status checks passed.',
);
