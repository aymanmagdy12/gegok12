<section id="eight-sessions" class="bg-white rounded p-4 my-3">
    <h2 class="font-semibold text-lg">Eight-session attendance & homework</h2>
    <p class="my-2">Expand a student, then choose ✓, × or leave a session unrecorded. Saving updates the student and linked-parent dashboards.</p>
    @if($role === 5)
    <form method="POST" class="my-3" action="{{ url('/learning/academy/groups/'.$record->id.'/checklists') }}">@csrf<button class="blue-bg text-white px-3 py-2 rounded">{{ $checklist['selected'] ? 'Refresh current student list' : 'Open the eight-session checklist' }}</button></form>
    @endif
    @if($checklist['cycles']->isNotEmpty())
    <form method="GET" class="my-3"><label>Set <select name="cycle" class="tw-form-control">@foreach($checklist['cycles'] as $cycle)<option value="{{ $cycle->id }}" @selected($checklist['selected']?->id === $cycle->id)>Set {{ $cycle->number }}{{ $cycle->active ? ' · Current' : ' · Archived' }}</option>@endforeach</select></label><button class="border px-3 py-2 rounded">Show</button></form>
    @endif
    @foreach($checklist['participants'] as $participant)
    @php($editable = $role === 5 && $checklist['selected']->active && $roster->contains('id', $participant->student_id))
    <details class="border rounded p-3 my-3" @if((int) request('student') === (int) $participant->student_id) open @endif><summary class="cursor-pointer font-semibold">{{ trim(($participant->firstname ?? '').' '.($participant->lastname ?? '')) ?: $participant->name }}</summary>
        @if($roster->contains('id', $participant->student_id))<p class="my-2"><a class="underline" href="{{ url('/learning/academy/students/'.$participant->student_id) }}">Learning record, links & feedback</a></p>@endif
        <form method="POST" action="{{ url('/learning/academy/groups/'.$record->id.'/checklists/'.$participant->cycle_id.'/students/'.$participant->student_id) }}">@csrf
            <input type="hidden" name="revision" value="{{ $participant->revision }}">
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="text-left"><th class="p-2">Session</th><th class="p-2">Attendance</th><th class="p-2">Homework</th></tr></thead><tbody>
            @foreach($checklist['marks']->get($participant->id, collect()) as $mark)
            <tr class="border-b"><td class="p-2">{{ $mark->session }}</td>
                @foreach(['attendance' => ['1' => '✓ Present', '0' => '× Absent'], 'homework' => ['1' => '✓ Complete', '0' => '× Not complete']] as $field => $options)
                <td class="p-2"><select class="tw-form-control" name="marks[{{ $mark->session }}][{{ $field }}]" aria-label="Session {{ $mark->session }} {{ $field }}" @disabled(!$editable)><option value="" @selected($mark->$field === null)>— Not recorded</option>@foreach($options as $value => $label)<option value="{{ $value }}" @selected($mark->$field !== null && (int) $mark->$field === (int) $value)>{{ $label }}</option>@endforeach</select></td>
                @endforeach
            </tr>
            @endforeach
            </tbody></table></div>
            @if($editable)<button class="custom-green text-white px-3 py-2 rounded my-3">Save this student's eight sessions</button>@else<p class="my-2">Historical or read-only record.</p>@endif
        </form>
    </details>
    @endforeach
    @if($role === 5 && $checklist['selected'] && $checklist['selected']->active)
    <details class="my-3"><summary class="cursor-pointer underline">Start another set of eight</summary><p class="my-2">The current set will be archived and retained. This does not change the student's level or unit.</p>
        <form method="POST" action="{{ url('/learning/academy/groups/'.$record->id.'/checklists') }}">@csrf<input type="hidden" name="next" value="1"><input type="hidden" name="previous_cycle" value="{{ $checklist['selected']->id }}"><button class="border px-3 py-2 rounded">Archive this set and start the next eight</button></form>
    </details>
    @endif
</section>
