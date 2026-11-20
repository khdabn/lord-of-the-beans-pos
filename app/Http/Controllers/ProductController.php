<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Http\Requests\ProductRequest;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q'=>['nullable','string','max:100'],'status'=>['nullable','in:active,low,archived']]);
        $query = Product::query()->where('is_archived', ($filters['status'] ?? '') === 'archived');
        if (($filters['status'] ?? '') === 'low') $query->where('stock_quantity','<=',5);
        if ($search = $filters['q'] ?? null) $query->where('name','like','%'.$search.'%');
        return view('products.index', ['products'=>$query->orderBy('name')->paginate(12)->withQueryString(), 'count'=>Product::active()->count()]);
    }
    public function create() { return view('products.form', ['product'=>new Product(['stock_quantity'=>0])]); }
    public function store(ProductRequest $request, ImageService $images)
    {
        $data = $request->safe()->only(['name','price','stock_quantity']); $new = null;
        if ($request->hasFile('image')) $data['image'] = $new = $images->store($request->file('image'),'image');
        try { Product::create($data); } catch (\Throwable $e) { $images->delete($new); throw $e; }
        return redirect()->route('products.index')->with('success','Product added to your catalog.');
    }
    public function edit(Product $product) { return view('products.form', compact('product')); }
    public function update(ProductRequest $request, Product $product, ImageService $images)
    {
        $data = $request->safe()->only(['name','price','stock_quantity']); $new = null;
        if ($request->boolean('remove_image')) $data['image'] = null;
        if ($request->hasFile('image')) $data['image'] = $new = $images->store($request->file('image'),'image');
        try {
            $old = DB::transaction(function () use ($product, $data, $request) {
                $locked = Product::lockForUpdate()->findOrFail($product->id);
                if ($locked->stock_quantity !== (int)$request->validated('stock_version')) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['stock_quantity'=>'Stock changed while this form was open. Reload this page, review the latest count, then save again.']);
                }
                $old = $locked->image; $locked->update($data); return $old;
            });
        } catch (\Throwable $e) { $images->delete($new); throw $e; }
        if (array_key_exists('image',$data) && $old !== $data['image']) $images->delete($old);
        return redirect()->route('products.index')->with('success','Product updated.');
    }
    public function destroy(Product $product)
    {
        DB::transaction(fn () => Product::lockForUpdate()->findOrFail($product->id)->update(['is_archived'=>true]));
        return redirect()->route('products.index')->with('success','Product archived. Its sales history is preserved.');
    }
    public function restore(Product $product)
    {
        $product->update(['is_archived'=>false]);
        return redirect()->route('products.index')->with('success','Product restored to the catalog.');
    }
}
