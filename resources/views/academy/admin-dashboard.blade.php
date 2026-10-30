@extends($layout)
@section('content')
<h1 class="admin-h1 my-4">Admin dashboard</h1>
@include('academy.messages')
<div class="my-3" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px">
@foreach([['Students', $counts->get(6,0), '/learning/academy'], ['Groups', $groups, '/learning/academy/groups'], ['Placement pending', $placement, '/learning/academy?stage=placement'], ['Pending leave requests', $pendingLeave, '/learning/academy/teacher/leaves']] as [$label,$number,$path])
<a href="{{ url($path) }}" class="bg-white rounded p-4 block"><p>{{ $label }}</p><p class="font-semibold text-xl my-2">{{ $number }}</p></a>
@endforeach
<div class="bg-white rounded p-4"><p>Teachers</p><p class="font-semibold text-xl my-2">{{ $counts->get(5,0) }}</p></div>
<div class="bg-white rounded p-4"><p>Parents</p><p class="font-semibold text-xl my-2">{{ $counts->get(7,0) }}</p></div>
</div>
<div class="flex flex-wrap my-3"><a class="blue-bg text-white rounded px-3 py-2 mr-2" href="{{ url('/learning/academy') }}">Students & progress</a><a class="border bg-white rounded px-3 py-2 mr-2" href="{{ url('/learning/academy/groups') }}">Groups & teachers</a><a class="border bg-white rounded px-3 py-2" href="{{ url('/learning/academy/notices') }}">Notice board</a></div>
<section class="bg-white rounded p-4 my-3"><h2 class="font-semibold text-lg">Recent learning activity</h2>
@forelse($events as $event)<p class="my-3">{{ str_replace('_',' ',$event->event) }} · {{ $event->created_at }} @if($event->student_id)· <a class="underline" href="{{ url('/learning/academy/students/'.$event->student_id) }}">Student #{{ $event->student_id }}</a>@endif</p>@empty<p class="my-3">No academy changes recorded yet.</p>@endforelse
<a class="underline" href="{{ url('/learning/academy/activity') }}">View activity log</a></section>
@endsection
