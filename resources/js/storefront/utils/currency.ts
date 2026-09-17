export function formatCurrency(value: number | null | undefined, currency = 'USD'): string {
  if (value == null || Number.isNaN(Number(value))) return '';
  return new Intl.NumberFormat('en-US', { style: 'currency', currency, minimumFractionDigits: 2 }).format(Number(value));
}
