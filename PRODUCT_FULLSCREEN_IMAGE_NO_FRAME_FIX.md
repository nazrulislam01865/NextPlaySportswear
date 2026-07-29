# Product Fullscreen Image No-Frame Fix

- Fullscreen product images now render on a zero-padding, full-viewport dark canvas.
- The preview image element fills the viewport and uses `object-fit: contain`, so no white or light-gray presentation frame appears around regular photos.
- Light or transparent outer whitespace embedded in uploaded product images is trimmed more aggressively before the preview opens.
- A unique Alpine viewer name prevents the compiled storefront bundle from overriding the updated standalone preview implementation.
- Product gallery switching and existing transitions remain unchanged.
