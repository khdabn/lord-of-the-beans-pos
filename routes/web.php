<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController, DashboardController, ProductController, CustomerController, StaffController, SaleController, MediaController};
Route::middleware('guest')->group(function () {
    Route::get('/login',[AuthController::class,'create'])->name('login');
    Route::post('/login',[AuthController::class,'store'])->middleware('throttle:20,1');
});
Route::middleware(['auth','auth.session'])->group(function () {
    Route::view('/group', 'group')->name('group');
    Route::get('/',DashboardController::class)->name('dashboard');
    Route::post('/logout',[AuthController::class,'destroy'])->name('logout');
    Route::resource('products',ProductController::class)->except('show');
    Route::delete('/products/{product}/permanent', [ProductController::class, 'permanentlyDelete'])->name('products.permanent-delete');
    Route::patch('/products/{product}/restore',[ProductController::class,'restore'])->name('products.restore');
    Route::resource('customers',CustomerController::class)->except('show');
    Route::resource('staff',StaffController::class)->except('show')->parameters(['staff'=>'staff']);
    Route::resource('sales',SaleController::class)->only(['index','create','store','show']);
    Route::get('/media/{filename}',MediaController::class)->name('media');
});
