<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in - IskolarNotes</title>
    <link rel="stylesheet" href="{{ asset('css/iskolar.css') }}">
</head>
<body>
<div class="wrap auth">
    <section class="hero">
        <h1>IskolarNotes</h1>
        <p>A shared library of lecture notes, reviewers and past exams, organized by department, course and instructor.</p>
        <ul>
            <li>Find notes for your exact course in seconds</li>
            <li>Practice with past exams checked by moderators and faculty</li>
            <li>Earn credits and badges when you share your own notes</li>
        </ul>
        <p class="demo">Demo accounts (password <b>password123</b>): student@iskolar.test, author@iskolar.test, moderator@iskolar.test, faculty@iskolar.test</p>
    </section>

    <section class="panel">
        <h2>Log in</h2>
        <form method="POST" action="{{ url('/login') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
            @if($errors->any())
                <ul class="errors">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            @endif
            <p><button class="btn maroon" type="submit"><x-icon name="log-in" /> Log in</button></p>
        </form>
        <p style="font-size:14px">New here? <a href="{{ route('register') }}">Create an account</a></p>
    </section>
</div>
</body>
</html>
