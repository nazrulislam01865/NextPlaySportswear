import type { NavigationItem } from '../../../types/navigation';

export interface MegaMenuLink {
  label: string;
  href: string;
  lookupLabels?: string[];
}

export interface MegaMenuColumnConfig {
  title: string;
  links: MegaMenuLink[];
}

export interface MegaMenuConfig {
  key: string;
  topChoices: {
    eyebrow: string;
    links: MegaMenuLink[];
  };
  columns: MegaMenuColumnConfig[];
  promo: {
    image: string;
    alt: string;
    label: string;
    href: string;
    lookupLabels?: string[];
  };
}

export interface DesktopNavItem {
  key: string;
  label: string;
  href: string;
  lookupLabels?: string[];
  megaMenuKey?: string;
}

export const DESKTOP_NAV_ITEMS: DesktopNavItem[] = [
  { key: 'shop', label: 'SHOP', href: '/products', lookupLabels: ['Shop Products', 'All Products'], megaMenuKey: 'shop' },
  { key: 'sports', label: 'SPORTS', href: '/categories', lookupLabels: ['Sports'] },
  { key: 'men', label: 'MEN', href: '/men', lookupLabels: [] },
  { key: 'women', label: 'WOMEN', href: '/products?q=women', lookupLabels: ['Women'] },
  { key: 'kids', label: 'KIDS', href: '/products?q=kids', lookupLabels: ['Kids'] },
  { key: 'custom-teamwear', label: 'CUSTOM TEAMWEAR', href: '/bulk-quote', lookupLabels: ['Custom Teamwear', 'Bulk Quote'] },
  { key: 'explore', label: 'EXPLORE', href: '/about-us', lookupLabels: ['Explore', 'About Us'] },
];

export const SHOP_MEGA_MENU: MegaMenuConfig = {
  key: 'shop',
  topChoices: {
    eyebrow: 'Top Choices',
    links: [
      { label: 'BEST SELLERS', href: '/products?sort=best-selling', lookupLabels: ['Best Sellers', 'Best Selling'] },
      { label: 'ON SALE', href: '/products?on_sale=1', lookupLabels: ['On Sale', 'Sale'] },
      { label: 'FEATURED FOR YOU', href: '/products?featured=1', lookupLabels: ['Featured For You', 'Featured'] },
      { label: 'SUMMER 2026', href: '/products?q=summer', lookupLabels: ['Summer 2026', 'Summer'] },
    ],
  },
  columns: [
    {
      title: 'NEW ARRIVALS',
      links: [
        { label: "New for Men's", href: '/products?q=men&sort=newest' },
        { label: "New for Women's", href: '/products?q=women&sort=newest' },
        { label: "New for Kid's", href: '/products?q=kids&sort=newest' },
        { label: 'New in Accessories', href: '/products?q=accessories&sort=newest', lookupLabels: ['Accessories'] },
      ],
    },
    {
      title: 'MEN',
      links: [
        { label: 'Jerseys', href: '/products?q=men+jersey', lookupLabels: ['Jerseys'] },
        { label: 'Shorts', href: '/products?q=men+shorts', lookupLabels: ['Shorts'] },
        { label: 'Training Wear', href: '/products?q=men+training', lookupLabels: ['Training Wear'] },
        { label: 'Uniforms', href: '/products?q=men+uniform', lookupLabels: ['Uniforms'] },
        { label: 'Accessories', href: '/products?q=men+accessories', lookupLabels: ['Accessories'] },
      ],
    },
    {
      title: 'WOMEN',
      links: [
        { label: 'Jerseys', href: '/products?q=women+jersey', lookupLabels: ['Jerseys'] },
        { label: 'Shorts', href: '/products?q=women+shorts', lookupLabels: ['Shorts'] },
        { label: 'Training Wear', href: '/products?q=women+training', lookupLabels: ['Training Wear'] },
        { label: 'Uniforms', href: '/products?q=women+uniform', lookupLabels: ['Uniforms'] },
        { label: 'Accessories', href: '/products?q=women+accessories', lookupLabels: ['Accessories'] },
      ],
    },
    {
      title: 'KIDS',
      links: [
        { label: 'Jerseys', href: '/products?q=kids+jersey', lookupLabels: ['Jerseys'] },
        { label: 'Shorts', href: '/products?q=kids+shorts', lookupLabels: ['Shorts'] },
        { label: 'Training Wear', href: '/products?q=kids+training', lookupLabels: ['Training Wear'] },
        { label: 'Uniforms', href: '/products?q=kids+uniform', lookupLabels: ['Uniforms'] },
        { label: 'Accessories', href: '/products?q=kids+accessories', lookupLabels: ['Accessories'] },
      ],
    },
  ],
  promo: {
    image: '/images/storefront-vue/navigation/shop-mega-promo.jpg',
    alt: 'Basketball players competing on an outdoor court',
    label: 'Shop All Products',
    href: '/products',
    lookupLabels: ['Shop Products', 'All Products'],
  },
};

function normalize(value: string): string {
  return value.toLowerCase().replace(/[^a-z0-9]+/g, '');
}

function flatten(items: NavigationItem[]): NavigationItem[] {
  return items.flatMap((item) => [item, ...flatten(item.children ?? [])]);
}

export function resolveNavigationUrl(
  navigation: NavigationItem[] | undefined,
  fallback: string,
  labels: string[] = [],
): string {
  const candidates = flatten(navigation ?? []);
  const needles = labels.map(normalize).filter(Boolean);
  if (!needles.length) return fallback;

  const exact = candidates.find((item) => needles.includes(normalize(item.label || '')));
  if (exact?.url && exact.url !== '#') return exact.url;

  const partial = candidates.find((item) => {
    const candidate = normalize(item.label || '');
    return needles.some((needle) => candidate.includes(needle) || needle.includes(candidate));
  });

  return partial?.url && partial.url !== '#' ? partial.url : fallback;
}
