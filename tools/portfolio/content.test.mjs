import test from 'node:test';
import assert from 'node:assert/strict';
import { validateMarkdown } from './validate-content.mjs';
const content = (body) =>
  `---\ntitle: A useful project note\ndraft: true\n---\n${body}`;
test('prose with HTTPS links and code examples is accepted', () => {
  assert.doesNotThrow(() =>
    validateMarkdown(
      content(
        'A [source](https://github.com/sidarthakumar).\n```html\n<script>example only</script>\n```',
      ),
      'useful-project-note',
      'articles',
    ),
  );
});
test('raw executable HTML is rejected before it reaches the public output', () => {
  for (const body of [
    '<script>alert(1)</script>',
    '<img src=x onerror=alert(1)>',
    '<iframe src="https://example.com"></iframe>',
  ]) {
    assert.throws(() =>
      validateMarkdown(content(body), 'unsafe-content', 'articles'),
    );
  }
});
test('unsafe links and conflicting routes fail closed', () => {
  assert.throws(() =>
    validateMarkdown(
      content('[link](javascript:alert(1))'),
      'unsafe-content',
      'articles',
    ),
  );
  assert.throws(() => validateMarkdown(content('Text'), 'about', 'articles'));
  assert.throws(() =>
    validateMarkdown(content('Text'), '../private', 'projects'),
  );
});
