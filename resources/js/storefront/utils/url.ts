export function safeHref(value: string | null | undefined, fallback = '#'): string {
  const url = String(value ?? '').trim();
  if (!url) return fallback;
  if (url.startsWith('/') || url.startsWith('#') || /^https?:\/\//i.test(url)) return url;
  return fallback;
}
