<x-layouts.admin title="Edit Navigation Menu"><form method="POST" action="{{ route('admin.menus.update',$menu) }}">@csrf @method('PUT') @include('admin.menus._form')</form></x-layouts.admin>
