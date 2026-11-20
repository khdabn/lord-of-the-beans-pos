<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('full_name', 100);
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_archived')->default(false)->index();
            $table->timestamps();
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 100);
            $table->string('email', 100);
            $table->string('phone', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('sold_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('total_price', 10, 2);
            // Snapshots preserve what was sold, at which price, and by whom.
            $table->decimal('unit_price', 10, 2);
            $table->string('product_name', 100);
            $table->string('customer_name', 100)->nullable();
            $table->string('staff_name', 100);
            $table->uuid('request_token')->unique();
            $table->timestamps();
            $table->index('created_at');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sales'); Schema::dropIfExists('customers');
        Schema::dropIfExists('products'); Schema::dropIfExists('users');
    }
};
