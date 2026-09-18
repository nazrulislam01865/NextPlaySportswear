import { apiClient } from './client';
import { API_ENDPOINTS } from './endpoints';
import type { ApiEnvelope } from './types';

export async function addProductToCart(productSlug: string, quantity: number) {
  const idempotencyKey = globalThis.crypto?.randomUUID?.() ?? `${Date.now()}-${Math.random()}`;
  const response = await apiClient.post<ApiEnvelope<any>>(
    API_ENDPOINTS.cartItems,
    { product_slug: productSlug, quantity },
    { headers: { 'Idempotency-Key': idempotencyKey } },
  );
  return response.data.data;
}

export async function addProductToWishlist(productId: number) {
  const response = await apiClient.post<ApiEnvelope<any>>(API_ENDPOINTS.wishlistItems, { product_id: productId });
  return response.data.data;
}
