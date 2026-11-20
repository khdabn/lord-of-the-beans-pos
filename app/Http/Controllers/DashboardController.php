<?php
namespace App\Http\Controllers;
use App\Models\{Product, Customer, Sale};
class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = Sale::whereDate('created_at', today());
        $daily = Sale::selectRaw('DATE(created_at) as day, SUM(total_price) as total')
            ->where('created_at','>=',today()->subDays(6))->groupByRaw('DATE(created_at)')->pluck('total','day');
        $days = collect(range(6,0))->map(fn ($offset) => [
            'label'=>today()->subDays($offset)->format('D'),
            'total'=>(float)($daily[today()->subDays($offset)->toDateString()] ?? 0),
        ]);
        return view('dashboard', [
            'revenue'=>(clone $today)->sum('total_price'),'transactions'=>(clone $today)->count(),
            'units'=>(clone $today)->sum('quantity'), 'productCount'=>Product::active()->count(),
            'customerCount'=>Customer::count(), 'lowStock'=>Product::active()->where('stock_quantity','<=',5)->orderBy('stock_quantity')->limit(5)->get(),
            'recent'=>Sale::latest('id')->limit(5)->get(), 'days'=>$days, 'chartMax'=>max(1,$days->max('total')),
        ]);
    }
}
