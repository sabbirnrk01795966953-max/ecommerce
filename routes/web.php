<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\SubcategoryController as AdminSubcategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MetaEventController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class,'index'])->name('home');
Route::get('/shop', [HomeController::class,'shop'])->name('shop');
Route::get('/product/{slug}', [ProductController::class,'show'])->name('product.show');
Route::get('/subcategory/{slug}', [ProductController::class,'subcategory'])->name('subcategory.show');
Route::get('/cart', [CartController::class,'index'])->name('cart');
Route::post('/cart/add/{product}', [CartController::class,'add'])->name('cart.add');
Route::patch('/cart/update/{product}', [CartController::class,'update'])->name('cart.update');
Route::delete('/cart/remove/{product}', [CartController::class,'remove'])->name('cart.remove');
Route::get('/checkout', [CheckoutController::class,'show'])->name('checkout');
Route::get('/checkout/quick', [CheckoutController::class,'quick'])->name('checkout.quick');
Route::post('/checkout', [CheckoutController::class,'store'])->name('checkout.store');
Route::get('/order/success/{invoiceId}', [CheckoutController::class,'success'])->name('order.success');
Route::post('/meta/event', [MetaEventController::class,'store'])->name('meta.event');
Route::get('/meta/catalog.csv', [CatalogController::class,'csv'])->name('meta.catalog');

Route::middleware('guest')->group(function(){
    Route::get('/admin/login',[AdminAuthController::class,'create'])->name('admin.login');
    Route::post('/admin/login',[AdminAuthController::class,'store'])->name('admin.login.store');
});
Route::prefix('admin')->name('admin.')->middleware(['auth','admin'])->group(function(){
    Route::post('/logout',[AdminAuthController::class,'destroy'])->name('logout');
    Route::get('/',[AdminDashboardController::class,'index'])->name('dashboard');
    Route::resource('categories',AdminCategoryController::class)->except('show');
    Route::resource('subcategories',AdminSubcategoryController::class)->except('show');
    Route::post('/products/editor-media',[AdminProductController::class,'uploadEditorMedia'])->name('products.editor-media');
    Route::resource('products',AdminProductController::class)->except('show');
    Route::delete('/products/{product}/images/{image}',[AdminProductController::class,'deleteImage'])->name('products.images.destroy');
    Route::get('/orders',[AdminOrderController::class,'index'])->name('orders.index');
    Route::patch('/orders/bulk-status',[AdminOrderController::class,'bulkStatus'])->name('orders.bulk-status');
    Route::get('/orders/{order}',[AdminOrderController::class,'show'])->name('orders.show');
    Route::patch('/orders/{order}',[AdminOrderController::class,'update'])->name('orders.update');
    Route::delete('/orders/{order}',[AdminOrderController::class,'destroy'])->name('orders.destroy');
    Route::post('/orders/{order}/resend-oms',[AdminOrderController::class,'resend'])->name('orders.resend-oms');
    Route::get('/settings',[AdminSettingsController::class,'edit'])->name('settings.edit');
    Route::post('/settings/save-oms',[AdminSettingsController::class,'saveOms'])->name('settings.save-oms');
    Route::post('/settings/test-oms',[AdminSettingsController::class,'testOms'])->name('settings.test-oms');
    Route::put('/settings',[AdminSettingsController::class,'update'])->name('settings.update');
});
