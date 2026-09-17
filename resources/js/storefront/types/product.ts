export interface StorefrontProduct {
  id: number;
  slug: string;
  title?: string;
  short_title?: string;
  summary?: string;
  sku?: string;
  category?: string;
  subcategory?: string;
  sport?: string;
  price?: string;
  display_unit_price?: number | null;
  discount_price?: number | null;
  original_price?: number | null;
  original_price_label?: string | null;
  display_compare_at_price?: number | null;
  compare_at_price_label?: string | null;
  discount_percentage?: number | null;
  currency?: string;
  image?: string;
  gallery?: Array<string | { url?: string | null; alt?: string | null }>;
  alt?: string;
  url?: string;
  tag?: string | null;
  tag_color?: string | null;
  is_customizable?: boolean;
  minimum_quantity?: number | null;
}
