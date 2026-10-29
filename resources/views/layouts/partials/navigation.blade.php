@if(config('academy.enabled') && auth()->check() && in_array((int) auth()->user()->usergroup_id, [3, 5, 6, 7], true))
    @include('academy.header')
@else
    @include('academy.legacy.admin-header')
@endif
