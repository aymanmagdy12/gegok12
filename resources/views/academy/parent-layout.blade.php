@extends('layouts.app')
@section('base-navigation')
<nav class="navbar bg-white w-full flex px-4 lg:px-8 py-2 justify-between items-center">
    <div class="flex items-center"><button type="button" class="block lg:hidden md:hidden mr-3" onclick="showsidebar('res_sidebar')" aria-label="Open navigation">☰</button><a class="text-xl font-semibold" href="{{ url('/learning/academy/dashboard') }}">Accel Hub Academy</a></div>
    <div class="flex items-center"><span class="mx-3">{{ auth()->user()->email }}</span><form method="POST" action="{{ url('/logout') }}">@csrf<button class="px-3 py-2">Logout</button></form></div>
</nav>
@endsection
@section('base-sidebar')
<div class="w-full lg:w-48 md:w-48 h-full bg-purple-800 text-white teacher-sidebar"><div class="min-h-full header-wrapper-b hidden lg:block md:block">@include('academy.menu')</div></div>
<div id="res_sidebar" class="w-full lg:w-48 md:w-48 hidden lg:hidden md:hidden res_sidebar teacher-sidebar"><div class="min-h-full header-wrapper-b lg:hidden md:hidden bg-purple-700 text-white">@include('academy.menu')</div></div>
@endsection
@section('base-content')
    @yield('content')
@endsection
