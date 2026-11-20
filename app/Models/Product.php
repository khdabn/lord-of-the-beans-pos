<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Product extends Model
{
    protected $fillable = ['name', 'price', 'stock_quantity', 'image', 'is_archived'];
    protected function casts(): array { return ['price'=>'decimal:2', 'stock_quantity'=>'integer', 'is_archived'=>'boolean']; }
    public function scopeActive(Builder $query): Builder { return $query->where('is_archived', false); }
    public function sales(): HasMany { return $this->hasMany(Sale::class); }
    public function getImageUrlAttribute(): string
    {
        if (!$this->image) return asset('images/cafe-product.svg');
        if (preg_match('/^(?:demo|cafe)-[a-z]+\.svg$/', $this->image)) return asset('images/'.$this->image);
        return route('media', ['filename'=>$this->image]);
    }
}
