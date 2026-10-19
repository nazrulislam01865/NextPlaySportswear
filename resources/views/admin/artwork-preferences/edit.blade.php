<x-layouts.admin :title="'Edit '.($type === 'pantone' ? 'Pantone Color' : 'Design Style')" :subtitle="'Update the saved '.($type === 'pantone' ? 'color and screen preview' : 'design style').' for product configuration.'">
    @include('admin.artwork-preferences._form')
</x-layouts.admin>
