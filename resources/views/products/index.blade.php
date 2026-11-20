@extends('layouts.app')
@section('title','Products')
@section('content')
<div class="page-heading"><div><span class="section-kicker">FROM OUR COFFEE BAR</span><h1>A menu worth coming back for.</h1><p>{{ $count }} active menu items. Brewed and baked with care.</p></div><a class="btn primary" href="{{ route('products.create') }}"><x-icon name="plus"/> Add menu item</a></div>
<form class="toolbar" method="get"><div class="search-field"><x-icon name="search"/><input aria-label="Search products" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Find a coffee or pastry…"></div><select name="status" aria-label="Product status"><option value="active">Active menu items</option><option value="low" @selected(request('status')==='low')>Low stock</option><option value="archived" @selected(request('status')==='archived')>Archived</option></select><button class="btn" type="submit">Filter</button>@if(request()->hasAny(['q','status']))<a class="text-link" href="{{ route('products.index') }}">Reset</a>@endif</form>
@if(request('status') === 'archived')
<p class="hint">Archived items with no recorded sales can be permanently deleted. Items used in sales remain protected.</p>
@endif
<div class="product-grid">@forelse($products as $product)
<article class="product-card"><a class="product-picture" href="{{ route('products.edit',$product) }}"><img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"><span class="product-code">SK / {{ str_pad($product->id,3,'0',STR_PAD_LEFT) }}</span></a><div class="product-detail"><div class="card-meta"><span class="badge {{ $product->is_archived ? 'neutral' : ($product->stock_quantity===0 ? 'red' : ($product->stock_quantity<=5 ? 'amber':'green')) }}">{{ $product->is_archived ? 'Archived' : ($product->stock_quantity===0 ? 'Out of stock' : $product->stock_quantity.' in stock') }}</span><span class="product-price">{{ \App\Support\Money::format($product->price) }}</span></div><h2>{{ $product->name }}</h2><div class="card-actions"><a class="text-link" href="{{ route('products.edit',$product) }}">Edit menu item <x-icon name="arrow"/></a>@if($product->is_archived)<form method="post" action="{{ route('products.restore',$product) }}">@csrf @method('PATCH')<button class="link-button">Restore</button></form>@else<form method="post" action="{{ route('products.destroy',$product) }}" data-confirm="Archive this product? It will no longer be available for new sales.">@csrf @method('DELETE')<button class="link-button muted">Archive</button></form>@endif</div>
@if($product->is_archived)
<div style="border-top:1px solid var(--line);margin-top:12px;padding-top:12px">
    @if($product->sales_count === 0)
    <form method="post" action="{{ route('products.permanent-delete',$product) }}" data-confirm="Permanently delete this menu item? This removes it from the database and cannot be undone.">
        @csrf @method('DELETE')
        <button type="submit" class="link-button danger" aria-label="Permanently delete {{ $product->name }}">Delete permanently</button>
    </form>
    @else
    <span class="hint">Has recorded sales · deletion protected</span>
    @endif
</div>
@endif
</div></article>
@empty<div class="empty panel wide"><x-icon name="box"/><h2>Your menu starts here.</h2><p>Add a menu item or try a different search.</p><a class="btn primary" href="{{ route('products.create') }}">Add menu item</a></div>@endforelse</div>
{{ $products->links() }}
@endsection
