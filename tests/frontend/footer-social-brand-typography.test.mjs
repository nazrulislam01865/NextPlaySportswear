import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

const tokensFile = 'resources/js/storefront/styles/tokens.css';
const footerFile = 'resources/js/storefront/components/layout/StorefrontFooter.vue';
const megaFooterFile = 'resources/js/storefront/components/layout/navigation/MegaMenuFooter.vue';
const socialIconFile = 'resources/js/storefront/components/common/SocialBrandIcon.vue';

test('footer typography matches approved prototype sizing', () => {
  const tokens = read(tokensFile);
  for (const [token, value] of [
    ['--np-footer-brand-size', '14px'],
    ['--np-footer-heading-size', '16px'],
    ['--np-footer-link-size', '14px'],
    ['--np-footer-signup-title-size', '14px'],
    ['--np-footer-signup-button-size', '14px'],
  ]) {
    assert.match(tokens, new RegExp(`${token}:\\s*${value.replace('.', '\\.')}\\s*;`));
  }
});

test('footer and mega menu share the same brand social icon component and size', () => {
  assert.equal(fs.existsSync(path.join(root, socialIconFile)), true, 'SocialBrandIcon.vue must exist');
  const footer = read(footerFile);
  const megaFooter = read(megaFooterFile);
  const icon = read(socialIconFile);
  const tokens = read(tokensFile);

  assert.match(tokens, /--np-social-brand-icon-size:\s*20px;/);
  assert.match(footer, /import\s+SocialBrandIcon\s+from\s+['"]\.\.\/common\/SocialBrandIcon\.vue['"]/);
  assert.match(megaFooter, /import\s+SocialBrandIcon\s+from\s+['"]\.\.\/\.\.\/common\/SocialBrandIcon\.vue['"]/);
  assert.doesNotMatch(footer, /import\s*\{[^}]*\b(Youtube|Instagram|Facebook|Music2)\b[^}]*\}\s*from\s*['"]lucide-vue-next['"]/);
  assert.doesNotMatch(megaFooter, /import\s*\{[^}]*\b(Youtube|Instagram|Facebook|Music2)\b[^}]*\}\s*from\s*['"]lucide-vue-next['"]/);
  assert.doesNotMatch(footer, /<(Youtube|Instagram|Facebook|Music2)\b/);
  assert.doesNotMatch(megaFooter, /<(Youtube|Instagram|Facebook|Music2)\b/);

  for (const network of ['youtube', 'instagram', 'facebook', 'tiktok']) {
    assert.match(footer, new RegExp(`<SocialBrandIcon\\s+network=['"]${network}['"]`));
    assert.match(megaFooter, new RegExp(`<SocialBrandIcon\\s+network=['"]${network}['"]`));
  }

  assert.match(icon, /width:\s*var\(--np-social-brand-icon-size\)/);
  assert.match(icon, /height:\s*var\(--np-social-brand-icon-size\)/);
  assert.match(icon, /network === 'youtube'/);
  assert.match(icon, /network === 'instagram'/);
  assert.match(icon, /network === 'facebook'/);
  assert.match(icon, /network === 'tiktok'/);
});
