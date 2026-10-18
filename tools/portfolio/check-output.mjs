import { spawnSync } from 'node:child_process';
const result = spawnSync('python3', ['tools/portfolio/check-output.py'], {
  stdio: 'inherit',
});
if (result.error) throw result.error;
process.exit(result.status ?? 1);
