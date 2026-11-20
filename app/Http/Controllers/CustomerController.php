<?php
namespace App\Http\Controllers;
use App\Models\Customer;
use App\Http\Requests\CustomerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['q'=>['nullable','string','max:100']]);
        $query = Customer::withCount('sales')->withSum('sales','total_price');
        if ($q = $data['q'] ?? null) $query->where(fn ($query) => $query->where('full_name','like',"%$q%")->orWhere('email','like',"%$q%"));
        return view('customers.index',['customers'=>$query->orderBy('full_name')->paginate(12)->withQueryString()]);
    }
    public function create() { return view('customers.form',['customer'=>new Customer]); }
    public function store(CustomerRequest $request)
    {
        Customer::create($request->validated());
        return redirect()->route('customers.index')->with('success','Customer added.');
    }
    public function edit(Customer $customer) { return view('customers.form',compact('customer')); }
    public function update(CustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());
        return redirect()->route('customers.index')->with('success','Customer updated.');
    }
    public function destroy(Customer $customer)
    {
        DB::transaction(fn () => Customer::lockForUpdate()->findOrFail($customer->id)->delete());
        return redirect()->route('customers.index')->with('success','Customer removed from the directory. Past sales remain available.');
    }
}
