import type { HomeSection, HomeSectionItem } from '../types/home.types';

export function sectionIsActive(section?: HomeSection): boolean {
  return section?.is_active !== false;
}

export function sectionTitle(section: HomeSection | undefined, fallback: string): string {
  const value = String(section?.title ?? '').trim();
  return value || fallback;
}

export function sectionItems<T extends HomeSectionItem>(section?: HomeSection): T[] {
  return Array.isArray(section?.items) ? section.items as T[] : [];
}

export function sectionSettings<T extends Record<string, unknown>>(section: HomeSection | undefined, fallback: T): T {
  return { ...fallback, ...((section?.settings ?? {}) as Partial<T>) } as T;
}
