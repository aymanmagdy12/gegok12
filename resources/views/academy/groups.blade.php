@extends($layout)
@section('content')
<h1 class="admin-h1 my-4">{{ $role === 5 ? 'My groups' : 'Groups & teachers' }}</h1>
@include('academy.messages')
@if($role === 3)
<div class="bg-white rounded p-4 my-3">
    <h2 class="font-semibold text-lg">New English group</h2>
    <form method="POST" action="{{ url('/learning/academy/groups') }}" class="my-3">
        @csrf
        @include('academy.group-fields', ['group' => null])
        <button class="custom-green text-white px-3 py-2 rounded">Create group</button>
    </form>
</div>
@endif
@forelse($groups as $group)
<section class="bg-white rounded p-4 my-3">
    <h2 class="font-semibold text-lg"><a class="underline" href="{{ url('/learning/academy/groups/'.$group->id) }}">{{ $group->group_name }}</a> · {{ $group->level ? 'Level '.$group->level : 'Course level needs setup' }}</h2>
    <p class="my-2">@include('academy.schedule')</p>
    <p class="my-2">Responsible teacher: {{ $group->teacher_name ?: 'Not assigned' }} · {{ $group->schedule }}</p>
    <div class="my-3"><p class="font-semibold">Students in {{ $group->group_name }} ({{ $groupStudents->get($group->id, collect())->count() }})</p>
        @forelse($groupStudents->get($group->id, collect()) as $student)<p class="my-1"><a class="underline" href="{{ url('/learning/academy/students/'.$student->id) }}">{{ trim(($student->firstname ?? '').' '.($student->lastname ?? '')) ?: $student->name }}</a></p>@empty<p>No students yet.</p>@endforelse
    </div>
    @if($role === 3)
    <details class="my-3"><summary class="cursor-pointer underline">Edit group / set course level</summary>
        <form method="POST" action="{{ url('/learning/academy/groups/'.$group->id) }}" class="my-3">
            @csrf @include('academy.group-fields')
            <button class="blue-bg text-white px-3 py-2 rounded">Save group</button>
        </form>
        <form method="POST" action="{{ url('/learning/academy/groups/'.$group->id.'/archive') }}">@csrf
            <p class="my-2">Only empty groups can be archived. Move enrolled students first. History is retained.</p>
            <button class="border px-3 py-2 rounded">Archive empty group</button>
        </form>
    </details>
    @endif
    <a class="underline" href="{{ url('/learning/academy/groups/'.$group->id) }}">Open group & students</a>

</section>
@empty<p class="bg-white rounded p-4">No groups assigned yet.</p>@endforelse
{{ $groups->links() }}
@endsection
