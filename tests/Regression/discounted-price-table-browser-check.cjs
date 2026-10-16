const fs = require('fs');
const vm = require('vm');
const assert = require('node:assert/strict');
const template = fs.readFileSync('resources/views/components/storefront/product/builder.blade.php', 'utf8');
const script = template.split('<script>')[1].split('</script>')[0];
const runtime = {window: {productBuilder: (config) => ({
  totalQuantity: () => config.quantity,
  selections: config.selections || {},
  optionValue: (group, id) => (group.values || []).find((value) => value.id === id),
  priceTableRowIndexForQuantity: (_, quantity) => quantity >= 10 ? 1 : 0,
  parseDisplayMoney: (value) => Number(String(value).replace(/[^0-9.]/g, '')),
})}};
vm.runInNewContext(script, runtime, {timeout: 500});
const cfg = {
  base_price: 5.05,
  quantity: 10,
  price_tiers: [{min: 1, max: 9, unit: 5.05}, {min: 10, max: null, unit: 4.05}],
  price_table: {
    headers: ['Quantity', 'Product Price'],
    highlight_column: 1,
    rows: [['1-9', '$5.06'], ['10+', '$4.06']],
  },
  sale_campaigns: [{discount_type: 'percentage', discount_value: 20, maximum_discount: null}],
};
const builder = runtime.window.productBuilderFabricPricing(cfg);
assert.equal(builder.tierPrice(), 3.25, 'Discount uses $4.06 from the selected quantity table');
assert.equal(builder.offeredTableCell('$4.06', 1, 1), '$3.25', 'Visible quantity table matches the cart');
assert.equal(builder.offeredTableCell('$5.06', 1, 0), '$4.05', 'Each quantity tier gets its own discount');
const combinedConfig = {
  ...cfg,
  material_group_id: 'fabric',
  selections: {fabric: '130g-butterfly-mesh'},
  option_groups: [{id: 'fabric', label: 'Fabric', type: 'select', values: [
    {id: '130g-butterfly-mesh', label: '130G Butterfly Mesh', default: true},
    {id: '160g-butterfly-mesh', label: '160G Butterfly Mesh'},
  ]}],
  price_table: {
    highlight_column: 2,
    headers: ['Quantity', 'Fabric', 'Product Price', 'Shipping'],
    rows: [
      ['1-9', '130G Butterfly Mesh', '$5.06', '$2'],
      ['1-9', '160G Butterfly Mesh', '$5.05', '$2'],
      ['10+', '130G Butterfly Mesh', '$4.06', '$1'],
      ['10+', '160G Butterfly Mesh', '$4.05', '$1'],
    ],
  },
};
const combinedBuilder = runtime.window.productBuilderFabricPricing(combinedConfig);
assert.equal(combinedBuilder.tierPrice(), 3.25, 'Selected 130G fabric uses its own price from a combined table');
console.log('Browser discount pricing check passed.');

// The actual listed table has $4.21 as its *original* 1000+ price.
// That value must remain visible next to the $3.37 campaign offer.
const tieredConfig = {
  ...cfg,
  quantity: 1000,
  price_tiers: [
    {min: 1, max: 4, unit: 99},
    {min: 5, max: 11, unit: 99},
    {min: 12, max: 999, unit: 99},
    {min: 1000, max: null, unit: 99},
  ],
  price_table: {headers: ['Quantity', 'Product price per piece', 'Shipping'], highlight_column: 1, rows: [
    ['1-4', '5.41', '1.50'], ['5-11', '5.08', '1.50'],
    ['12-999', '4.53', '1.00'], ['1000+', '4.21', '0.80'],
  ]},
};
const tiered = runtime.window.productBuilderFabricPricing(tieredConfig);
assert.equal(tiered.startingPriceOffer().saleLabel, '$3.37');
assert.equal(tiered.startingPriceOffer().originalLabel, '$4.21');
assert.equal(tiered.startingPriceOffer().discounted, true);
assert.equal(tiered.priceTableCellOffer('4.21', 1, 3).label, '3.37');
assert.equal(tiered.priceTableCellOffer('4.21', 1, 3).originalLabel, '4.21');
assert.equal(tiered.priceTableCellOffer('4.21', 1, 3).discounted, true);
assert.equal(tiered.priceTableCellOffer('5.41', 1, 0).label, '4.33');
assert.equal(tiered.priceTableCellOffer('5.08', 1, 1).label, '4.06');
assert.equal(tiered.priceTableCellOffer('4.53', 1, 2).label, '3.62');
assert.equal(tiered.priceTableCellOffer('1.50', 2, 0).label, '1.50', 'No discount on shipping columns');

