import assert from 'node:assert/strict';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '../..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');

const routes = read('routes/web.php');
const slideForm = read('resources/views/admin/homepage-slides/_form.blade.php');
const sectionForm = read('resources/views/admin/homepage-sections/edit.blade.php');
const slideRequest = read('app/Http/Requests/Admin/HomepageSlideRequest.php');
const sectionRequest = read('app/Http/Requests/Admin/HomepageSectionRequest.php');
const slideMedia = read('app/Services/Catalog/HomepageSlideMediaService.php');
const sectionMedia = read('app/Services/Catalog/HomepageSectionMediaService.php');
const adminJs = read('resources/js/admin.js');

assert.ok(routes.includes('HomepageStagedUploadController'), 'Homepage staged upload routes must exist');
assert.ok(routes.includes('homepage-media-uploads'), 'Homepage staged upload URL must exist');
assert.ok(slideForm.includes('data-homepage-upload-form'), 'Hero slide form must opt into staged uploading');
assert.ok(sectionForm.includes('data-homepage-upload-form'), 'Homepage section form must opt into staged uploading');
assert.ok(slideRequest.includes("'image_upload_token'"), 'Hero slide request must accept staged upload token');
assert.ok(sectionRequest.includes("'image_upload_token'"), 'Section request must accept section staged upload token');
assert.ok(sectionRequest.includes("'items.*.image_upload_token'"), 'Section request must accept item staged upload tokens');
assert.ok(slideMedia.includes('HomepageStagedUploadService'), 'Hero slide media service must consume staged uploads');
assert.ok(sectionMedia.includes('HomepageStagedUploadService'), 'Section media service must consume staged uploads');
assert.ok(adminJs.includes("./admin/homepage-staged-upload"), 'Admin JS must initialize staged homepage uploader');
assert.ok(fs.existsSync(path.join(root, 'app/Http/Controllers/Admin/HomepageStagedUploadController.php')), 'Staged upload controller must exist');
assert.ok(fs.existsSync(path.join(root, 'app/Services/Catalog/HomepageStagedUploadService.php')), 'Staged upload service must exist');
assert.ok(fs.existsSync(path.join(root, 'resources/js/admin/homepage-staged-upload.js')), 'Staged uploader JS module must exist');

console.log('homepage staged upload contract: PASS');
