@php
    $isEdit = $category->exists;
    $currentFilters = $category->relationLoaded('filters') ? $category->filters->keyBy('id') : collect();
    $initialBlocks = old('content_blocks', $category->relationLoaded('contentBlocks') ? $category->contentBlocks->map(fn($block) => [
        'existing_id' => $block->id, 'block_type' => $block->block_type, 'heading' => $block->heading,
        'subheading' => $block->subheading, 'content_html' => $block->content_html, 'image_url' => $block->image_url,
        'image_alt' => $block->image_alt, 'button_label' => $block->button_label, 'button_url' => $block->button_url,
        'settings_json' => $block->settings ? json_encode($block->settings, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '',
        'is_active' => $block->is_active, 'preview' => $block->publicImageUrl(),
    ])->values()->all() : []);
    $initialFaqs = old('faqs', $category->relationLoaded('faqs') ? $category->faqs->map(fn($faq) => [
        'question' => $faq->question, 'answer_html' => $faq->answer_html, 'is_active' => $faq->is_active,
    ])->values()->all() : []);
@endphp

<form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="space-y-6" x-data="categoryAdminForm(@js(['blocks' => $initialBlocks, 'faqs' => $initialFaqs, 'name' => old('name',$category->name), 'slug' => old('slug',$category->slug)]))">
    @csrf @if($isEdit) @method('PUT') @endif

    <nav class="sticky top-20 z-20 -mx-4 overflow-x-auto border-y border-slate-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="flex min-w-max gap-2 text-sm font-bold">
            @foreach([['structure','Structure'],['visibility','Visibility'],['media','Media'],['content','Content'],['filters','Filters'],['blocks','Page Blocks'],['seo','SEO']] as [$anchor,$label])<a href="#{{ $anchor }}" class="rounded-lg bg-slate-100 px-3 py-2 hover:bg-brand-dark hover:text-white">{{ $label }}</a>@endforeach
            @if($isEdit)<a href="{{ route('admin.categories.products.index',$category) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-brand-blue hover:bg-brand-blue hover:text-white">Manage Products</a>@endif
        </div>
    </nav>

    <x-admin.section-card id="structure" title="Category Structure" description="Create an Imprint-style hierarchy with multiple category levels. Circular relationships and excessive depth are blocked on the server.">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <label class="admin-label md:col-span-2">Category name<input class="admin-input" name="name" x-model="name" x-on:input="updateSlug()" required maxlength="160"></label>
            <label class="admin-label">Menu label<input class="admin-input" name="menu_label" value="{{ old('menu_label',$category->menu_label) }}" placeholder="Optional shorter label"></label>
            <label class="admin-label md:col-span-2">URL slug<input class="admin-input font-mono" name="slug" x-model="slug" x-on:change="slugTouched = true" required pattern="[a-z0-9]+(?:-[a-z0-9]+)*"></label>
            <label class="admin-label">Parent category<select class="admin-input" name="parent_id"><option value="">Top-level category</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" @selected((string)old('parent_id',$category->parent_id)===(string)$parent->id)>{{ $parent->indented_name }}</option>@endforeach</select></label>
            <label class="admin-label">Category type<select class="admin-input" name="category_type">@foreach(['standard','sport','collection','apparel','accessory','promotional','sale','new-arrival','navigation-only'] as $type)<option value="{{ $type }}" @selected(old('category_type',$category->category_type ?? 'standard')===$type)>{{ ucwords(str_replace('-',' ',$type)) }}</option>@endforeach</select></label>
            <label class="admin-label">Page template<select class="admin-input" name="page_template">@foreach(['product_grid'=>'Standard product grid','sport_landing'=>'Sport landing page','collection_landing'=>'Collection landing page','image_focused'=>'Image-focused category','quote_only'=>'Quote-only category','content_landing'=>'Content landing page','navigation_only'=>'Navigation-only category'] as $value=>$label)<option value="{{ $value }}" @selected(old('page_template',$category->page_template ?? 'product_grid')===$value)>{{ $label }}</option>@endforeach</select></label>
            <label class="admin-label">Status<select class="admin-input" name="status">@foreach(['draft','active','inactive','archived'] as $status)<option value="{{ $status }}" @selected(old('status',$category->status ?? 'draft')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
            <label class="admin-label">Sort order<input class="admin-input" type="number" min="0" name="sort_order" value="{{ old('sort_order',$category->sort_order ?? 0) }}"></label>
            <label class="admin-label">Publish date<input class="admin-input" type="datetime-local" name="published_at" value="{{ old('published_at',optional($category->published_at)->format('Y-m-d\TH:i')) }}"></label>
            <label class="admin-label">Default product sorting<select class="admin-input" name="default_product_sort">@foreach(['featured'=>'Featured','newest'=>'Newest','price-low'=>'Price: low to high','price-high'=>'Price: high to low','name-asc'=>'Name A–Z'] as $value=>$label)<option value="{{ $value }}" @selected(old('default_product_sort',$category->default_product_sort ?? 'featured')===$value)>{{ $label }}</option>@endforeach</select></label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="visibility" title="Storefront Visibility & Behavior" description="Control where the category appears and whether parent pages automatically include products from their descendants.">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach([
                ['is_visible_in_catalog','Show in catalog','Allow this category to appear on category pages.',$category->is_visible_in_catalog ?? true],
                ['is_visible_in_menu','Available to menus','Allow navigation menus to link to it.',$category->is_visible_in_menu ?? true],
                ['is_featured','Featured category','Use on homepage and featured-category sections.',$category->is_featured ?? false],
                ['show_product_count','Show product count','Display category product totals to customers.',$category->show_product_count ?? true],
                ['include_descendant_products','Include child products','Parent pages include products from all descendants.',$category->include_descendant_products ?? true],
            ] as [$name,$label,$help,$default])
                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4"><input type="hidden" name="{{ $name }}" value="0"><input class="mt-1" type="checkbox" name="{{ $name }}" value="1" @checked(old($name,$default))><span><strong class="block text-sm">{{ $label }}</strong><small class="mt-1 block text-xs leading-5 text-slate-500">{{ $help }}</small></span></label>
            @endforeach
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="media" title="Category Media" description="For every placement, the admin can upload an image or use a remote image link. Uploaded files take priority and are stored securely on the public disk.">
        @foreach([
            ['image','Fallback image',\App\Support\PublicMedia::url($category->image_path, $category->image_url),'image_alt',$category->image_alt],
            ['thumbnail','Square category thumbnail',$category->thumbnailUrl(),'thumbnail_alt',$category->thumbnail_alt],
            ['banner','Desktop hero banner',$category->bannerUrl(),'banner_alt',$category->banner_alt],
            ['mobile_banner','Mobile hero banner',$category->bannerUrl(true),'mobile_banner_alt',$category->mobile_banner_alt],
            ['og_image','Social sharing image',$category->ogImageUrl(),null,null],
        ] as [$key,$label,$preview,$altName,$altValue])
            <div class="grid gap-4 border-b border-slate-100 py-5 first:pt-0 last:border-0 last:pb-0 lg:grid-cols-[150px_minmax(0,1fr)_minmax(0,1fr)]">
                <div>@if($preview)<img src="{{ $preview }}" alt="" class="h-28 w-full rounded-xl border border-slate-200 object-cover">@else<div class="grid h-28 place-items-center rounded-xl border border-dashed border-slate-300 text-xs text-slate-400">No image</div>@endif</div>
                <label class="admin-label">{{ $label }} upload<input class="admin-input py-3" type="file" name="{{ $key }}_file" accept="image/jpeg,image/png,image/webp,image/avif"><small class="font-normal text-slate-500">JPG, PNG, WebP, or AVIF. 5–8 MB maximum.</small></label>
                <div class="grid gap-3"><label class="admin-label">Or remote image URL<input class="admin-input" type="url" name="{{ $key }}_url" value="{{ old($key.'_url',$category->{$key.'_url'} ?? ($key==='image' ? $category->image_url : null)) }}"></label>@if($altName)<label class="admin-label">Alt text<input class="admin-input" name="{{ $altName }}" value="{{ old($altName,$altValue) }}"></label>@endif<label class="flex items-center gap-2 text-xs font-bold text-red-700"><input type="checkbox" name="remove_{{ $key }}" value="1"> Remove current {{ strtolower($label) }}</label></div>
            </div>
        @endforeach
    </x-admin.section-card>

    <x-admin.section-card id="content" title="Customer Content" description="Format the category landing content freely. HTML is sanitized on the server before it is stored or displayed.">
        <div class="grid gap-5 md:grid-cols-2">
            <label class="admin-label">Eyebrow label<input class="admin-input" name="eyebrow" value="{{ old('eyebrow',$category->eyebrow) }}"></label>
            <label class="admin-label">Short page title<input class="admin-input" name="short_title" value="{{ old('short_title',$category->short_title) }}"></label>
            <label class="admin-label md:col-span-2">Short description<textarea class="admin-textarea" name="short_description" maxlength="1500">{{ old('short_description',$category->short_description) }}</textarea></label>
        </div>
        <div class="mt-5"><x-admin.rich-editor name="description_html" :value="$category->description_html ?: $category->description" label="Formatted category description" /></div>
        <div class="mt-5 grid gap-5 md:grid-cols-2"><label class="admin-label">Best for<textarea class="admin-textarea" name="best_for">{{ old('best_for',$category->best_for) }}</textarea></label><label class="admin-label">Highlights, one per line<textarea class="admin-textarea" name="highlights_text">{{ old('highlights_text',implode("\n",$category->highlights ?? [])) }}</textarea></label><label class="admin-label">CTA label<input class="admin-input" name="cta_label" value="{{ old('cta_label',$category->cta_label ?: 'View Category') }}" required></label><label class="admin-label">Optional icon<input class="admin-input" name="icon" value="{{ old('icon',$category->icon) }}" placeholder="Unicode symbol or icon key"></label></div>
    </x-admin.section-card>

    <x-admin.section-card id="filters" title="Category-Specific Faceted Filters" description="Choose only the filters relevant to this category, similar to Imprint's color, size, production, shipping, and service filters.">
        <div class="space-y-3">
            @forelse($attributes as $attribute)
                @php($pivot = $currentFilters->get($attribute->id)?->pivot)
                <div class="grid gap-3 rounded-2xl border border-slate-200 p-4 md:grid-cols-[auto_minmax(0,1fr)_minmax(0,1fr)_140px_auto] md:items-end">
                    <label class="flex items-center gap-2 pb-3 text-sm font-black"><input type="hidden" name="filter_settings[{{ $attribute->id }}][enabled]" value="0"><input type="checkbox" name="filter_settings[{{ $attribute->id }}][enabled]" value="1" @checked(old("filter_settings.{$attribute->id}.enabled",(bool)$pivot))> Enable</label>
                    <div><strong class="block">{{ $attribute->name }}</strong><small class="text-slate-500">{{ ucfirst($attribute->display_type) }} · {{ $attribute->values->count() }} values</small></div>
                    <label class="admin-label">Customer label<input class="admin-input" name="filter_settings[{{ $attribute->id }}][label]" value="{{ old("filter_settings.{$attribute->id}.label",$pivot?->label) }}" placeholder="{{ $attribute->name }}"></label>
                    <label class="admin-label">Order<input class="admin-input" type="number" min="0" name="filter_settings[{{ $attribute->id }}][sort_order]" value="{{ old("filter_settings.{$attribute->id}.sort_order",$pivot?->sort_order ?? $attribute->sort_order) }}"></label>
                    <label class="flex items-center gap-2 pb-3 text-xs font-bold"><input type="hidden" name="filter_settings[{{ $attribute->id }}][is_expanded]" value="0"><input type="checkbox" name="filter_settings[{{ $attribute->id }}][is_expanded]" value="1" @checked(old("filter_settings.{$attribute->id}.is_expanded",$pivot?->is_expanded ?? true))> Expanded</label>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center"><p class="text-slate-500">No catalog attributes exist yet.</p><a href="{{ route('admin.attributes.create') }}" class="btn btn-red mt-4">Create Attribute</a></div>
            @endforelse
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="blocks" title="Dynamic Page Blocks & FAQs" description="Build different category landing pages without creating new Blade templates. Reorder blocks by changing their row order.">
        <div class="space-y-4">
            <template x-for="(block,index) in blocks" :key="index">
                <div class="rounded-2xl border border-slate-200 p-4">
                    <input type="hidden" :name="`content_blocks[${index}][existing_id]`" x-model="block.existing_id">
                    <div class="grid gap-3 md:grid-cols-3"><label class="admin-label">Block type<select class="admin-input" :name="`content_blocks[${index}][block_type]`" x-model="block.block_type"><option value="rich_text">Rich text</option><option value="image_text">Image and text</option><option value="promo_banner">Promotional banner</option><option value="featured_products">Featured products</option><option value="selected_products">Selected products</option><option value="child_categories">Child categories</option><option value="highlights">Highlights</option><option value="video">Video</option><option value="cta">CTA</option><option value="logo_list">Logo list</option><option value="trust_badges">Trust badges</option><option value="related_categories">Related categories</option></select></label><label class="admin-label">Heading<input class="admin-input" :name="`content_blocks[${index}][heading]`" x-model="block.heading"></label><label class="admin-label">Subheading<input class="admin-input" :name="`content_blocks[${index}][subheading]`" x-model="block.subheading"></label></div>
                    <label class="admin-label mt-3">HTML content<textarea class="admin-textarea min-h-36 font-mono text-xs" :name="`content_blocks[${index}][content_html]`" x-model="block.content_html" placeholder="Safe formatted HTML or plain text"></textarea></label>
                    <div class="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-4"><label class="admin-label">Image upload<input class="admin-input py-3" type="file" :name="`content_blocks[${index}][image_file]`" accept="image/jpeg,image/png,image/webp,image/avif"></label><label class="admin-label">Image URL<input class="admin-input" type="url" :name="`content_blocks[${index}][image_url]`" x-model="block.image_url"></label><label class="admin-label">Image alt<input class="admin-input" :name="`content_blocks[${index}][image_alt]`" x-model="block.image_alt"></label><label class="admin-label">Button label<input class="admin-input" :name="`content_blocks[${index}][button_label]`" x-model="block.button_label"></label><label class="admin-label lg:col-span-2">Button URL<input class="admin-input" :name="`content_blocks[${index}][button_url]`" x-model="block.button_url"></label><label class="admin-label lg:col-span-2">Settings JSON<input class="admin-input font-mono" :name="`content_blocks[${index}][settings_json]`" x-model="block.settings_json"></label></div>
                    <div class="mt-3 flex items-center justify-between"><label class="flex items-center gap-2 text-sm font-bold"><input type="hidden" :name="`content_blocks[${index}][is_active]`" value="0"><input type="checkbox" :name="`content_blocks[${index}][is_active]`" value="1" x-model="block.is_active"> Active block</label><button type="button" class="btn btn-white text-red-700" x-on:click="blocks.splice(index,1)">Remove</button></div>
                </div>
            </template>
            <button type="button" class="btn btn-white" x-on:click="addBlock()">+ Add Page Block</button>
        </div>

        <div class="mt-8 border-t border-slate-200 pt-6">
            <div class="mb-4 flex items-center justify-between"><div><h3 class="font-black">Category FAQs</h3><p class="text-sm text-slate-500">FAQs also generate structured data when the category is indexable.</p></div><button type="button" class="btn btn-white" x-on:click="addFaq()">+ Add FAQ</button></div>
            <div class="space-y-3"><template x-for="(faq,index) in faqs" :key="index"><div class="rounded-2xl border border-slate-200 p-4"><label class="admin-label">Question<input class="admin-input" :name="`faqs[${index}][question]`" x-model="faq.question"></label><label class="admin-label mt-3">Answer HTML<textarea class="admin-textarea" :name="`faqs[${index}][answer_html]`" x-model="faq.answer_html"></textarea></label><div class="mt-3 flex justify-between"><label class="flex items-center gap-2 text-sm font-bold"><input type="hidden" :name="`faqs[${index}][is_active]`" value="0"><input type="checkbox" :name="`faqs[${index}][is_active]`" value="1" x-model="faq.is_active"> Active</label><button type="button" class="btn btn-white text-red-700" x-on:click="faqs.splice(index,1)">Remove</button></div></div></template></div>
        </div>
    </x-admin.section-card>

    <x-admin.section-card id="seo" title="SEO, Social Sharing & Structured Data" description="Control page titles, metadata, canonical behavior, crawler directives, Open Graph content, and optional schema overrides.">
        <div class="grid gap-5 md:grid-cols-2"><label class="admin-label">SEO title<input class="admin-input" name="meta_title" value="{{ old('meta_title',$category->meta_title) }}" maxlength="255"></label><label class="admin-label">Canonical URL<input class="admin-input" type="url" name="canonical_url" value="{{ old('canonical_url',$category->canonical_url) }}"></label><label class="admin-label md:col-span-2">Meta description<textarea class="admin-textarea" name="meta_description">{{ old('meta_description',$category->meta_description) }}</textarea></label><label class="admin-label md:col-span-2">Meta keywords<input class="admin-input" name="meta_keywords" value="{{ old('meta_keywords',$category->meta_keywords) }}"></label><label class="admin-label">Open Graph title<input class="admin-input" name="og_title" value="{{ old('og_title',$category->og_title) }}"></label><label class="admin-label">Open Graph description<textarea class="admin-textarea" name="og_description">{{ old('og_description',$category->og_description) }}</textarea></label></div>
        <div class="mt-5 grid gap-3 md:grid-cols-2"><label class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4"><input type="hidden" name="robots_index" value="0"><input type="checkbox" name="robots_index" value="1" @checked(old('robots_index',$category->robots_index ?? true))><span><strong class="block">Allow indexing</strong><small class="text-slate-500">Disable for temporary or navigation-only categories.</small></span></label><label class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4"><input type="hidden" name="robots_follow" value="0"><input type="checkbox" name="robots_follow" value="1" @checked(old('robots_follow',$category->robots_follow ?? true))><span><strong class="block">Allow link following</strong><small class="text-slate-500">Usually enabled even when filtered pages are noindex.</small></span></label></div>
        <label class="admin-label mt-5">Custom schema JSON<textarea class="admin-textarea min-h-56 font-mono text-xs" name="schema_json_text">{{ old('schema_json_text',$category->schema_json ? json_encode($category->schema_json,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '') }}</textarea></label>
    </x-admin.section-card>

    <div class="sticky bottom-3 z-30 flex flex-col gap-3 sm:bottom-4 sm:flex-row sm:flex-wrap sm:justify-end rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft backdrop-blur"><a href="{{ route('admin.categories.index') }}" class="btn btn-white">Cancel</a>@if($isEdit && $category->status==='active')<a href="{{ route('categories.show',$category->slug) }}" target="_blank" rel="noopener" class="btn btn-white">Preview Storefront</a>@endif<button class="btn btn-red">{{ $isEdit ? 'Update Category' : 'Create Category' }}</button></div>
</form>

@once
<script>
function categoryAdminForm(initial) {
    return {
        blocks: initial.blocks || [], faqs: initial.faqs || [], name: initial.name || '', slug: initial.slug || '', slugTouched: Boolean(initial.slug),
        updateSlug() { if (this.slugTouched) return; this.slug = this.name.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,''); },
        addBlock() { this.blocks.push({existing_id:'',block_type:'rich_text',heading:'',subheading:'',content_html:'',image_url:'',image_alt:'',button_label:'',button_url:'',settings_json:'',is_active:true}); },
        addFaq() { this.faqs.push({question:'',answer_html:'',is_active:true}); },
    };
}
</script>
@endonce
