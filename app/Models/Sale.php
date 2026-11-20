<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Sale extends Model
{
    protected $fillable = ['product_id','customer_id','sold_by','quantity','total_price','unit_price','product_name','customer_name','staff_name','request_token'];
    protected function casts(): array { return ['sold_by'=>'integer','product_id'=>'integer','customer_id'=>'integer','quantity'=>'integer','total_price'=>'decimal:2','unit_price'=>'decimal:2']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class)->withTrashed(); }
    public function staff(): BelongsTo { return $this->belongsTo(User::class, 'sold_by')->withTrashed(); }
    public function getReferenceAttribute(): string { return 'SK-'.str_pad((string)$this->id, 6, '0', STR_PAD_LEFT); }
}
