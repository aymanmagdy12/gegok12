@foreach(['recordings_url' => 'Recorded sessions', 'homework_drive_url' => 'Homework link', 'report_card_url' => 'Feedback / report cards'] as $field => $label)
    <p class="my-2">@if(!empty($links?->$field))<a class="underline" href="{{ $links->$field }}" target="_blank" rel="noopener noreferrer">{{ $label }} ↗</a>@else{{ $label }} · Not added yet @endif</p>
@endforeach
