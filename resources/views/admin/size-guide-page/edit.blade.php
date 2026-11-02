<x-layouts.admin title="Size Guide Page" :storefront-url="route('size-guide')">
    <form method="POST" action="{{ route('admin.size-guide-page.update') }}" class="np-storefront-editor space-y-6">
        @csrf
        @method('PUT')
        <x-admin.storefront-editor-toolbar
            description="Edit the new Size Guide, measurements, adult/youth/bottoms charts and SEO. The customer page design remains consistent."
            :preview-url="route('size-guide')"
            preview-label="Preview Size Guide"
            save-label="Save Size Guide"
            :can-manage="$canManage"
        />
        @if(session('status'))<div class="border border-green-200 bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">Please correct the errors below. No changes were saved.</div>@endif
        <section class="border border-slate-200 bg-white p-5 sm:p-6 space-y-4">
            <h2 class="text-xl font-bold text-brand-ink">Page header</h2>
            <label class="admin-label" for="size-title">Title</label>
            <input id="size-title" class="admin-input" name="hero[title]" maxlength="255" required value="{{ old('hero.title', data_get($content,'hero.title')) }}" @disabled(!$canManage)>
            @error('hero.title')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
            <label class="admin-label" for="size-description">Description</label>
            <textarea id="size-description" class="admin-input np-content-textarea" name="hero[description]" maxlength="1000" required @disabled(!$canManage)>{{ old('hero.description', data_get($content,'hero.description')) }}</textarea>
            @error('hero.description')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
        </section>
        @foreach(data_get($content,'sections',[]) as $index => $section)
        <section class="border border-slate-200 bg-white p-5 sm:p-6 space-y-4">
            <h2 class="text-xl font-bold text-brand-ink">Section {{ $index + 1 }}</h2>
            <label class="admin-label">Section heading</label>
            <input class="admin-input" name="sections[{{ $index }}][title]" maxlength="255" required value="{{ old('sections.'.$index.'.title', $section['title']) }}" @disabled(!$canManage)>
            <label class="admin-label">Section description</label>
            <textarea class="admin-input np-content-textarea" name="sections[{{ $index }}][description]" maxlength="1000" required @disabled(!$canManage)>{{ old('sections.'.$index.'.description', $section['description']) }}</textarea>
            <h3 class="text-sm font-bold text-brand-ink">Measurement instructions</h3>
            @foreach($section['items'] as $itemIndex => $item)
                <label class="admin-label" for="size-tip-{{ $index }}-{{ $itemIndex }}">Instruction {{ $itemIndex + 1 }}</label>
                <textarea id="size-tip-{{ $index }}-{{ $itemIndex }}" class="admin-input np-content-textarea" name="sections[{{ $index }}][items][{{ $itemIndex }}]" maxlength="500" required @disabled(!$canManage)>{{ old('sections.'.$index.'.items.'.$itemIndex, $item) }}</textarea>
            @endforeach
            @error('sections.'.$index.'.items')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
        </section>
        @endforeach
        @foreach(data_get($content,'charts',[]) as $chartIndex => $chart)
        <section class="border border-slate-200 bg-white p-5 sm:p-6 space-y-4">
            <h2 class="text-xl font-bold text-brand-ink">{{ $chart['title'] }} chart</h2>
            <label class="admin-label">Chart heading</label>
            <input class="admin-input" name="charts[{{ $chartIndex }}][title]" maxlength="255" required value="{{ old('charts.'.$chartIndex.'.title', $chart['title']) }}" @disabled(!$canManage)>
            <label class="admin-label">Chart description</label>
            <textarea class="admin-input np-content-textarea" name="charts[{{ $chartIndex }}][description]" maxlength="1000" required @disabled(!$canManage)>{{ old('charts.'.$chartIndex.'.description', $chart['description']) }}</textarea>
            <p class="text-sm text-slate-500">Columns: Size, Chest, Waist, Hips, Height / Inseam. All dimensions are editable.</p>
            @foreach($chart['rows'] as $rowIndex => $row)
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-5" aria-label="Size row {{ $rowIndex + 1 }}">
                    @foreach($row as $column => $cell)
                        <div><label class="admin-label" for="size-row-{{ $chartIndex }}-{{ $rowIndex }}-{{ $column }}">{{ ['Size','Chest','Waist','Hips','Height / Inseam'][$column] }}</label>
                        <input id="size-row-{{ $chartIndex }}-{{ $rowIndex }}-{{ $column }}" class="admin-input" name="charts[{{ $chartIndex }}][rows][{{ $rowIndex }}][{{ $column }}]" maxlength="100" required value="{{ old('charts.'.$chartIndex.'.rows.'.$rowIndex.'.'.$column, $cell) }}" @disabled(!$canManage)></div>
                    @endforeach
                </div>
            @endforeach
            @error('charts.'.$chartIndex.'.rows')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
        </section>
        @endforeach
        <section class="border border-slate-200 bg-white p-5 sm:p-6 space-y-4">
            <h2 class="text-xl font-bold text-brand-ink">Fit guidance and support</h2>
            <label class="admin-label">Heading</label>
            <input class="admin-input" name="fit[title]" maxlength="255" required value="{{ old('fit.title', data_get($content,'fit.title')) }}" @disabled(!$canManage)>
            <label class="admin-label">Description</label>
            <textarea class="admin-input np-content-textarea" name="fit[description]" maxlength="1000" required @disabled(!$canManage)>{{ old('fit.description', data_get($content,'fit.description')) }}</textarea>
            @foreach(data_get($content,'fit.tips',[]) as $index => $tip)
                <label class="admin-label">Tip {{ $index + 1 }}</label>
                <textarea class="admin-input np-content-textarea" name="fit[tips][{{ $index }}]" maxlength="500" required @disabled(!$canManage)>{{ old('fit.tips.'.$index, $tip) }}</textarea>
            @endforeach
            <label class="admin-label">Support button label</label>
            <input class="admin-input" name="fit[cta_text]" maxlength="100" required value="{{ old('fit.cta_text', data_get($content,'fit.cta_text')) }}" @disabled(!$canManage)>
        </section>
        <section class="border border-slate-200 bg-white p-5 sm:p-6 space-y-4">
            <h2 class="text-xl font-bold text-brand-ink">SEO</h2>
            <label class="admin-label">Meta title</label><input class="admin-input" name="seo[meta_title]" maxlength="255" value="{{ old('seo.meta_title',data_get($content,'seo.meta_title')) }}" @disabled(!$canManage)>
            <label class="admin-label">Meta description</label><textarea class="admin-input np-content-textarea" name="seo[meta_description]" maxlength="500" @disabled(!$canManage)>{{ old('seo.meta_description',data_get($content,'seo.meta_description')) }}</textarea>
        </section>
        @if($canManage)<div class="flex justify-end"><button type="submit" class="btn btn-primary">Save Size Guide</button></div>@endif
    </form>
</x-layouts.admin>
