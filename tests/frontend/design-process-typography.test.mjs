import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('design process step typography matches the approved prototype without changing geometry', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');

  assert.match(tokens, /--np-home-process-step-title-size:\s*16px;/);
  assert.match(tokens, /--np-home-process-step-description-size:\s*14px;/);

  assert.match(tokens, /--np-home-process-step-title-weight:\s*700;/);

  // Geometry must stay untouched for this typography-only change.
  assert.match(tokens, /--np-home-process-copy-min-height:\s*70px;/);
  assert.match(tokens, /--np-home-process-image-gap:\s*14px;/);
  assert.match(tokens, /--np-home-process-image-height:\s*195px;/);
});
