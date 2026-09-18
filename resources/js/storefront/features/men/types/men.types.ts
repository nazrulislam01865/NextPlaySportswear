import type { StorefrontCategory } from '../../../types/category';
import type { StorefrontProduct } from '../../../types/product';

export type MenFilterArrayKey = 'categories' | 'sports' | 'product_types' | 'colors' | 'materials' | 'moq' | 'customization' | 'availability';
export type MenPriceFilterKey = 'min_price' | 'max_price';
export type MenFilterKey = MenFilterArrayKey | MenPriceFilterKey;

export interface MenCatalogFilters {
  categories: number[];
  sports: number[];
  product_types: string[];
  colors: string[];
  materials: string[];
  min_price: number | null;
  max_price: number | null;
  moq: string[];
  customization: string[];
  availability: string[];
}

export interface MenFilterOption {
  value: string;
  label: string;
  count: number;
  color_hex?: string | null;
}

export interface MenSportFilterOption {
  id: number;
  label: string;
  slug?: string;
  count: number;
  selected?: boolean;
}

export interface MenCategoryFilterOption {
  id: number;
  label: string;
  slug?: string;
  count: number;
  selected?: boolean;
  has_selected_child?: boolean;
  children: Array<{
    id: number;
    label: string;
    slug?: string;
    count: number;
    selected?: boolean;
  }>;
}

export interface MenFacetTotals {
  product_types: number;
  colors: number;
  materials: number;
  moq: number;
  customization: number;
  availability: number;
}

export interface MenFilterOptions {
  categories: MenCategoryFilterOption[];
  sports: MenSportFilterOption[];
  product_types: MenFilterOption[];
  colors: MenFilterOption[];
  materials: MenFilterOption[];
  price_floor: number;
  price_ceiling: number;
  moq: MenFilterOption[];
  customization: MenFilterOption[];
  availability: MenFilterOption[];
  facet_totals: MenFacetTotals;
}

export interface MenFilterChip {
  id: string;
  key: MenFilterKey;
  value: string | number;
  label: string;
}

export interface MenPageData {
  categories: StorefrontCategory[];
  products: StorefrontProduct[];
  productCount: number;
  currentPage: number;
  lastPage: number;
  perPage: number;
  filterOptions: MenFilterOptions;
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
