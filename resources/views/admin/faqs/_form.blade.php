@php($isEdit = $faq->exists)
<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($formMethod !== 'POST') @method($formMethod) @endif

    <x-admin.section-card title="FAQ Details" description="This content can be reused across any number of products. Updating it here updates every assigned product automatically.">
        <div class="grid gap-5">
            <label class="admin-label">Question <span class="text-brand-red">*</span>
                <input class="admin-input" name="question" value="{{ old('question', $faq->question) }}" maxlength="500" required placeholder="e.g., What is the minimum order quantity?">
                @error('question')<span class="mt-1 text-xs font-bold text-red-600">{{ $message }}</span>@enderror
            </label>

            <label class="admin-label">Answer <span class="text-brand-red">*</span>
                <textarea class="admin-textarea min-h-40" name="answer" maxlength="5000" required placeholder="Write the customer-facing answer.">{{ old('answer', $faq->answer) }}</textarea>
                @error('answer')<span class="mt-1 text-xs font-bold text-red-600">{{ $message }}</span>@enderror
            </label>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="admin-label">Display order
                    <input class="admin-input" type="number" name="sort_order" min="0" max="999999" value="{{ old('sort_order', $faq->sort_order ?? 0) }}">
                </label>
                <label class="flex min-h-14 items-center gap-3 self-end rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-black text-brand-ink">
                    <input type="hidden" name="is_active" value="0">
                    <input class="h-5 w-5 rounded border-slate-300 text-brand-blue" type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $faq->is_active ?? true))>
                    Active and available on the storefront
                </label>
            </div>
        </div>
    </x-admin.section-card>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.faqs.index') }}" class="btn btn-white">Cancel</a>
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update FAQ' : 'Create FAQ' }}</button>
    </div>
</form>
