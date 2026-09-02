<x-layouts.admin title="Create Customization Option">
    <form method="POST" action="{{ route('admin.world-cup-customization-options.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.world-cup-customization-options._form')
    </form>
</x-layouts.admin>
