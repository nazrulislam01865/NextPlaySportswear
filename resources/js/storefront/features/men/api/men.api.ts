import { apiClient } from '../../../api/client';
import { API_ENDPOINTS } from '../../../api/endpoints';
import type { ApiEnvelope } from '../../../api/types';
import type { StorefrontCategory } from '../../../types/category';
import type { StorefrontProduct } from '../../../types/product';
import type {
  CatalogCategoryIndexData,
  CatalogCategoryApiItem,
  CatalogProductApiItem,
  MenCatalogFilters,
  MenCategoryFilterOption,
  MenFilterOption,
  MenFilterOptions,
  MenPageData,
  MenSportFilterOption,
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

const emptyFilterOptions = (): MenFilterOptions => ({
  categories: [],
  sports: [],
  product_types: [],
  colors: [],
  materials: [],
  price_floor: 0,
  price_ceiling: 100,
  moq: [],
  customization: [],
  availability: [],
  facet_totals: {
    product_types: 0,
    colors: 0,
    materials: 0,
    moq: 0,
    customization: 0,
    availability: 0,
  },
});

function normalizeSimpleOptions(value: unknown): MenFilterOption[] {
  if (!Array.isArray(value)) return [];
  return value.map((item) => {
    const source = (item ?? {}) as Record<string, unknown>;
    return {
      value: String(source.value ?? ''),
      label: String(source.label ?? source.value ?? ''),
      count: Number(source.count ?? 0),
      color_hex: source.color_hex ? String(source.color_hex) : null,
    };
  }).filter((item) => item.value && item.label);
}

function normalizeSports(value: unknown): MenSportFilterOption[] {
  if (!Array.isArray(value)) return [];
  return value.map((item) => {
    const source = (item ?? {}) as Record<string, unknown>;
    return {
      id: Number(source.id ?? 0),
      label: String(source.label ?? ''),
      slug: source.slug ? String(source.slug) : undefined,
      count: Number(source.count ?? 0),
      selected: Boolean(source.selected),
    };
  }).filter((item) => item.id > 0 && item.label);
}

function normalizeCategories(value: unknown): MenCategoryFilterOption[] {
  if (!Array.isArray(value)) return [];
  return value.map((item) => {
    const source = (item ?? {}) as Record<string, unknown>;
    const children = Array.isArray(source.children) ? source.children : [];
    return {
      id: Number(source.id ?? 0),
      label: String(source.label ?? ''),
      slug: source.slug ? String(source.slug) : undefined,
      count: Number(source.count ?? 0),
      selected: Boolean(source.selected),
      has_selected_child: Boolean(source.has_selected_child),
      children: children.map((child) => {
        const childSource = (child ?? {}) as Record<string, unknown>;
        return {
          id: Number(childSource.id ?? 0),
          label: String(childSource.label ?? ''),
          slug: childSource.slug ? String(childSource.slug) : undefined,
          count: Number(childSource.count ?? 0),
          selected: Boolean(childSource.selected),
        };
      }).filter((child) => child.id > 0 && child.label),
    };
  }).filter((item) => item.id > 0 && item.label);
}

function normalizeFacetTotals(value: unknown): MenFilterOptions['facet_totals'] {
  const source = (value ?? {}) as Record<string, unknown>;
  return {
    product_types: Number(source.product_types ?? 0),
    colors: Number(source.colors ?? 0),
    materials: Number(source.materials ?? 0),
    moq: Number(source.moq ?? 0),
    customization: Number(source.customization ?? 0),
    availability: Number(source.availability ?? 0),
  };
}

function normalizeFilterOptions(value: unknown): MenFilterOptions {
  const defaults = emptyFilterOptions();
  const source = (value ?? {}) as Record<string, unknown>;

  return {
    categories: normalizeCategories(source.categories),
    sports: normalizeSports(source.sports),
    product_types: normalizeSimpleOptions(source.product_types),
    colors: normalizeSimpleOptions(source.colors),
    materials: normalizeSimpleOptions(source.materials),
    price_floor: Number(source.price_floor ?? defaults.price_floor),
    price_ceiling: Number(source.price_ceiling ?? defaults.price_ceiling),
    moq: normalizeSimpleOptions(source.moq),
    customization: normalizeSimpleOptions(source.customization),
    availability: normalizeSimpleOptions(source.availability),
    facet_totals: normalizeFacetTotals(source.facet_totals),
  };
}

function filterParams(filters: MenCatalogFilters): Record<string, unknown> {
  return {
    categories: filters.categories.length ? filters.categories : undefined,
    sports: filters.sports.length ? filters.sports : undefined,
    product_types: filters.product_types.length ? filters.product_types : undefined,
    colors: filters.colors.length ? filters.colors : undefined,
    materials: filters.materials.length ? filters.materials : undefined,
    min_price: filters.min_price ?? undefined,
    max_price: filters.max_price ?? undefined,
    moq: filters.moq.length ? filters.moq : undefined,
    customization: filters.customization.length ? filters.customization : undefined,
    availability: filters.availability.length ? filters.availability : undefined,
  };
}

export async function fetchMenPage(page = 1, filters: MenCatalogFilters): Promise<MenPageData> {
  page = Math.max(1, Math.floor(page));
  const [categoriesResponse, productsResponse] = await Promise.all([
    apiClient.get<ApiEnvelope<CatalogCategoryIndexData>>(API_ENDPOINTS.categories),
    apiClient.get<ApiEnvelope<CatalogProductApiItem[]>>(API_ENDPOINTS.products, {
      params: { q: 'men', page, per_page: 24, ...filterParams(filters) },
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
    filterOptions: normalizeFilterOptions(meta.filter_options),
  };
}
