@extends($layout)
@section('content')
<h1 class="admin-h1 my-4">Notice board</h1>
@include('academy.messages')
<form method="GET" class="my-3"><label>Search notices <input type="search" name="q" maxlength="200" value="{{ request('q') }}" class="tw-form-control"></label><button class="blue-bg text-white rounded px-3 py-2">Search</button></form>
@if($role === 3)<p class="my-3"><a class="underline" href="{{ url('/admin/notice/add') }}">Add notice</a></p>@endif
@forelse($notices as $notice)
<article class="bg-white rounded p-4 my-3"><h2 class="font-semibold text-lg">{{ $notice->title }}</h2><p class="text-sm my-2">{{ $notice->publish_date ? \Illuminate\Support\Carbon::parse($notice->publish_date)->timezone('Africa/Cairo')->format('d M Y · g:i A') : '' }}</p><p style="white-space:pre-wrap">{{ html_entity_decode(strip_tags($notice->description ?? ''), ENT_QUOTES, 'UTF-8') }}</p>@if(!empty($notice->attachment_file))<p class="my-2"><a class="underline" href="{{ url('/learning/academy/notices/'.$notice->id.'/attachment') }}">Download attachment</a></p>@endif</article>
@empty<p class="bg-white rounded p-4 my-3">No notices available.</p>@endforelse
{{ $notices->links() }}
@endsection
