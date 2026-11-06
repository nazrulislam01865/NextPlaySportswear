@php $canManage = auth('admin')->user()?->canAdmin('orders.manage') ?? false; @endphp
<x-layouts.admin title="Quotation Payment Terms" eyebrow="Master Data / Quotation Payment Terms" subtitle="Manage reusable payment terms used in Bulk Quote quotation publishing.">
    <x-admin.section-card title="Payment Terms" description="Only active terms appear in the quotation dropdown. Published quotes retain their original text even after a term changes.">
        @if($errors->any())
            <div class="nps-alert" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if($canManage)
            <form method="POST" action="{{ route('admin.bulk-quote-payment-terms.store') }}" class="mb-6 grid gap-4 md:grid-cols-[minmax(0,1fr)_125px_110px_auto] md:items-end">
                @csrf
                <label class="admin-label">New payment term
                    <input class="admin-input" name="label" type="text" maxlength="200" required placeholder="e.g. 50% deposit / 50% before dispatch" value="{{ old('label') }}">
                </label>
                <label class="admin-label">Sort order<input class="admin-input" name="sort_order" type="number" min="0" max="999999" value="{{ old('sort_order', ($terms->max('sort_order') ?? 0) + 10) }}" required></label>
                <label class="admin-label">Status<select class="admin-input" name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></label>
                <button class="btn btn-red" type="submit">Add term</button>
            </form>
        @endif
        <div class="admin-table-scroll" tabindex="0" aria-label="Quotation payment terms">
            <table class="admin-table w-full min-w-[660px] text-sm"><thead><tr><th>Payment term</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                @forelse($terms as $term)
                    <tr>
                        @if($canManage)
                            <td colspan="4" class="p-3">
                                <form id="np-term-form-{{ $term->id }}" method="POST" action="{{ route('admin.bulk-quote-payment-terms.update', $term) }}" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_85px_110px_auto_auto] md:items-center">
                                    @csrf @method('PATCH')
                                    <input class="admin-input" name="label" type="text" value="{{ $term->label }}" maxlength="200" required aria-label="Payment term label">
                                    <input class="admin-input" name="sort_order" type="number" value="{{ $term->sort_order }}" min="0" max="999999" required aria-label="Sort order">
                                    <select class="admin-input" name="is_active" aria-label="Status"><option value="1" @selected($term->is_active)>Active</option><option value="0" @selected(!$term->is_active)>Inactive</option></select>
                                    <button class="btn btn-white" type="submit">Save</button>
                                    <button class="btn btn-white" type="submit" form="np-term-remove-{{ $term->id }}" onclick="return confirm('Delete this payment term?')">Delete</button>
                                </form>
                                <form id="np-term-remove-{{ $term->id }}" method="POST" action="{{ route('admin.bulk-quote-payment-terms.destroy', $term) }}">@csrf @method('DELETE')</form>
                            </td>
                        @else
                            <td>{{ $term->label }}</td><td>{{ $term->sort_order }}</td><td>{{ $term->is_active ? 'Active' : 'Inactive' }}</td><td>View only</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-5">No payment terms have been configured.</td></tr>
                @endforelse
            </tbody></table>
        </div>
    </x-admin.section-card>
</x-layouts.admin>
