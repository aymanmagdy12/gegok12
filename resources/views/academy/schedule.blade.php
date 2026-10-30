@php($meetingDays = json_decode($group->meeting_days ?? '[]', true) ?: [])
@if($meetingDays && $group->start_time)
    {{ implode(', ', $meetingDays) }} · {{ substr($group->start_time, 0, 5) }}@if($group->end_time)–{{ substr($group->end_time, 0, 5) }}@endif · Cairo time
@else
    Meeting day/time not set
@endif
