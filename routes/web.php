<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductAttributeController;
use App\Http\Controllers\ProductAttributeValueController;

Route::get('/', [WelcomeController::class, 'index']);

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Clients & Suppliers
    Route::resource('clientes', ClientController::class)->except(['show'])->parameters(['clientes' => 'cliente']);
    Route::resource('proveedores', SupplierController::class)->except(['show'])->parameters(['proveedores' => 'proveedore']);

    // Product Categories & Brands
    Route::resource('categorias', CategoryController::class)->except(['show'])->parameters(['categorias' => 'categoria']);
    Route::resource('marcas', BrandController::class)->except(['show'])->parameters(['marcas' => 'marca']);

    // Products
    Route::resource('productos', ProductController::class)->except(['show'])->parameters(['productos' => 'producto']);

    // Product Attributes
    Route::resource('atributos-producto', ProductAttributeController::class)->except(['show'])->parameters(['atributos-producto' => 'atributo']);

    // Attribute Values (nested under attributes)
    Route::prefix('atributos-producto/{atributo}')->name('atributos-producto.')->group(function () {
        Route::get('valores', [ProductAttributeValueController::class, 'index'])->name('valores.index');
        Route::post('valores', [ProductAttributeValueController::class, 'store'])->name('valores.store');
        Route::put('valores/{valor}', [ProductAttributeValueController::class, 'update'])->name('valores.update');
        Route::delete('valores/{valor}', [ProductAttributeValueController::class, 'destroy'])->name('valores.destroy');
    });

    // POS Routes
    Route::get('/pos', [App\Http\Controllers\POSController::class, 'index'])->name('pos.index');
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('search', [App\Http\Controllers\POSController::class, 'search'])->name('search');
        Route::get('cart', [App\Http\Controllers\POSController::class, 'getCart'])->name('cart');
        Route::post('cart/add', [App\Http\Controllers\POSController::class, 'addToCart'])->name('add');
        Route::put('cart/update/{item}', [App\Http\Controllers\POSController::class, 'updateItem'])->name('update');
        Route::delete('cart/remove/{item}', [App\Http\Controllers\POSController::class, 'removeItem'])->name('remove');
        Route::post('cart/client', [App\Http\Controllers\POSController::class, 'assignClient'])->name('client');
        Route::post('cart/cancel', [App\Http\Controllers\POSController::class, 'cancelSale'])->name('cancel');
        Route::post('checkout', [App\Http\Controllers\POSController::class, 'checkout'])->name('checkout');
    });

    // Sales History
    Route::get('/ventas', [App\Http\Controllers\SaleController::class, 'index'])->name('sales.index');
    Route::get('/ventas/{sale}', [App\Http\Controllers\SaleController::class, 'show'])->name('sales.show');
    Route::get('/ventas/{sale}/pdf', [App\Http\Controllers\SaleController::class, 'downloadPdf'])->name('sales.pdf');

    // Inventory
    Route::resource('inventario', App\Http\Controllers\InventoryController::class)->only(['index', 'update']);
});
