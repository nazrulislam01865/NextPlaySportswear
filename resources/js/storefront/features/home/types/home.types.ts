import type { StorefrontCategory } from '../../../types/category';
import type { NavigationItem } from '../../../types/navigation';
import type { StorefrontProduct } from '../../../types/product';

export interface HomeSlide {
  id?: number | string;
  eyebrow?: string;
  title?: string;
  description?: string;
  image?: string;
  mobile_image?: string;
  alt?: string;
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
  mobile_image?: string | null;
  image_alt?: string | null;
  items?: Array<Record<string, unknown>>;
}

export interface HomeProcessStep {
  title: string;
  description: string;
}

export interface HomeFaq {
  question: string;
  answer: string;
}

export interface HomePageData {
  seo: Record<string, string>;
  slides: HomeSlide[];
  sections: HomeSection[];
  categories: StorefrontCategory[];
  buyer_paths: Array<Record<string, unknown>>;
  featured_products: StorefrontProduct[];
  latest_products: StorefrontProduct[];
  latest_products_signature: string;
  best_selling_products: StorefrontProduct[];
  best_selling_gear_categories: StorefrontCategory[];
  sports: StorefrontCategory[];
  process_steps: HomeProcessStep[];
  faqs: HomeFaq[];
  navigation: NavigationItem[];
  menus: Record<string, NavigationItem[]>;
}
