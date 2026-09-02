<x-layouts.admin title="Edit Customization Option">
    <form method="POST" action="{{ route('admin.world-cup-customization-options.update', $option) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.world-cup-customization-options._form')
    </form>
</x-layouts.admin>
