import type { StorefrontCategory } from '../../../types/category';
import type { StorefrontProduct } from '../../../types/product';

export interface HomeSlide {
  id?: number | string;
  eyebrow?: string;
  title?: string;
  description?: string;
  image?: string;
  alt?: string;
  image_alt?: string;
  image_focal_position?: string;
  show_content?: boolean;
  show_eyebrow?: boolean;
  show_title?: boolean;
  show_description?: boolean;
  show_primary_button?: boolean;
  primary_label?: string;
  primary_url?: string;
  primary_target?: string;
  show_secondary_button?: boolean;
  secondary_label?: string;
  secondary_url?: string;
  secondary_target?: string;
  overlay_rgba?: string;
}

export interface HomeSectionItem {
  id?: string;
  title?: string;
  description?: string;
  url?: string;
  label?: string;
  category_id?: number;
  image?: string | null;
  image_url?: string | null;
  image_alt?: string | null;
}

export interface HomeAudienceItem extends HomeSectionItem {
  id: 'men' | 'women' | 'kids';
  title: string;
  url: string;
}

export interface HomeSportItem extends HomeSectionItem {
  id: string;
  category_id?: number;
}

export interface HomeCategoryItem extends HomeSectionItem {
  id: string;
  category_id?: number;
}

export interface HomeDesignProcessItem extends HomeSectionItem {
  id: string;
  title: string;
  description: string;
}

export interface HomeBestChoicesSettings extends Record<string, unknown> {
  tabs: {
    featured: { label: string; enabled: boolean };
    popular: { label: string; enabled: boolean };
    trending: { label: string; enabled: boolean };
  };
}

export interface HomeShopBySportSettings extends Record<string, unknown> {
  default_sport_id?: number | null;
  quick_links: Array<{ id: string; label: string; url: string }>;
}

export interface HomeSection {
  key?: string;
  component?: string;
  eyebrow?: string | null;
  title?: string | null;
  description?: string | null;
  primary_label?: string | null;
  primary_url?: string | null;
  secondary_label?: string | null;
  secondary_url?: string | null;
  image?: string | null;
  image_alt?: string | null;
  settings?: Record<string, unknown>;
  is_active?: boolean;
  items?: HomeSectionItem[];
}

export interface HomePageData {
  seo: Record<string, string>;
  slides: HomeSlide[];
  sections: HomeSection[];
  categories: StorefrontCategory[];
  featured_products: StorefrontProduct[];
  latest_products: StorefrontProduct[];
  latest_products_signature: string;
  best_selling_products: StorefrontProduct[];
  sports: StorefrontCategory[];
}
