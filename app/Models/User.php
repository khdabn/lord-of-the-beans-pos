<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
class User extends Authenticatable
{
    use SoftDeletes;
    protected $fillable = ['username', 'full_name', 'password', 'avatar'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array { return ['password' => 'hashed']; }
    public function sales(): HasMany { return $this->hasMany(Sale::class, 'sold_by'); }
    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar ? route('media', ['filename' => $this->avatar]) : asset('images/cafe-avatar.svg');
    }
}
