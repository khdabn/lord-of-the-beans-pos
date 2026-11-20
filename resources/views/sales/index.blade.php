@extends('layouts.app')
@section('title','Sales history')
@section('content')
<div class="page-heading"><div><span class="section-kicker">EVERY SALE HAS A STORY</span><h1>A record of good business.</h1><p>Every transaction, kept in one place.</p></div><a class="btn primary" href="{{ route('sales.create') }}"><x-icon name="plus"/> Record sale</a></div>
<section class="history-summary"><div><span class="eyebrow">FILTERED SALES TOTAL</span><strong>{{ \App\Support\Money::format($total) }}</strong></div><span>{{ $sales->total() }} {{ \Illuminate\Support\Str::plural('transaction',$sales->total()) }}</span><p>Recorded in Philippine pesos · Asia/Manila</p></section>
<form class="toolbar history-filters" method="get"><div class="search-field"><x-icon name="search"/><input name="q" aria-label="Search sales" value="{{ request('q') }}" maxlength="100" placeholder="Product, customer, or staff…"></div><label>From<input type="date" name="from" value="{{ request('from') }}"></label><label>To<input type="date" name="to" value="{{ request('to') }}"></label><button class="btn">Apply</button><a class="text-link" href="{{ route('sales.index') }}">Reset</a></form><section class="panel table-panel">@include('sales._table')</section>{{ $sales->links() }}
@endsection
