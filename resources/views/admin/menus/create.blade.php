<x-layouts.admin title="Create Navigation Menu"><form method="POST" action="{{ route('admin.menus.store') }}">@csrf @include('admin.menus._form')</form></x-layouts.admin>
