<x-layouts.admin title="Edit Jersey Customization Option">
    <form method="POST" action="{{ route('admin.jersey-customization-options.update', $option) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.jersey-customization-options._form')
    </form>
</x-layouts.admin>
