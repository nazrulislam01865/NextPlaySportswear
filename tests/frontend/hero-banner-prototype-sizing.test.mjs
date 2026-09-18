import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

test('hero banner typography and CTAs match the compact approved prototype without changing banner geometry', () => {
  const tokens = read('resources/js/storefront/styles/tokens.css');
  const hero = read('resources/js/storefront/features/home/components/HomeHero.vue');

  for (const declaration of [
    '--np-home-hero-title-size: 40px;',
    '--np-home-hero-copy-size: 16px;',
    '--np-home-hero-action-size: 13px;',
    '--np-home-hero-action-min-width: 150px;',
    '--np-home-hero-action-height: 48px;',
    '--np-home-hero-action-padding-inline: 20px;',
    '--np-home-hero-control-size: 48px;',
    '--np-home-hero-control-icon-size: 18px;',
  ]) {
    assert.ok(tokens.includes(declaration), `Missing compact hero token: ${declaration}`);
  }

  assert.match(hero, /\.np-hero__actions\s*\{[^}]*margin-top:\s*26px;/s);

  // The request is typography/button sizing only; banner height and content anchoring stay untouched.
  assert.match(tokens, /--np-hero-height:\s*clamp\(590px,\s*40\.25vw,\s*824px\);/);
  assert.match(hero, /bottom:\s*clamp\(58px,\s*6\.2vw,\s*92px\);/);
  assert.match(hero, /width:\s*min\(650px,\s*calc\(100% - \(var\(--np-first-fold-gutter\) \* 2\)\)\);/);
});
