@extends($layout)
@section('content')
<h1 class="admin-h1 my-4">{{ $role === 5 ? 'Teacher dashboard' : ($role === 7 ? 'Parent dashboard' : 'Student dashboard') }}</h1>
@include('academy.messages')
@if($role === 5)
<div class="my-3" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px">
    @foreach(['My groups' => $groups->count(), 'My students' => $students->count(), 'Pending leave requests' => $pendingLeave] as $label => $value)<div class="bg-white rounded p-4"><p>{{ $label }}</p><p class="font-semibold text-xl my-2">{{ $value }}</p></div>@endforeach
</div>
<div class="flex flex-wrap my-3"><a class="blue-bg text-white px-3 py-2 rounded mr-2" href="{{ url('/learning/academy/messages') }}">Messages</a><a class="border bg-white px-3 py-2 rounded mr-2" href="{{ url('/learning/academy/teacher/leaves') }}">My leave requests</a><a class="border bg-white px-3 py-2 rounded" href="{{ url('/learning/academy/teacher/attendance') }}">My attendance</a></div>
@forelse($groups as $group)
<section class="bg-white rounded p-4 my-3"><h2 class="font-semibold text-lg">{{ $group->group_name }}</h2><p class="my-2">@include('academy.schedule')</p><a class="blue-bg text-white px-3 py-2 rounded inline-block my-2" href="{{ url('/learning/academy/groups/'.$group->id).'#eight-sessions' }}">Open eight-session checklist</a>
    @foreach($groupStudents->get($group->id, collect()) as $student)<p class="my-2"><a class="underline" href="{{ url('/learning/academy/students/'.$student->id) }}">{{ trim(($student->firstname ?? '').' '.($student->lastname ?? '')) ?: $student->name }}</a> · {{ $group->group_name }}</p>@endforeach
</section>
@empty<p class="bg-white rounded p-4">No groups or students assigned to you yet.</p>@endforelse
@else
@if($students->isEmpty())
<div class="bg-white rounded p-4 my-3">{{ $role === 7 ? 'No student assigned to you yet. Ask the administrator to link your child to your parent account.' : 'No student record assigned to this account yet.' }}</div>
@else
@if($role === 7)<p class="my-3">Only your linked children appear here. Choose a child in the left menu to open their recordings, homework and feedback links.</p>@endif
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr));gap:16px" class="my-4">
@foreach($cards as $card)
<section class="bg-white rounded p-4"><h2 class="font-semibold text-lg">{{ trim(($card['student']->firstname ?? '').' '.($card['student']->lastname ?? '')) ?: $card['student']->name }}</h2>
    @include('academy.checklist-readonly', ['progress' => $card['progress']])
    <a class="underline mr-3" href="{{ url('/learning/academy/students/'.$card['student']->id) }}">Learning record & feedback</a>
    <a class="underline" href="{{ url('/learning/academy/messages/'.$card['student']->id) }}">Messages</a>
</section>
@endforeach
</div>
@endif
@endif
@endsection
