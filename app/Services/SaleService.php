<?php
namespace App\Services;
use App\Models\{Product, Customer, User, Sale};
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class SaleService
{
    public function record(array $data, User $staff): Sale
    {
        return DB::transaction(function () use ($data, $staff) {
            // The staff lock serializes retries with the same authenticated cashier.
            $cashier = User::query()->lockForUpdate()->findOrFail($staff->id);
            $previous = Sale::where('request_token', $data['request_token'])->first();
            if ($previous) {
                abort_unless($previous->sold_by === $cashier->id, 403);
                return $previous;
            }
            $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);
            if ($product->is_archived) {
                throw ValidationException::withMessages(['product_id'=>'This product is archived. Choose an active product.']);
            }
            $quantity = (int) $data['quantity'];
            if ($quantity < 1 || $quantity > $product->stock_quantity) {
                throw ValidationException::withMessages(['quantity'=>"Only {$product->stock_quantity} units are available. Reduce the quantity and try again."]);
            }
            $customer = empty($data['customer_id']) ? null : Customer::query()->lockForUpdate()->find($data['customer_id']);
            if (!empty($data['customer_id']) && !$customer) {
                throw ValidationException::withMessages(['customer_id'=>'This customer is no longer active. Choose another customer or Walk-in.']);
            }
            $total = Money::cents($product->price) * $quantity;
            if ($total > 9999999999) {
                throw ValidationException::withMessages(['quantity'=>'The total exceeds the transaction limit of ₱99,999,999.99. Reduce the quantity.']);
            }
            $product->decrement('stock_quantity', $quantity);
            return Sale::create([
                'product_id'=>$product->id, 'customer_id'=>$customer?->id, 'sold_by'=>$cashier->id,
                'quantity'=>$quantity, 'unit_price'=>$product->price, 'total_price'=>Money::decimal($total),
                'product_name'=>$product->name, 'customer_name'=>$customer?->full_name,
                'staff_name'=>$cashier->full_name, 'request_token'=>$data['request_token'],
            ]);
        }, 3);
    }
}
