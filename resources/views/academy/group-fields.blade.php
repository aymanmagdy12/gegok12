<label class="block my-2">Group name <input required class="tw-form-control" name="group_name" maxlength="100" value="{{ $group->group_name ?? old('group_name') }}"></label>
<label class="block my-2">Course level <select required name="level" class="tw-form-control"><option value="">Choose level</option>
    @foreach(range(1,6) as $level)<option value="{{ $level }}" @selected((int) ($group->level ?? old('level')) === $level)>Level {{ $level }}</option>@endforeach
</select></label>
<label class="block my-2">Responsible teacher <select required name="teacher_id" class="tw-form-control"><option value="">Choose teacher</option>
    @foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((int) ($group->class_teacher_id ?? old('teacher_id')) === (int) $teacher->id)>{{ trim(($teacher->firstname ?? '').' '.($teacher->lastname ?? '')) }} · {{ $teacher->email }}</option>@endforeach
</select></label>
<label class="block my-2">Meeting times / lesson link (optional)<input name="schedule" class="tw-form-control w-full" maxlength="500" value="{{ $group->schedule ?? old('schedule') }}"></label>
