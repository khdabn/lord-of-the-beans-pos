<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Overview') · Lord of the Beans</title>
<link rel="icon" href="{{ asset('images/cafe-mark.svg') }}" type="image/svg+xml">
<link rel="stylesheet" href="{{ asset('css/app.css').'?v=beans-1' }}">
<script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<aside class="sidebar" id="sidebar">
<a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark beans-mark">LB</span><span class="beans-wordmark">Lord of<br>the Beans<span class="brand-caption">THE CAFÉ WORKSPACE</span></span></a>
<div class="store-card"><span class="store-dot"></span><div>Lord of the Beans<small>Good coffee. Shared moments.</small></div></div>
<span class="nav-label">WORKSPACE</span>
<nav aria-label="Main navigation">
@foreach([['dashboard','grid','Overview'],['sales.create','bag','Record sale'],['products.index','box','Café menu'],['customers.index','people','Customers'],['staff.index','staff','Staff'],['sales.index','history','Sales history'],['group','people','Group 4']] as [$route,$icon,$label])
@php $active = $route === 'sales.index' ? request()->routeIs('sales.index','sales.show') : ($route === 'sales.create' || $route === 'dashboard' || $route === 'group' ? request()->routeIs($route) : request()->routeIs(strtok($route,'.').'.*')); @endphp
<a href="{{ route($route) }}" class="nav-item {{ $active ? 'active' : '' }}" @if($active) aria-current="page" @endif><x-icon :name="$icon"/><span>{{ $label }}</span>@if($route === 'sales.create')<span class="nav-plus">+</span>@endif</a>
@endforeach
</nav>
<div class="sidebar-bottom"><div class="sidebar-note"><span class="eyebrow">TAKE A LITTLE BREW BREAK.</span><p>Good coffee.<br>Great company.</p><span class="note-orbit" aria-hidden="true"></span></div>
<div class="account"><img src="{{ auth()->user()->avatar_url }}" alt="" width="36" height="36"><div><strong>{{ auth()->user()->full_name }}</strong><small>Staff workspace</small></div><form action="{{ route('logout') }}" method="post">@csrf<button class="icon-button" title="Log out" aria-label="Log out"><x-icon name="logout"/></button></form></div></div>
</aside>
<div class="workspace">
<header class="topbar"><div class="breadcrumb"><button class="icon-button mobile-menu" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false"><x-icon name="menu"/></button><span>Workspace</span><x-icon name="chevron"/><strong>@yield('title', 'Overview')</strong></div><div class="topbar-right"><span class="today">{{ now()->format('D, d M Y') }}</span><span class="live-badge"><i></i> Café workspace</span></div></header>
<main id="main" tabindex="-1">
@if(session('success'))<div class="alert success" role="status"><x-icon name="check"/>{{ session('success') }}</div>@endif
<x-errors/>
@yield('content')
</main>
<footer class="site-footer"><span>Lord of the Beans / Coffee & company.</span><span>IT0049 · <a class="group-link" href="{{ route('group') }}">Group 4 · Meet the team</a></span></footer>
</div>
</body></html>
