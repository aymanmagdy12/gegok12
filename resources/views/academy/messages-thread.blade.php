@extends($layout)
@section('content')
<h1 class="admin-h1 my-4">Messages</h1>
@include('academy.messages')
<p class="my-3">Each conversation is shared with the selected student, their linked parents and authorized academy staff.</p>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr));gap:16px" class="my-4">
<aside class="bg-white rounded p-4"><h2 class="font-semibold">{{ $role === 7 ? 'My children' : 'Students' }}</h2>
    @forelse($students as $student)<p class="my-3"><a class="underline" href="{{ url('/learning/academy/messages/'.$student->id) }}">{{ trim(($student->firstname ?? '').' '.($student->lastname ?? '')) ?: $student->name }}</a></p>@empty<p class="my-3">{{ $role === 7 ? 'No student assigned to you yet.' : 'No students assigned to you yet.' }}</p>@endforelse
    {{ $students->links() }}
</aside>
<section class="bg-white rounded p-4">
@if($person)
    <h2 class="font-semibold">Conversation: {{ $person->display_name }}</h2><a class="underline my-2 inline-block" href="{{ url('/learning/academy/students/'.$person->id) }}">Open student learning record</a>
    @forelse($messages->getCollection()->reverse() as $message)
    <article class="border rounded p-3 my-3"><p class="font-semibold">{{ trim(($message->firstname ?? '').' '.($message->lastname ?? '')) }} · {{ [3 => 'Administrator',5 => 'Teacher',6 => 'Student',7 => 'Parent'][$message->sender_role] ?? 'Staff' }}</p><p class="text-sm">{{ $message->created_at }}{{ $message->origin === 'feedback' ? ' · Teacher feedback' : '' }}</p><p class="my-2" style="white-space:pre-wrap">{{ $message->message }}</p></article>
    @empty<p class="my-3">No messages yet. Start the conversation below.</p>@endforelse
    {{ $messages->links() }}
    <form method="POST" class="my-4" action="{{ url('/learning/academy/messages/'.$person->id) }}">@csrf<label class="block">Message<textarea required name="message" maxlength="5000" rows="4" class="tw-form-control block w-full">{{ old('message') }}</textarea></label><button class="blue-bg text-white px-4 py-2 rounded my-3">Send message</button></form>
@else<p class="my-3">Choose a student to open their conversation.</p>@endif
</section>
</div>
@endsection
