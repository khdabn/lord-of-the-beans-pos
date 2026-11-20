<?php
namespace Tests\Feature;
use App\Models\{Product, User};
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
class ProductDeletionTest extends TestCase
{
    use RefreshDatabase;
    private function staff(): User
    {
        return User::create(['username'=>'delete-tester','full_name'=>'Test Staff','password'=>'Testing-password-123!']);
    }
    private function product(bool $archived=true): Product
    {
        return Product::create(['name'=>'Delete Test Coffee','price'=>'100.00','stock_quantity'=>10,'is_archived'=>$archived]);
    }
    public function test_guests_cannot_permanently_delete_products(): void
    {
        $product=$this->product();
        $this->delete(route('products.permanent-delete',$product))->assertRedirect('/login');
        $this->assertDatabaseHas('products',['id'=>$product->id]);
    }
    public function test_active_products_must_be_archived_first(): void
    {
        $product=$this->product(false);$this->actingAs($this->staff());
        $this->delete(route('products.permanent-delete',$product))->assertSessionHasErrors('product');
        $this->assertDatabaseHas('products',['id'=>$product->id,'is_archived'=>false]);
    }
    public function test_unsold_archived_product_is_deleted_and_its_upload_is_removed(): void
    {
        Storage::fake('local');
        $product=$this->product();$filename=Str::uuid().'.jpg';
        $product->update(['image'=>$filename]);Storage::disk('local')->put('media/'.$filename,'test image');
        $this->actingAs($this->staff())->delete(route('products.permanent-delete',$product))
            ->assertSessionHasNoErrors()->assertRedirect(route('products.index',['status'=>'archived']));
        $this->assertDatabaseMissing('products',['id'=>$product->id]);
        Storage::disk('local')->assertMissing('media/'.$filename);
        $this->delete(route('products.permanent-delete',$product->id))->assertNotFound();
    }
    public function test_product_with_sales_is_protected_even_when_request_is_sent_directly(): void
    {
        $staff=$this->staff();$product=$this->product(false);
        $sale=app(SaleService::class)->record(['product_id'=>$product->id,'quantity'=>1,'request_token'=>Str::uuid()->toString()],$staff);
        $product->update(['is_archived'=>true]);$this->actingAs($staff);
        $this->get(route('products.index',['status'=>'archived']))->assertSee('deletion protected')->assertDontSee('Delete permanently');
        $this->delete(route('products.permanent-delete',$product))->assertSessionHasErrors('product');
        $this->assertDatabaseHas('products',['id'=>$product->id,'stock_quantity'=>9]);
        $this->assertDatabaseHas('sales',['id'=>$sale->id]);
        $this->get(route('sales.show',$sale))->assertOk()->assertSee($sale->reference);
    }
    public function test_archive_and_restore_still_work_and_button_only_appears_for_archived_unsold_items(): void
    {
        $product=$this->product(false);$this->actingAs($this->staff());
        $this->get(route('products.index'))->assertDontSee('Delete permanently');
        $this->delete(route('products.destroy',$product))->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products',['id'=>$product->id,'is_archived'=>true]);
        $this->get(route('products.index',['status'=>'archived']))->assertOk()->assertSee('Delete permanently');
        $this->patch(route('products.restore',$product))->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products',['id'=>$product->id,'is_archived'=>false]);
    }
}
