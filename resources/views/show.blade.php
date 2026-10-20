@extends('layouts.app')
@section('title', $r->title.' - IskolarNotes')
@section('content')
    @php $free = $r->owned || $r->user_id === auth()->id() || $r->cost === 0; @endphp
    <p><a class="btn ghost small" href="{{ route('browse') }}"><x-icon name="arrow-left" :size="14" /> Back to browse</a></p>

    <div class="panel">
        <div class="actions" style="margin-bottom:8px">
            <span class="chip"><x-icon name="file-text" :size="12" /> {{ ucfirst(str_replace('_', ' ', $r->type)) }}</span>
            @include('partials.status')
        </div>
        <h2>{{ $r->title }}</h2>
        <p class="meta">{{ $r->course_code }} · {{ $r->instructor }} · {{ $r->department->name }}</p>
        <p class="resource-description">{{ $r->description ?: 'No description provided.' }}</p>
        <p class="meta">Uploaded by {{ $r->author->name }} {{ $r->created_at->diffForHumans() }} · {{ $r->votes_count }} upvotes · {{ $r->downloads_count }} downloads</p>

        @if($r->status === 'verified')
            <div class="actions" style="margin-top:18px">
                <form method="POST" action="{{ route('resources.download', $r) }}">
                    @csrf
                    <button type="submit" class="btn">
                        <x-icon name="download" />
                        {{ $free ? 'Download' : 'Download for '.$r->cost.' credits' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('resources.vote', $r) }}">
                    @csrf
                    <button type="submit" class="btn ghost vote {{ $r->voted ? 'on' : '' }}">
                        <x-icon name="thumbs-up" /> {{ $r->voted ? 'Upvoted' : 'Upvote' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('resources.report', $r) }}" onsubmit="return confirm('Send this resource to the moderators for review?')">
                    @csrf
                    <button type="submit" class="linkbtn"><x-icon name="flag" :size="13" /> Report copyright issue</button>
                </form>
            </div>
        @else
            <p class="meta" style="margin-top:16px">This resource is not public yet.</p>
        @endif
    </div>
@endsection