// Quantity and selected fabric must change builder price without using stale tiers.
const secondFabric = {
  headers: ['Quantity', 'Product price'], highlight_column: 1,
  rows: [['1-9', '$6.00'], ['10+', '$5.00']],
  price_tiers: [{min: 1, max: 9, unit: 100}, {min: 10, max: null, unit: 100}],
};
const variantConfig = {
  ...cfg,
  quantity: 10,
  material_group_id: 'fabric',
  selections: {fabric: 'first'},
  option_groups: [{id: 'fabric', type: 'select', values: [
    {id: 'first', label: 'First', fabric_price_table: cfg.price_table},
    {id: 'second', label: 'Second', fabric_price_table: secondFabric},
  ]}],
};
const variant = runtime.window.productBuilderFabricPricing(variantConfig);
assert.equal(variant.startingPriceOffer().originalLabel, '$4.06');
assert.equal(variant.startingPriceOffer().saleLabel, '$3.25');
assert.equal(variant.tierPrice(), 3.25);
variant.selections.fabric = 'second';
assert.equal(variant.startingPriceOffer().originalLabel, '$5.00');
assert.equal(variant.startingPriceOffer().saleLabel, '$4.00');
assert.equal(variant.tierPrice(), 4);
variantConfig.quantity = 1;
assert.equal(variant.tierPrice(), 4.8, 'Selected fabric and updated quantity must be reflected');

const nonDiscounted = runtime.window.productBuilderFabricPricing({...cfg, sale_campaigns: [], compare_at_price: null});
assert.equal(nonDiscounted.startingPriceOffer().discounted, false);
assert.equal(nonDiscounted.priceTableCellOffer('$4.06', 1, 1).discounted, false);
const nativeBetter = runtime.window.productBuilderFabricPricing({...cfg, compare_at_price: 2.99});
assert.equal(nativeBetter.startingPriceOffer().saleLabel, '$2.99', 'Native offer and campaign must not stack');
const capped = runtime.window.productBuilderFabricPricing({...cfg, sale_campaigns: [
  {discount_type: 'fixed', discount_value: 99, maximum_discount: 0.5},
]});
assert.equal(capped.startingPriceOffer().saleLabel, '$3.56', 'Cart-style fixed/maximum discount rules');
const precise = runtime.window.productBuilderFabricPricing({...cfg, price_table: {
  ...cfg.price_table, rows: [['1-9', '$5.06'], ['10+', '$4.065']],
}});
assert.equal(precise.startingPriceOffer().originalLabel, '$4.07', 'Use server-style original price rounding');
assert.equal(precise.startingPriceOffer().saleLabel, '$3.26', 'Discount must use rounded table price');
assert.equal(precise.priceTableCellOffer('$4.065', 1, 1).label, '$3.26');

const view = fs.readFileSync('resources/views/components/storefront/product/customizer/price-table.blade.php', 'utf8');
assert(view.includes('x-show="offer().discounted"'), 'Discount table markup must show crossed-out original price');
assert(template.includes('x-show="startingPriceOffer().discounted"'), 'Product starting price follows current selected fabric');
console.log('Original/discounted quantity pricing and reactive fabric checks passed.');
