<?php
namespace App\Http\Controllers;
use App\Models\{Sale, Product, Customer};
use App\Services\SaleService;
use App\Http\Requests\SaleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class SaleController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['from'=>['nullable','date_format:Y-m-d'],'to'=>['nullable','date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],'q'=>['nullable','string','max:100']]);
        $query = Sale::query();
        if (!empty($filters['from'])) $query->where('created_at','>=',$filters['from'].' 00:00:00');
        if (!empty($filters['to'])) $query->where('created_at','<=',$filters['to'].' 23:59:59');
        if ($q = $filters['q'] ?? null) $query->where(fn ($builder) => $builder->where('product_name','like',"%$q%")->orWhere('customer_name','like',"%$q%")->orWhere('staff_name','like',"%$q%"));
        return view('sales.index',['total'=>(clone $query)->sum('total_price'), 'sales'=>$query->latest('id')->paginate(15)->withQueryString()]);
    }
    public function create(Request $request)
    {
        $data = $request->validate(['q'=>['nullable','string','max:100']]);
        $products = Product::active()->orderBy('name');
        if ($q = $data['q'] ?? null) $products->where('name','like',"%$q%");
        return view('sales.create', ['products'=>$products->paginate(12)->withQueryString(), 'customers'=>Customer::orderBy('full_name')->get(['id','full_name']), 'token'=>Str::uuid()->toString()]);
    }
    public function store(SaleRequest $request, SaleService $service)
    {
        $sale = $service->record($request->validated(), $request->user());
        return redirect()->route('sales.show',$sale)->with('success','Sale recorded. Inventory has been updated.');
    }
    public function show(Sale $sale) { return view('sales.show',compact('sale')); }
}
