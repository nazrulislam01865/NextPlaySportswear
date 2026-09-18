import type { StorefrontCategory } from '../../../types/category';
import type { StorefrontProduct } from '../../../types/product';

export interface MenPageData {
  categories: StorefrontCategory[];
  products: StorefrontProduct[];
  productCount: number;
  currentPage: number;
  lastPage: number;
  perPage: number;
}

export interface CatalogCategoryApiItem {
  id?: number;
  slug?: string;
  title?: string;
  short_title?: string;
  description?: string | null;
  product_count?: number;
  media?: {
    image?: string | null;
    banner?: string | null;
    alt?: string | null;
    banner_alt?: string | null;
  };
  cta?: {
    label?: string | null;
    url?: string | null;
  };
}

export interface CatalogCategoryIndexData {
  categories: CatalogCategoryApiItem[];
}

export interface CatalogProductApiItem {
  id?: number | null;
  slug?: string;
  title?: string;
  short_title?: string;
  summary?: string;
  sku?: string;
  category?: {
    name?: string | null;
    subcategory_name?: string | null;
  };
  pricing?: {
    currency?: string;
    display?: string;
    unit_amount?: number | null;
    discount_amount?: number | null;
    original_amount?: number | null;
    original_label?: string | null;
    compare_at_amount?: number | null;
    compare_at_label?: string | null;
    discount_percentage?: number | null;
  };
  quantity?: {
    minimum?: number | null;
  };
  media?: {
    image?: string | null;
    alt?: string | null;
    gallery?: Array<string | { url?: string | null; alt?: string | null }>;
  };
  flags?: {
    customizable?: boolean;
  };
  badge?: {
    label?: string | null;
    color?: string | null;
  };
  url?: string | null;
}
