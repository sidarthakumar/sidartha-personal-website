import test from 'node:test';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
const base = {
  PATH: process.env.PATH,
  SITE_REVISION: 'a'.repeat(40),
  HOSTINGER_SSH_HOST: 'example.invalid',
  HOSTINGER_SSH_PORT: '65002',
  HOSTINGER_SSH_USER: 'synthetic_user',
  HOSTINGER_SSH_KEY: 'synthetic-placeholder',
  HOSTINGER_KNOWN_HOSTS: 'synthetic-host-key',
  HOSTINGER_SITE_ROOT: '/home/synthetic_user/domains/sidarthakumar.com',
};
const invoke = (env) =>
  spawnSync('python3', ['tools/portfolio/deploy.py'], {
    env,
    encoding: 'utf8',
    timeout: 10000,
  });
test('deployment stops before connecting when the secret configuration is missing', () => {
  const result = invoke({
    PATH: process.env.PATH,
    SITE_REVISION: 'a'.repeat(40),
  });
  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /not configured/);
});
test('deployment rejects a different domain or shell-shaped connection input', () => {
  for (const change of [
    { HOSTINGER_SITE_ROOT: '/home/synthetic_user/domains/unapproved.invalid' },
    { HOSTINGER_SSH_HOST: 'example.invalid; unexpected' },
    { HOSTINGER_SSH_PORT: '0' },
    { HOSTINGER_SSH_USER: '../unexpected' },
  ]) {
    const result = invoke({ ...base, ...change });
    assert.notEqual(result.status, 0);
    assert.match(result.stderr, /Invalid SSH|approved domain/);
    assert.equal(
      (result.stdout + result.stderr).includes(base.HOSTINGER_SSH_KEY),
      false,
    );
  }
});
