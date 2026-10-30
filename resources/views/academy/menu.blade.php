@php
    $academyRole = (int) auth()->user()->usergroup_id;
    $academyPrefix = [3 => 'admin', 5 => 'teacher', 6 => 'student'][$academyRole] ?? null;
    $academyLinks = ['Students & progress' => '/learning/academy'];
    if (in_array($academyRole, [3, 5])) { $academyLinks['Groups & teachers'] = '/learning/academy/groups'; }
    if ($academyRole === 3) {
        $academyLinks += ['Add student' => '/learning/academy/accounts/new/student', 'Teachers' => '/admin/teachers', 'Parents' => '/admin/parents', 'Learning activity log' => '/learning/academy/activity', 'Birthdays' => '/admin/dashboard/birthday', 'Attendance' => '/admin/attendance/add'];
    }
    if ($academyPrefix) {
        $academyLinks += ['Dashboard' => '/'.$academyPrefix.'/dashboard', 'Homework' => '/'.$academyPrefix.'/homeworks', 'Messages' => '/'.$academyPrefix.'/conversations', 'Notices' => '/'.$academyPrefix.'/notices'];
    }
    if ($academyRole === 5) { $academyLinks['Attendance & homework'] = '/learning/academy/groups'; }
@endphp
<ul class="list-reset text-sm">
@foreach($academyLinks as $label => $path)
    <li class="py-3 px-3 hover:bg-purple-900 {{ request()->getPathInfo() === $path ? 'active' : '' }}">
        <a href="{{ url($path) }}" class="flex items-center"><span class="mx-3 whitespace-no-wrap">{{ $label }}</span></a>
    </li>
@endforeach
</ul>
