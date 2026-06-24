<x-layouts.admin title="Create Size Option Group">
    <form method="POST" action="{{ route('admin.size-option-groups.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.size-option-groups._form')
    </form>
</x-layouts.admin>
