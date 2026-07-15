<x-layouts.admin
    title="Edit Training Vest Size Group"
    :eyebrow="'Master Data / '.$type->groupNumber().' '.$type->groupLabel()"
    :subtitle="$type->helpText()"
    compact-header
>
    <div class="space-y-6">
        @include('admin.training-vest-customization-options._training-vest-tabs')

        <form method="POST" action="{{ route('admin.training-vest-size-option-groups.update', $group) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.training-vest-customization-options._size-form')
        </form>
    </div>
</x-layouts.admin>
