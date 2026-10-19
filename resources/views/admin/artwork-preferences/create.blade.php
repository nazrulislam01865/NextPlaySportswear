<x-layouts.admin :title="'Add '.($type === 'pantone' ? 'Pantone Color' : 'Design Style')" :subtitle="'Create a reusable '.($type === 'pantone' ? 'color' : 'style').' that can be assigned to products.'">
    @include('admin.artwork-preferences._form')
</x-layouts.admin>
