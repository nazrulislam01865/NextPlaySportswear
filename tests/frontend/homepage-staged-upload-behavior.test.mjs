import assert from 'node:assert/strict';
import { tokenFieldName, splitIntoChunkRanges } from '../../resources/js/admin/homepage-staged-upload.js';

assert.equal(tokenFieldName('image_file'), 'image_upload_token');
assert.equal(tokenFieldName('items[3][image_file]'), 'items[3][image_upload_token]');
assert.equal(tokenFieldName('product_image'), null);

assert.deepEqual(splitIntoChunkRanges(359301, 524288), [[0, 359301]]);
assert.deepEqual(splitIntoChunkRanges(1048577, 524288), [[0, 524288], [524288, 1048576], [1048576, 1048577]]);
assert.deepEqual(splitIntoChunkRanges(0, 524288), []);

console.log('homepage staged upload behavior: PASS');
