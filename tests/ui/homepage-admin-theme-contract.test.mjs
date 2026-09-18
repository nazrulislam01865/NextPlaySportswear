import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
const css = readFileSync(new URL('../../resources/css/admin/homepage.css', import.meta.url), 'utf8');
for (const token of [
  '--np-admin-home-navy: #061f44;',
  '--np-admin-home-orange: #cf5d38;',
  '--np-admin-home-orange-hover: #b94f2f;',
  '--np-admin-home-bg: #f4f6f8;',
  '--np-admin-home-surface: #ffffff;',
  '--np-admin-home-muted: #677386;',
  '--np-admin-home-border: #e1e6eb;',
  "--np-admin-home-font: 'Expose', ui-sans-serif, system-ui, sans-serif;",
]) assert.ok(css.includes(token), `Missing homepage admin token: ${token}`);
assert.ok(css.includes('.np-home-admin {'));
for (const line of css.split('\n')) {
  const trimmed=line.trim();
  if(trimmed.endsWith('{') && !trimmed.startsWith('@') && !trimmed.startsWith('.np-home-admin')) {
    assert.fail(`Unscoped homepage admin selector: ${trimmed}`);
  }
}
console.log('Homepage admin theme contract passed.');
