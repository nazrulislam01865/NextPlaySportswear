import { defineStore } from 'pinia';
import { apiClient } from '../api/client';
import { API_ENDPOINTS } from '../api/endpoints';
import type { ApiEnvelope } from '../api/types';
import type { HeaderNavigationItemSetting } from '../components/layout/navigation/mega-menu.config';
import type { StorefrontCustomer } from '../types/customer';

export interface StorefrontLinkSetting {
  enabled?: boolean;
  label?: string | null;
  url?: string | null;
  icon?: string | null;
}

export interface HeaderAnnouncementSetting {
  enabled?: boolean;
  text?: string | null;
  url?: string | null;
  dismissible?: boolean;
}

export interface NavigationSettings {
  items?: HeaderNavigationItemSetting[];
}

export interface HeaderSettings {
  announcements?: HeaderAnnouncementSetting[];
  utility_links?: StorefrontLinkSetting[];
  actions?: {
    search?: StorefrontLinkSetting;
    account_enabled?: boolean;
    wishlist_enabled?: boolean;
    cart_enabled?: boolean;
    quote?: StorefrontLinkSetting;
  };
}

export interface FooterSettings {
  contact?: {
    address?: string | null;
    email?: string | null;
    phone?: string | null;
  };
  columns?: Array<{
    enabled?: boolean;
    title?: string | null;
    items?: StorefrontLinkSetting[];
  }>;
  club?: {
    enabled?: boolean;
    title?: string | null;
    button_label?: string | null;
    button_url?: string | null;
  };
  social?: {
    enabled?: boolean;
    label?: string | null;
    links?: StorefrontLinkSetting[];
  };
  legal?: {
    copyright?: string | null;
    links?: StorefrontLinkSetting[];
  };
  payments?: {
    enabled?: boolean;
    label?: string | null;
  };
}

interface BootstrapPayload {
  site: { name?: string; tagline?: string; logo?: string; logo_alt?: string };
  customer: StorefrontCustomer | null;
  cart: { quantity?: number; total_items?: number; total?: number };
  wishlist: { total_items?: number };
  navigation: NavigationSettings;
  header: HeaderSettings;
  footer: FooterSettings;
}

export const useStorefrontStore = defineStore('storefront', {
  state: () => ({
    initialized: false,
    loading: false,
    site: {} as BootstrapPayload['site'],
    customer: null as StorefrontCustomer | null,
    cartQuantity: 0,
    cartTotal: 0,
    wishlistCount: 0,
    navigation: {} as NavigationSettings,
    header: {} as HeaderSettings,
    footer: {} as FooterSettings,
  }),
  actions: {
    async bootstrap(force = false) {
      if ((this.initialized || this.loading) && !force) return;
      this.loading = true;
      try {
        const response = await apiClient.get<ApiEnvelope<BootstrapPayload>>(API_ENDPOINTS.bootstrap);
        const payload = response.data.data;
        this.site = payload.site ?? {};
        this.customer = payload.customer ?? null;
        this.cartQuantity = Number(payload.cart?.quantity ?? payload.cart?.total_items ?? 0);
        this.cartTotal = Number(payload.cart?.total ?? 0);
        this.wishlistCount = Number(payload.wishlist?.total_items ?? 0);
        this.navigation = payload.navigation ?? {};
        this.header = payload.header ?? {};
        this.footer = payload.footer ?? {};
        this.initialized = true;
      } finally {
        this.loading = false;
      }
    },
    setCartQuantity(quantity: number) {
      this.cartQuantity = Math.max(0, Number(quantity || 0));
    },
    setCartSummary(cart: { quantity?: number; total?: number }) {
      this.cartQuantity = Math.max(0, Number(cart?.quantity ?? 0));
      this.cartTotal = Math.max(0, Number(cart?.total ?? 0));
    },
    setWishlistCount(count: number) {
      this.wishlistCount = Math.max(0, Number(count || 0));
    },
  },
});
