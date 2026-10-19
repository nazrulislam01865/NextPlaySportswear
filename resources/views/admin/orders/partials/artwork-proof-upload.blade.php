<details class="mt-3 rounded-xl border border-slate-200 bg-white" @if($currentProof === null) open @endif>
    <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-brand-ink">{{ $currentProof ? 'Publish a New Proof Version' : 'Upload First Proof' }} <span class="ml-2 font-normal text-slate-500">PNG, JPG, WebP or PDF · 20 MB max</span></summary>
    <form method="POST" action="{{ route('admin.orders.artwork-proofs.store', $order) }}" enctype="multipart/form-data" class="border-t border-slate-200 p-4">
        @csrf
        <input type="hidden" name="order_item_id" value="{{ $item->id }}">
        <div class="grid gap-3 md:grid-cols-2">
            <label><span class="admin-label">Design name <span class="font-normal text-slate-500">(optional)</span></span><input class="admin-input" name="design_name" maxlength="190" value="{{ old('order_item_id') == $item->id ? old('design_name') : '' }}" placeholder="{{ $item->product_name }}"></label>
            <label><span class="admin-label">Proof image / PDF *</span><input class="admin-input" name="proof_file" type="file" accept="image/png,image/jpeg,image/webp,application/pdf" required><small class="mt-1 block text-xs text-slate-500">Stored privately. Customer will be notified when published.</small></label>
            <label class="md:col-span-2"><span class="admin-label">Designer notes <span class="font-normal text-slate-500">(visible to customer)</span></span><textarea class="admin-textarea" name="designer_notes" rows="2" maxlength="3000" placeholder="Explain what changed in this proof..." style="min-height: 80px">{{ old('order_item_id') == $item->id ? old('designer_notes') : '' }}</textarea></label>
        </div>
        @if(old('order_item_id') == $item->id && $errors->any())
            <div class="mt-3 text-sm text-rose-700" role="alert">{{ $errors->first() }}</div>
        @endif
        <div class="mt-3 flex justify-end"><button type="submit" class="btn btn-red">{{ $currentProof ? 'Publish Updated Proof (v'.($currentProof->version + 1).')' : 'Publish First Proof' }}</button></div>
    </form>
</details>
