<x-layouts.admin title="Edit Training Vest Customization Option">
    <form method="POST" action="{{ route('admin.training-vest-customization-options.update', $option) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.training-vest-customization-options._form')
    </form>
</x-layouts.admin>
