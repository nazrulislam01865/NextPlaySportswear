<x-layouts.admin title="Create Jersey Customization Option">
    <form method="POST" action="{{ route('admin.jersey-customization-options.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.jersey-customization-options._form')
    </form>
</x-layouts.admin>
