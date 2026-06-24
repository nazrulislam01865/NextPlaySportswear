<x-layouts.admin title="Edit Size Option Group">
    <form method="POST" action="{{ route('admin.size-option-groups.update', $group) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.size-option-groups._form')
    </form>
</x-layouts.admin>
