import { ref } from 'vue';
import { addProductToCart, addProductToWishlist } from '../api/product-actions';
import { useStorefrontStore } from '../stores/storefront.store';
import type { StorefrontProduct } from '../types/product';

export function useProductActions() {
  const storefront = useStorefrontStore();
  const busyProductId = ref<number | null>(null);
  const message = ref<string | null>(null);

  async function addToCart(product: StorefrontProduct) {
    if (product.is_customizable) {
      window.location.assign(product.url || `/products/${product.slug}`);
      return;
    }

    busyProductId.value = product.id;
    message.value = null;

    try {
      const cart = await addProductToCart(product.slug, Math.max(1, Number(product.minimum_quantity ?? 1)));
      storefront.setCartSummary({
        quantity: Number(cart?.quantity ?? 0),
        total: Number(cart?.total ?? 0),
      });
      message.value = 'Added to cart.';
    } catch (cause) {
      message.value = cause instanceof Error ? cause.message : 'Unable to add this item.';
    } finally {
      busyProductId.value = null;
    }
  }

  async function addToWishlist(product: StorefrontProduct) {
    busyProductId.value = product.id;
    message.value = null;

    try {
      const wishlist = await addProductToWishlist(product.id);
      storefront.setWishlistCount(Number(wishlist?.count ?? wishlist?.total_items ?? storefront.wishlistCount + 1));
      message.value = 'Saved to wishlist.';
    } catch (cause) {
      message.value = cause instanceof Error ? cause.message : 'Unable to save this item.';
    } finally {
      busyProductId.value = null;
    }
  }

  return { busyProductId, message, addToCart, addToWishlist };
}
