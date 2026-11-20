<?php
namespace Tests\Feature;
use App\Models\{User, Product, Sale};
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
class CafeUpgradeTest extends TestCase
{
    use RefreshDatabase;
    public function test_cafe_upgrade_keeps_password_sales_and_existing_stock_and_can_be_repeated(): void
    {
        $user=User::create(['username'=>'buen','full_name'=>'Original Staff','password'=>'Testing-password-123!']);
        $hash=$user->password;
        $product=Product::create(['name'=>'Sandstone ceramic mug','price'=>'380.00','stock_quantity'=>10,'image'=>'demo-mug.svg']);
        $sale=app(SaleService::class)->record(['product_id'=>$product->id,'quantity'=>1,'request_token'=>Str::uuid()->toString()],$user);
        $this->artisan('soko:cafe-upgrade')->assertSuccessful();
        $this->assertSame('group4',$user->fresh()->username);
        $this->assertSame($hash,$user->fresh()->password);
        $this->assertTrue($product->fresh()->is_archived);
        $this->assertSame(9,$product->fresh()->stock_quantity);
        $this->assertSame('Original Staff',$sale->fresh()->staff_name);
        $this->assertSame('Sandstone ceramic mug',$sale->fresh()->product_name);
        $latte=Product::where('name','Spanish Latte')->firstOrFail();
        $latte->update(['stock_quantity'=>7]);
        $this->artisan('soko:cafe-upgrade')->assertSuccessful();
        $this->assertSame(7,$latte->fresh()->stock_quantity);
        $this->assertDatabaseCount('products',7);
        $this->assertDatabaseCount('sales',1);
        $this->actingAs($user->fresh())->get('/group')->assertOk()
            ->assertSee('Butad, Carlos Joaquin A.')->assertSee('Cafugauan, Mc Kenrick L.')
            ->assertSee('Buen, Angelo P.')->assertSee('Orlanda, Mark John M.');
    }
}
