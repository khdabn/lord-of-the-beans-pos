@extends('layouts.app')
@section('title','Group 4')
@section('content')
<div class="page-heading"><div><span class="section-kicker">THE PEOPLE BEHIND Lord of the Beans</span><h1>Four people. One shared table.</h1><p>IT0049 · Complete Point-of-Sale System · Group 4</p></div></div>
<section class="team-intro"><h2>Lord of the Beans</h2><p>A café-inspired point-of-sale project for managing menu items, customers, staff, and daily sales. Built around warm coffee, good company, and a well-run counter.</p></section>
<div class="team-grid">
@foreach(['Butad, Carlos Joaquin A.','Cafugauan, Mc Kenrick L.','Buen, Angelo P.','Orlanda, Mark John M.'] as $member)
<article class="panel team-card"><span class="team-number">0{{ $loop->iteration }}</span><div><h2>{{ $member }}</h2><p>Group 4 · Project member</p></div></article>
@endforeach
</div>
@endsection
