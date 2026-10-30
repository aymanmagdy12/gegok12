@include('academy.checklist-progress')
@if($progress['participant'])
<p class="my-2">{{ $progress['participant']->group_name }} · Set {{ $progress['participant']->number }}</p>
<details class="my-3"><summary class="cursor-pointer underline">View the eight sessions</summary>
    <table class="w-full text-sm"><thead><tr class="text-left"><th class="p-2">Session</th><th class="p-2">Attendance</th><th class="p-2">Homework</th></tr></thead><tbody>
    @foreach($progress['marks'] as $mark)<tr class="border-b"><td class="p-2">{{ $mark->session }}</td><td class="p-2">{{ $mark->attendance === null ? '— Not recorded' : ((int) $mark->attendance === 1 ? '✓ Present' : '× Absent') }}</td><td class="p-2">{{ $mark->homework === null ? '— Not recorded' : ((int) $mark->homework === 1 ? '✓ Complete' : '× Not complete') }}</td></tr>@endforeach
    </tbody></table>
</details>
@else<p class="my-2">No eight-session checklist has been opened for the current group yet.</p>@endif

@if($progress['history']->count() > 1)
<form method="GET" class="my-3" action="{{ url('/learning/academy/students/'.$progress['student_id']) }}"><label>Checklist history <select name="cycle" class="tw-form-control">@foreach($progress['history'] as $cycle)<option value="{{ $cycle->id }}" @selected($progress['participant']?->cycle_id === $cycle->id)>{{ $cycle->group_name }} · Set {{ $cycle->number }}</option>@endforeach</select></label><button class="border px-3 py-2 rounded">Show</button></form>
@endif
