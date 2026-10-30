<label class="block my-2">Group name <input required class="tw-form-control" name="group_name" maxlength="100" value="{{ $group->group_name ?? old('group_name') }}"></label>
<label class="block my-2">Course level <select required name="level" class="tw-form-control"><option value="">Choose level</option>
    @foreach(range(1,6) as $level)<option value="{{ $level }}" @selected((int) ($group->level ?? old('level')) === $level)>Level {{ $level }}</option>@endforeach
</select></label>
<label class="block my-2">Responsible teacher <select required name="teacher_id" class="tw-form-control"><option value="">Choose teacher</option>
    @foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((int) ($group->class_teacher_id ?? old('teacher_id')) === (int) $teacher->id)>{{ trim(($teacher->firstname ?? '').' '.($teacher->lastname ?? '')) }} · {{ $teacher->email }}</option>@endforeach
</select></label>
<label class="block my-2">Lesson link / additional notes (optional)<input name="schedule" class="tw-form-control w-full" maxlength="500" value="{{ $group->schedule ?? old('schedule') }}"></label>

<input type="hidden" name="structured_schedule" value="1">
@php($savedDays = old('meeting_days', json_decode($group->meeting_days ?? '[]', true) ?: []))
<fieldset class="my-3"><legend>Meeting days (Cairo time)</legend>
@foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)
<label class="inline-block mr-3 my-1"><input type="checkbox" name="meeting_days[]" value="{{ $day }}" @checked(in_array($day, $savedDays))> {{ $day }}</label>
@endforeach
</fieldset>
<label class="block my-2">Start time (Cairo) <input required type="time" name="start_time" class="tw-form-control" value="{{ old('start_time', substr($group->start_time ?? '', 0, 5)) }}"></label>
<label class="block my-2">End time (optional) <input type="time" name="end_time" class="tw-form-control" value="{{ old('end_time', substr($group->end_time ?? '', 0, 5)) }}"></label>
