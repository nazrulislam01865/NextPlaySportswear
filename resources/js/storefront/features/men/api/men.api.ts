import { apiClient } from '../../../api/client';
import { API_ENDPOINTS } from '../../../api/endpoints';
import type { ApiEnvelope } from '../../../api/types';
import type { StorefrontCategory } from '../../../types/category';
import type { StorefrontProduct } from '../../../types/product';
import type {
  CatalogCategoryIndexData,
  CatalogCategoryApiItem,
  CatalogProductApiItem,
  MenPageData,
} from '../types/men.types';

function toStorefrontCategory(category: CatalogCategoryApiItem): StorefrontCategory {
  return {
    id: category.id,
    slug: category.slug,
    title: category.title,
    short_title: category.short_title,
    description: category.description ?? undefined,
    image: category.media?.image ?? undefined,
    banner: category.media?.banner ?? category.media?.image ?? undefined,
    alt: category.media?.banner_alt ?? category.media?.alt ?? category.title,
    url: category.cta?.url ?? undefined,
    link_label: category.cta?.label ?? undefined,
  };
}

function toStorefrontProduct(product: CatalogProductApiItem): StorefrontProduct | null {
  const id = Number(product.id ?? 0);
  const slug = String(product.slug ?? '').trim();
  if (!id || !slug) return null;

  return {
    id,
    slug,
    title: product.title,
    short_title: product.short_title,
    summary: product.summary,
    sku: product.sku,
    category: product.category?.name ?? undefined,
    subcategory: product.category?.subcategory_name ?? undefined,
    price: product.pricing?.display,
    display_unit_price: product.pricing?.unit_amount ?? null,
    discount_price: product.pricing?.discount_amount ?? null,
    original_price: product.pricing?.original_amount ?? null,
    original_price_label: product.pricing?.original_label ?? null,
    display_compare_at_price: product.pricing?.compare_at_amount ?? null,
    compare_at_price_label: product.pricing?.compare_at_label ?? null,
    discount_percentage: product.pricing?.discount_percentage ?? null,
    currency: product.pricing?.currency,
    image: product.media?.image ?? undefined,
    gallery: product.media?.gallery ?? [],
    alt: product.media?.alt ?? product.title,
    url: product.url ?? undefined,
    tag: product.badge?.label ?? null,
    tag_color: product.badge?.color ?? null,
    is_customizable: Boolean(product.flags?.customizable),
    minimum_quantity: product.quantity?.minimum ?? 1,
  };
}

export async function fetchMenPage(page = 1): Promise<MenPageData> {
  page = Math.max(1, Math.floor(page));
  const [categoriesResponse, productsResponse] = await Promise.all([
    apiClient.get<ApiEnvelope<CatalogCategoryIndexData>>(API_ENDPOINTS.categories),
    apiClient.get<ApiEnvelope<CatalogProductApiItem[]>>(API_ENDPOINTS.products, {
      params: { q: 'men', page, per_page: 24 },
    }),
  ]);

  const products = (productsResponse.data.data ?? [])
    .map(toStorefrontProduct)
    .filter((product): product is StorefrontProduct => product !== null);
  const meta = productsResponse.data.meta ?? {};

  return {
    categories: (categoriesResponse.data.data.categories ?? []).map(toStorefrontCategory),
    products,
    productCount: Number(meta.total ?? products.length),
    currentPage: Number(meta.current_page ?? page),
    lastPage: Math.max(1, Number(meta.last_page ?? 1)),
    perPage: Math.max(1, Number(meta.per_page ?? (products.length || 24))),
  };
}
