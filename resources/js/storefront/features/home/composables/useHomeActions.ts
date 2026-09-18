import { useProductActions } from '../../../composables/useProductActions';

/**
 * Homepage compatibility wrapper. The shared product action composable owns
 * setCartSummary / wishlist synchronization so collection pages stay aligned.
 */
export function useHomeActions() {
  return useProductActions();
}
