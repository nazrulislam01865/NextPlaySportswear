<x-layouts.admin title="Create Training Vest Customization Option">
    <form method="POST" action="{{ route('admin.training-vest-customization-options.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.training-vest-customization-options._form')
    </form>
</x-layouts.admin>
