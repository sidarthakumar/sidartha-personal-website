import { readFile } from 'node:fs/promises';
import {
  githubClient,
  repository,
  rollbackArtifact,
} from './publish-artifact.mjs';

try {
  if (process.env.GITHUB_REPOSITORY !== repository)
    throw new Error('Unexpected repository.');
  const publication = JSON.parse(
    await readFile('portfolio-publication.json', 'utf8'),
  );
  const restored = await rollbackArtifact({
    publication,
    request: githubClient(process.env.GITHUB_TOKEN),
  });
  console.log(
    `Requested code-only rollback ${restored}. Hostinger must deploy it; inspect deployment history and live revision. The failed workflow remains failed.`,
  );
} catch (error) {
  console.error(`Automatic code rollback was not completed: ${error.message}`);
  process.exitCode = 1;
}
