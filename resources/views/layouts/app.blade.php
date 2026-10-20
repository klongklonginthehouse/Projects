<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'IskolarNotes')</title>
    <link rel="stylesheet" href="{{ asset('css/iskolar.css') }}">
</head>
<body>
@php $me = auth()->user(); @endphp
<header class="top">
    <div class="wrap bar">
        <a class="brand" href="{{ route('browse') }}">
            <h1>IskolarNotes</h1>
            <p>Notes and past exams, shared by students, checked by students.</p>
        </a>
        <div class="who">
            <span class="chip"><x-icon name="coins" :size="14" /> {{ $me->credits }} credits</span>
            <span class="chip"><x-icon name="user" :size="14" /> {{ $me->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="linkbtn" type="submit"><x-icon name="log-out" :size="14" /> Log out</button>
            </form>
        </div>
    </div>
    <nav class="wrap nav" aria-label="Main">
        <a href="{{ route('browse') }}" class="{{ request()->routeIs('browse', 'resources.show') ? 'on' : '' }}"><x-icon name="book-open" /> Browse</a>
        @if($me->canUpload())
            <a href="{{ route('upload') }}" class="{{ request()->routeIs('upload') ? 'on' : '' }}"><x-icon name="upload" /> {{ $me->role === 'faculty' ? 'Publish official' : 'Upload' }}</a>
        @endif
        @if($me->isStaff())
            <a href="{{ route('review') }}" class="{{ request()->routeIs('review') ? 'on' : '' }}"><x-icon name="shield-check" /> Review queue</a>
        @endif
        <a href="{{ route('shelf') }}" class="{{ request()->routeIs('shelf') ? 'on' : '' }}"><x-icon name="file-text" /> My shelf</a>
    </nav>
</header>

<main class="wrap">
    @if(session('status'))<div class="flash ok" role="status">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="flash err" role="alert">{{ session('error') }}</div>@endif
    @yield('content')
</main>

@if(session('download_url'))
    <iframe src="{{ session('download_url') }}" hidden title="Download"></iframe>
@endif
</body>
</html>
