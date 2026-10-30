@php
    $academyRole = (int) auth()->user()->usergroup_id;
    $academyPrefix = [3 => 'admin', 5 => 'teacher', 6 => 'student'][$academyRole] ?? null;
    $academyLinks = ['Dashboard' => $academyRole === 3 ? '/learning/academy' : '/learning/academy/dashboard'];
    if (in_array($academyRole, [3, 5])) { $academyLinks['Groups & teachers'] = '/learning/academy/groups'; }
    if (in_array($academyRole, [3, 5, 7])) { $academyLinks[$academyRole === 7 ? 'My children' : 'Students & progress'] = $academyRole === 7 ? '/learning/academy/dashboard' : '/learning/academy'; }
    $academyLinks['Messages'] = '/learning/academy/messages';
    if ($academyRole === 3) {
        if (!config('academy_backend.test_only')) { $academyLinks['Add student'] = '/learning/academy/accounts/new/student'; }
        $academyLinks += ['Teachers' => '/admin/teachers', 'Parents' => '/admin/parents', 'Teacher leave requests' => '/learning/academy/teacher/leaves', 'Teacher attendance' => '/learning/academy/teacher/attendance', 'Learning activity log' => '/learning/academy/activity', 'Birthdays' => '/admin/dashboard/birthday'];
    }
    if ($academyRole === 3 && config('academy_backend.test_only')) { unset($academyLinks['Teachers'], $academyLinks['Parents']); $academyLinks['Test accounts'] = '/learning/academy/test-accounts'; }
    if ($academyPrefix) { $academyLinks['Notices'] = '/'.$academyPrefix.'/notices'; }
    if ($academyRole === 5) {
        $academyLinks += ['Eight-session checklists' => '/learning/academy/groups', 'My tasks' => '/teacher/tasks', 'Lesson plans' => '/teacher/lessonplans', 'Calendar' => '/teacher/events', 'My leave requests' => '/learning/academy/teacher/leaves', 'My attendance' => '/learning/academy/teacher/attendance', 'Change password' => '/teacher/changepassword', 'Profile photo' => '/teacher/changeavatar'];
    }
    $nav = $academyNav ?? ['students' => collect(), 'selected' => null, 'links' => null];
@endphp
<ul class="list-reset text-sm">
@foreach($academyLinks as $label => $path)
    <li class="py-3 px-3 hover:bg-purple-900 {{ request()->getPathInfo() === $path ? 'active' : '' }}"><a href="{{ url($path) }}" class="flex items-center"><span class="mx-3 whitespace-no-wrap">{{ $label }}</span></a></li>
@endforeach
</ul>
@if(in_array($academyRole, [5,6,7]))
<div class="border-t border-purple-600 p-3 my-3">
    @if($nav['students']->isEmpty())
        <p class="text-sm">{{ $academyRole === 7 ? 'No student assigned to you yet.' : 'No students assigned yet.' }}</p>
    @else
    @php($academySelectorId = 'academy-child-'.uniqid())
    <form method="GET" action="{{ url('/learning/academy/dashboard') }}" class="my-2">
        <label for="{{ $academySelectorId }}" class="block text-sm mb-2">{{ $academyRole === 7 ? 'Select child' : 'Selected student' }}</label>
        <select id="{{ $academySelectorId }}" name="student" class="tw-form-control text-black w-full" style="max-width:170px">
        @foreach($nav['students'] as $child)<option value="{{ $child->id }}" @selected((int) $nav['selected']?->id === (int) $child->id)>{{ trim(($child->firstname ?? '').' '.($child->lastname ?? '')) ?: $child->name }}</option>@endforeach
        </select><button class="border rounded px-3 py-1 my-2">Select</button>
    </form>
    @if($nav['selected'])
    <p class="font-semibold my-2">{{ trim(($nav['selected']->firstname ?? '').' '.($nav['selected']->lastname ?? '')) ?: $nav['selected']->name }}</p>
    @foreach(['recordings_url' => 'Recorded sessions', 'homework_drive_url' => 'Homework link', 'report_card_url' => 'Feedback / report cards'] as $field => $label)
        @if(!empty($nav['links']->$field))<p class="my-3"><a href="{{ $nav['links']->$field }}" target="_blank" rel="noopener noreferrer" class="underline">{{ $label }} ↗</a></p>
        @else<p class="my-3 text-sm">{{ $label }} · Not added yet</p>@endif
    @endforeach
    <p class="my-3"><a class="underline" href="{{ url('/learning/academy/students/'.$nav['selected']->id).'#student-feedback' }}">Teacher feedback</a></p>
    <p class="my-3"><a class="underline" href="{{ url('/learning/academy/messages/'.$nav['selected']->id) }}">Student conversation</a></p>
    @endif
    @endif
</div>
@endif
