<?php
namespace Tests\Feature;
use App\Models\{User, Product, Customer, Sale};
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Hash, Storage, RateLimiter, Event};
use Illuminate\Support\Str;
use Tests\TestCase;
class PosWorkflowTest extends TestCase
{
    use RefreshDatabase;
    private function staff(string $username='tester'): User
    {
        return User::create(['username'=>$username,'full_name'=>'Test Staff','password'=>'Testing-password-123!']);
    }
    private function product(array $extra=[]): Product
    {
        return Product::create(array_merge(['name'=>'Test Mug','price'=>'19.95','stock_quantity'=>10],$extra));
    }
    private function customer(): Customer
    {
        return Customer::create(['full_name'=>'Test Customer','email'=>'customer@example.com','phone'=>'09171234567']);
    }
    private function payload(Product $product, array $extra=[]): array
    {
        return array_merge(['product_id'=>$product->id,'quantity'=>2,'request_token'=>Str::uuid()->toString()],$extra);
    }
    public function test_all_management_pages_and_writes_require_login(): void
    {
        $product=$this->product(); $customer=$this->customer(); $staff=$this->staff();
        foreach (['/','/products','/products/create',"/products/{$product->id}/edit",'/customers','/customers/create',"/customers/{$customer->id}/edit",'/staff','/staff/create',"/staff/{$staff->id}/edit",'/sales','/sales/create','/sales/1','/media/'.Str::uuid().'.jpg'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
        foreach (['products','customers','staff','sales'] as $resource) $this->post('/'.$resource,[])->assertRedirect('/login');
        foreach (['products'=>$product->id,'customers'=>$customer->id,'staff'=>$staff->id] as $resource=>$id) {
            $this->put("/$resource/$id",[])->assertRedirect('/login');
            $this->delete("/$resource/$id")->assertRedirect('/login');
        }
        $this->patch("/products/{$product->id}/restore")->assertRedirect('/login');
    }
    public function test_valid_login_logout_and_invalid_password(): void
    {
        $staff=$this->staff();
        $this->post('/login',['username'=>'tester','password'=>'wrong'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->post('/login',['username'=>'tester','password'=>'Testing-password-123!'])->assertRedirect('/');
        $this->assertAuthenticatedAs($staff);
        $this->post('/logout')->assertRedirect('/login'); $this->assertGuest();
    }
    public function test_login_attempts_are_throttled(): void
    {
        $this->staff('throttle'); RateLimiter::clear('login:throttle|127.0.0.1');
        for ($i=0;$i<5;$i++) $this->post('/login',['username'=>'throttle','password'=>'incorrect']);
        $this->post('/login',['username'=>'throttle','password'=>'Testing-password-123!'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }
    public function test_archived_staff_cannot_log_in(): void
    {
        $staff=$this->staff('removed'); $staff->delete();
        $this->post('/login',['username'=>'removed','password'=>'Testing-password-123!'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }
    public function test_every_screen_renders_for_authenticated_staff(): void
    {
        $staff=$this->staff();$product=$this->product();$customer=$this->customer();
        $this->actingAs($staff);
        foreach (['/','/products','/products/create',"/products/{$product->id}/edit",'/customers','/customers/create',"/customers/{$customer->id}/edit",'/staff','/staff/create',"/staff/{$staff->id}/edit",'/sales','/sales/create','/sales?to=2030-01-01'] as $path) $this->get($path)->assertOk();
        $sale=app(SaleService::class)->record($this->payload($product),$staff);
        $this->get('/sales/'.$sale->id)->assertOk()->assertSee($sale->reference);
    }
    public function test_products_can_be_created_updated_archived_and_restored(): void
    {
        $this->actingAs($this->staff());
        $this->post('/products',['name'=>'New Product','price'=>'125.50','stock_quantity'=>8])->assertSessionHasNoErrors()->assertRedirect('/products');
        $product=Product::firstOrFail();
        $this->put('/products/'.$product->id,['name'=>'Updated Product','price'=>'150.25','stock_quantity'=>5,'stock_version'=>8])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products',['id'=>$product->id,'name'=>'Updated Product','stock_quantity'=>5]);
        $this->delete('/products/'.$product->id)->assertRedirect('/products');
        $this->assertTrue($product->fresh()->is_archived);
        $this->get('/products')->assertDontSee('Updated Product');
        $this->get('/products?status=archived')->assertSee('Updated Product');
        $this->patch('/products/'.$product->id.'/restore')->assertRedirect('/products');
        $this->assertFalse($product->fresh()->is_archived);
    }
    public function test_product_validation_rejects_invalid_price_stock_and_names(): void
    {
        $this->actingAs($this->staff());
        $this->post('/products',['name'=>'','price'=>'-1.55','stock_quantity'=>-1])->assertSessionHasErrors(['name','price','stock_quantity']);
        $this->post('/products',['name'=>'Valid','price'=>'3.999','stock_quantity'=>1])->assertSessionHasErrors('price');
        $this->post('/products',['name'=>'Valid','price'=>'1e2','stock_quantity'=>1])->assertSessionHasErrors('price');
        $this->assertDatabaseCount('products',0);
    }
    public function test_stale_stock_edit_cannot_overwrite_a_completed_sale(): void
    {
        $staff=$this->staff();$product=$this->product();$this->actingAs($staff);
        app(SaleService::class)->record($this->payload($product),$staff);
        $this->put('/products/'.$product->id,['name'=>$product->name,'price'=>$product->price,'stock_quantity'=>10,'stock_version'=>10])->assertSessionHasErrors('stock_quantity');
        $this->assertSame(8,$product->fresh()->stock_quantity);
    }
    public function test_product_image_is_reencoded_and_replaced_safely(): void
    {
        Storage::fake('local'); $this->actingAs($this->staff());
        $this->post('/products',['name'=>'Photo Product','price'=>'10.00','stock_quantity'=>4,'image'=>UploadedFile::fake()->image('photo.png',900,600)])->assertSessionHasNoErrors();
        $product=Product::firstOrFail();$first=$product->image;
        Storage::disk('local')->assertExists('media/'.$first);
        $size=getimagesize(Storage::disk('local')->path('media/'.$first));
        $this->assertSame([720,720],[$size[0],$size[1]]);$this->assertSame('image/jpeg',$size['mime']);
        $this->get('/media/'.$first)->assertOk()->assertHeader('Content-Type','image/jpeg');
        $this->put('/products/'.$product->id,['name'=>'Photo Product','price'=>'10.00','stock_quantity'=>4,'stock_version'=>4,'image'=>UploadedFile::fake()->image('new.jpg')])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing('media/'.$first);
        Storage::disk('local')->assertExists('media/'.$product->fresh()->image);
    }
    public function test_image_upload_rejects_svg_code_and_oversized_files(): void
    {
        $this->actingAs($this->staff());
        foreach ([UploadedFile::fake()->create('code.php',1,'application/x-php'),UploadedFile::fake()->create('icon.svg',1,'image/svg+xml'),UploadedFile::fake()->image('large.jpg')->size(2049)] as $file) {
            $this->post('/products',['name'=>'Unsafe','price'=>'9.99','stock_quantity'=>1,'image'=>$file])->assertSessionHasErrors('image');
        }
        $this->assertDatabaseCount('products',0);
    }
    public function test_customer_crud_and_validation(): void
    {
        $this->actingAs($this->staff());
        $this->post('/customers',['full_name'=>'','email'=>'wrong'])->assertSessionHasErrors(['full_name','email']);
        $this->post('/customers',['full_name'=>'New Customer','email'=>'new@example.com','phone'=>'+63 917 000 1234'])->assertSessionHasNoErrors();
        $customer=Customer::firstOrFail();
        $this->put('/customers/'.$customer->id,['full_name'=>'Changed Name','email'=>'new@example.com','phone'=>null])->assertSessionHasNoErrors();
        $this->get('/customers')->assertSee('Changed Name');
        $this->delete('/customers/'.$customer->id)->assertRedirect('/customers');
        $this->assertSoftDeleted('customers',['id'=>$customer->id]);
    }
    public function test_staff_creation_hashes_password_prepares_avatar_and_checks_unique_username(): void
    {
        Storage::fake('local'); $this->actingAs($this->staff());
        $data=['username'=>'cashier','full_name'=>'New Cashier','password'=>'New-password-123!','password_confirmation'=>'New-password-123!'];
        $this->post('/staff',$data+['avatar'=>UploadedFile::fake()->image('portrait.png',400,600)])->assertSessionHasNoErrors()->assertRedirect('/staff');
        $staff=User::where('username','cashier')->firstOrFail();
        $this->assertTrue(Hash::check('New-password-123!',$staff->password));
        $this->assertNotSame('New-password-123!',$staff->password);
        $size=getimagesize(Storage::disk('local')->path('media/'.$staff->avatar));
        $this->assertSame([256,256],[$size[0],$size[1]]);
        $this->post('/staff',$data)->assertSessionHasErrors('username');
    }
    public function test_staff_update_retains_blank_password_and_self_removal_is_blocked(): void
    {
        $staff=$this->staff();$hash=$staff->password;$this->actingAs($staff);
        $this->put('/staff/'.$staff->id,['username'=>'tester','full_name'=>'Updated Staff','password'=>'','password_confirmation'=>''])->assertSessionHasNoErrors();
        $this->assertSame($hash,$staff->fresh()->password);
        $this->delete('/staff/'.$staff->id)->assertSessionHasErrors('staff');
        $this->assertDatabaseHas('users',['id'=>$staff->id,'deleted_at'=>null]);
    }
    public function test_current_staff_can_change_password_without_losing_their_session(): void
    {
        $staff=$this->staff();$this->actingAs($staff);
        $this->get('/')->assertOk();
        $this->put('/staff/'.$staff->id,['username'=>'tester','full_name'=>'Test Staff','password'=>'Changed-password-123!','password_confirmation'=>'Changed-password-123!'])->assertSessionHasNoErrors();
        $this->get('/')->assertOk();
        $this->assertTrue(Hash::check('Changed-password-123!',$staff->fresh()->password));
    }
    public function test_staff_password_confirmation_and_minimum_length(): void
    {
        $this->actingAs($this->staff());
        $this->post('/staff',['username'=>'another','full_name'=>'Another','password'=>'short','password_confirmation'=>'different'])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users',1);
    }
    public function test_walk_in_sale_uses_database_price_and_authenticated_staff(): void
    {
        $staff=$this->staff();$product=$this->product();$this->actingAs($staff);
        $this->post('/sales',$this->payload($product,['total_price'=>'0.01','sold_by'=>999]))->assertSessionHasNoErrors()->assertRedirect();
        $sale=Sale::firstOrFail();
        $this->assertSame('39.90',$sale->total_price);$this->assertNull($sale->customer_id);
        $this->assertEquals($staff->id,$sale->sold_by);$this->assertSame(8,$product->fresh()->stock_quantity);
    }
    public function test_customer_sale_snapshots_names_and_preserves_history_after_deletion(): void
    {
        $staff=$this->staff();$product=$this->product();$customer=$this->customer();$this->actingAs($staff);
        $this->post('/sales',$this->payload($product,['customer_id'=>$customer->id]))->assertSessionHasNoErrors();
        $sale=Sale::firstOrFail();$product->update(['name'=>'Renamed','price'=>'99.00']);$customer->update(['full_name'=>'New Name']);
        $customer->delete();$staff->delete();
        $this->assertSame('Test Mug',$sale->product_name);$this->assertSame('Test Customer',$sale->customer_name);
        $this->assertSame('Test Staff',$sale->staff_name);$this->assertSame('19.95',$sale->unit_price);
        $this->assertNotNull($sale->customer);$this->assertNotNull($sale->staff);
        $this->assertDatabaseCount('sales',1);
    }
    public function test_insufficient_stock_leaves_both_inventory_and_sales_unchanged(): void
    {
        $product=$this->product(['stock_quantity'=>2]);$this->actingAs($this->staff());
        $this->post('/sales',$this->payload($product,['quantity'=>3]))->assertSessionHasErrors('quantity');
        $this->assertSame(2,$product->fresh()->stock_quantity);$this->assertDatabaseCount('sales',0);
    }
    public function test_zero_negative_and_fractional_quantities_are_rejected(): void
    {
        $product=$this->product();$this->actingAs($this->staff());
        foreach ([0,-1,1.5] as $quantity) $this->post('/sales',$this->payload($product,['quantity'=>$quantity]))->assertSessionHasErrors('quantity');
        $this->assertSame(10,$product->fresh()->stock_quantity);$this->assertDatabaseCount('sales',0);
    }
    public function test_archived_products_and_removed_customers_cannot_be_sold(): void
    {
        $staff=$this->staff();$this->actingAs($staff);$product=$this->product(['is_archived'=>true]);
        $this->post('/sales',$this->payload($product))->assertSessionHasErrors('product_id');
        $product->update(['is_archived'=>false]);$customer=$this->customer();$customer->delete();
        $this->post('/sales',$this->payload($product,['customer_id'=>$customer->id]))->assertSessionHasErrors('customer_id');
        $this->assertSame(10,$product->fresh()->stock_quantity);$this->assertDatabaseCount('sales',0);
    }
    public function test_duplicate_submission_creates_only_one_sale_and_stock_deduction(): void
    {
        $product=$this->product();$this->actingAs($this->staff());$data=$this->payload($product);
        $first=$this->post('/sales',$data)->assertSessionHasNoErrors();
        $second=$this->post('/sales',$data)->assertSessionHasNoErrors();
        $this->assertSame($first->headers->get('Location'),$second->headers->get('Location'));
        $this->assertDatabaseCount('sales',1);$this->assertSame(8,$product->fresh()->stock_quantity);
    }
    public function test_second_sale_cannot_use_stock_consumed_by_the_first(): void
    {
        $product=$this->product(['stock_quantity'=>3]);$this->actingAs($this->staff());
        $this->post('/sales',$this->payload($product))->assertSessionHasNoErrors();
        $this->post('/sales',$this->payload($product))->assertSessionHasErrors('quantity');
        $this->assertSame(1,$product->fresh()->stock_quantity);$this->assertDatabaseCount('sales',1);
    }
    public function test_exact_stock_sale_succeeds_and_total_overflow_is_rejected(): void
    {
        $product=$this->product(['stock_quantity'=>2]);$this->actingAs($this->staff());
        $this->post('/sales',$this->payload($product))->assertSessionHasNoErrors();
        $this->assertSame(0,$product->fresh()->stock_quantity);
        $expensive=$this->product(['price'=>'99999999.99','stock_quantity'=>2]);
        $this->post('/sales',$this->payload($expensive))->assertSessionHasErrors('quantity');
        $this->assertSame(2,$expensive->fresh()->stock_quantity);$this->assertDatabaseCount('sales',1);
    }
    public function test_sale_insert_failure_rolls_back_inventory(): void
    {
        $staff=$this->staff();$product=$this->product();
        $event='eloquent.creating: '.Sale::class;
        Event::listen($event,fn () => throw new \RuntimeException('Simulated storage failure'));
        try {
            app(SaleService::class)->record($this->payload($product),$staff);
            $this->fail('Expected the simulated failure.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Simulated storage failure',$error->getMessage());
        } finally { Event::forget($event); }
        $this->assertSame(10,$product->fresh()->stock_quantity);$this->assertDatabaseCount('sales',0);
    }
    public function test_search_and_date_filters_and_html_escaping(): void
    {
        $staff=$this->staff();$product=$this->product(['name'=>'<script>alert(1)</script>']);$this->actingAs($staff);
        $this->get('/products')->assertDontSee('<script>alert(1)</script>',false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false);
        $sale=app(SaleService::class)->record($this->payload($product),$staff);
        $sale->forceFill(['created_at'=>'2026-01-05 12:00:00'])->save();
        $this->get('/sales?from=2026-01-01&to=2026-01-31')->assertSee($sale->reference);
        $this->get('/sales?from=2026-02-01&to=2026-02-28')->assertDontSee($sale->reference);
        $this->get('/sales?from=2026-02-01&to=2026-01-01')->assertSessionHasErrors('to');
        $this->get('/products?q=missing')->assertDontSee('&lt;script&gt;',false);
    }
    public function test_staff_removal_preserves_sales_and_blocks_old_edit_url(): void
    {
        $manager=$this->staff();$cashier=$this->staff('cashier');$product=$this->product();
        $sale=app(SaleService::class)->record($this->payload($product),$cashier);
        $this->actingAs($manager)->delete('/staff/'.$cashier->id)->assertRedirect('/staff');
        $this->assertSoftDeleted('users',['id'=>$cashier->id]);
        $this->get('/staff/'.$cashier->id.'/edit')->assertNotFound();
        $this->get('/sales/'.$sale->id)->assertOk()->assertSee($sale->reference);
    }
    public function test_pagination_renders_for_more_than_one_page(): void
    {
        $this->actingAs($this->staff());
        for($i=0;$i<14;$i++) $this->product(['name'=>'Item '.str_pad($i,2,'0',STR_PAD_LEFT)]);
        $this->get('/products')->assertOk()->assertSee('Next');
        $this->get('/products?page=2')->assertOk()->assertSee('Previous');
    }
}
