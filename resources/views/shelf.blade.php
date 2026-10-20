@extends('layouts.app')
@section('title', 'My shelf - IskolarNotes')
@section('content')
    <div class="panel badge" style="margin-bottom:24px">
        <x-icon name="award" :size="36" class="award" />
        <div>
            <div class="big">{{ $badge ?? 'No badge yet' }}</div>
            <div class="meta" style="margin:0">
                {{ auth()->user()->name }} · {{ auth()->user()->credits }} credits · {{ $reputation }} reputation points
            </div>
        </div>
    </div>

    <h2>Your uploads and downloads</h2>
    <p class="sub">Verified uploads earn badges: 1 for First Contributor, 3 for Dean's Lister, 5 for Iskolar ng Bayan.</p>

    @forelse($items as $r)
        <article class="row">
            <div class="code"><b>{{ $r->course_code }}</b><span>{{ ucfirst(str_replace('_', ' ', $r->type)) }}</span></div>
            <div class="body">
                <h3><a href="{{ route('resources.show', $r) }}">{{ $r->title }}</a></h3>
                <p class="meta">{{ $r->user_id === auth()->id() ? 'Uploaded by you' : 'Downloaded' }} · {{ $r->department->name }}</p>
                <div class="actions">
                    @include('partials.status')
                    @if($r->status === 'verified')
                        <form method="POST" action="{{ route('resources.download', $r) }}">
                            @csrf
                            <button type="submit" class="btn small"><x-icon name="download" :size="14" /> Download again</button>
                        </form>
                    @elseif($r->user_id === auth()->id() && $r->file_path)
                        <a class="btn ghost small" href="{{ route('resources.file', $r) }}"><x-icon name="eye" :size="14" /> Open file</a>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="empty">Your shelf is empty. Download something from Browse to keep it here.</div>
    @endforelse
@endsection
