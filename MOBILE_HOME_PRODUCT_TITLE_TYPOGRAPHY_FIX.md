# Mobile Homepage Product Title Typography Fix

## Problem
On phone layouts, homepage product titles were still rendering too bold because a later global heading rule used `font-weight: 650 !important`, overriding the mobile list's lighter title weight.

## Update
- Added an explicit mobile title weight of `500 !important` so the global heading rule cannot override it.
- Reduced the mobile title size to a more balanced `16px–19px` responsive range.
- Improved line-height, letter spacing, text color, and wrapping for long product names.
- Added a smaller 15px treatment for screens up to 380px wide.

Desktop product cards and product-page typography are unchanged.
