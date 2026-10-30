<div class="my-3">
    <p class="font-semibold">Attendance: {{ $progress['present'] }}/8 · {{ number_format($progress['attendance_percent'], 1) }}%</p>
    <progress class="w-full" value="{{ $progress['attendance_percent'] }}" max="100" aria-label="Attendance out of eight sessions"></progress>
    <p class="text-sm my-1">{{ $progress['absent'] }} absent · {{ $progress['attendance_pending'] }} not recorded</p>
    <p class="font-semibold mt-3">Homework completed: {{ $progress['homework_done'] }}/8 · {{ number_format($progress['homework_percent'], 1) }}%</p>
    <progress class="w-full" value="{{ $progress['homework_percent'] }}" max="100" aria-label="Homework completion out of eight sessions"></progress>
    <p class="text-sm my-1">{{ $progress['homework_pending'] }} homework statuses not recorded. Percentages use all eight slots; blanks are not absences or missing work.</p>
</div>
