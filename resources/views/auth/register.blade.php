<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account - IskolarNotes</title>
    <link rel="stylesheet" href="{{ asset('css/iskolar.css') }}">
</head>
<body>
<div class="wrap auth">
    <section class="hero">
        <h1>Join IskolarNotes</h1>
        <p>You start with 10 credits. Downloading costs credits, and every note you share that gets verified earns you 5 more.</p>
    </section>

    <section class="panel">
        <h2>Create account</h2>
        <form method="POST" action="{{ url('/register') }}">
            @csrf
            <label for="name">Full name</label>
            <input id="name" name="name" value="{{ old('name') }}" required>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            <label for="password">Password (at least 8 characters)</label>
            <input id="password" name="password" type="password" required>
            <label for="role">I want to</label>
            <select id="role" name="role">
                <option value="student" @selected(old('role') === 'student')>Study from notes</option>
                <option value="author" @selected(old('role') === 'author')>Upload and study</option>
            </select>
            @if($errors->any())
                <ul class="errors">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            @endif
            <p><button class="btn maroon" type="submit"><x-icon name="user-plus" /> Create account</button></p>
        </form>
        <p style="font-size:14px">Already registered? <a href="{{ route('login') }}">Log in</a></p>
    </section>
</div>
</body>
</html>
