<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Staff login · Lord of the Beans</title><link rel="icon" href="{{ asset('images/cafe-mark.svg') }}"><link rel="stylesheet" href="{{ asset('css/app.css').'?v=beans-1' }}"></head>
<body class="login-page"><main class="login-shell">
<section class="login-story"><a class="brand" href="{{ route('login') }}"><span class="brand-mark beans-mark">LB</span><span class="beans-wordmark">Lord of<br>the Beans<span class="brand-caption">THE CAFÉ WORKSPACE</span></span></a><div><span class="eyebrow">FRESHLY BREWED. EVERY DAY.</span><h1>Good coffee.<br>Better company.</h1><p>A warm space for your daily brews,<br>fresh pastries, and familiar faces.</p></div><div class="login-art" aria-hidden="true"><img src="{{ asset('images/cafe-latte.svg') }}" alt=""><img src="{{ asset('images/cafe-croissant.svg') }}" alt=""><span class="art-label">COFFEE & COMPANY / GROUP 4</span></div><small>LORD OF THE BEANS · POINT OF SALE</small></section>
<section class="login-form"><div class="login-form-inner"><span class="section-kicker">YOUR DAILY START</span><h2>Welcome back.</h2><p class="muted">Sign in to your café workspace.</p><x-errors/>
<form method="post" action="{{ route('login') }}">@csrf
<label for="username">Username</label><input id="username" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus maxlength="50" placeholder="Your staff username">
<label for="password">Password</label><input type="password" id="password" name="password" autocomplete="current-password" required placeholder="Your password">
<button class="btn primary full" type="submit">Enter workspace <x-icon name="arrow"/></button>
</form><p class="login-note"><x-icon name="lock"/>Staff access only. Ask a colleague to create your account.</p></div><small class="muted">IT0049 · Group 4 · Complete Point-of-Sale System</small></section>
</main></body></html>
