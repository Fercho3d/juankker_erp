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
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\BetaRequestController;
use App\Http\Controllers\SuperadminController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\CrmActivityController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;

Route::get('/', [WelcomeController::class, 'index']);

// Tema e idioma: abiertos también a visitantes, que los guardan en cookie.
Route::put('/preferencias/tema', [PreferenceController::class, 'theme'])->name('preferences.theme');
Route::put('/preferencias/idioma', [PreferenceController::class, 'locale'])->name('preferences.locale');

// Página pública de planes y solicitud de beta.
Route::get('/precios', [PricingController::class, 'index'])->name('pricing');
Route::post('/beta', [BetaRequestController::class, 'store'])->name('beta.store');

Route::middleware('guest')->group(function () {
    Route::get('/unirse/{token}', [TeamInvitationController::class, 'show'])->name('team.join');
    Route::post('/unirse/{token}', [TeamInvitationController::class, 'accept'])->name('team.join.accept');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/register/verify', [AuthController::class, 'showVerifyForm'])->name('register.verify');
    Route::post('/register/verify', [AuthController::class, 'verifyOtp']);
    Route::post('/register/resend', [AuthController::class, 'resendOtp'])->name('register.resend');
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Recuperación de contraseña.
    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequest'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

    // Invitaciones beta.
    Route::get('/invitacion/{token}', [AuthController::class, 'showInvitation'])->name('invitation.show');
    Route::post('/invitacion/{token}', [AuthController::class, 'acceptInvitation'])->name('invitation.accept');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Suscripción del tenant.
    Route::middleware('acceso:plan')->group(function () {
        Route::get('/suscripcion', [SubscriptionController::class, 'index'])->name('subscription.index');
        Route::get('/suscripcion/upgrade/{plan}', [SubscriptionController::class, 'upgrade'])->name('subscription.upgrade');
        Route::post('/suscripcion/confirmar/{plan}', [SubscriptionController::class, 'confirm'])->name('subscription.confirm');
        Route::post('/suscripcion/cancelar', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    });

    // Equipo: miembros, invitaciones y perfiles de acceso.
    Route::middleware('acceso:equipo')->prefix('equipo')->name('team.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::post('invitaciones', [TeamController::class, 'invite'])->name('invite');
        Route::post('invitaciones/{invitacion}/reenviar', [TeamController::class, 'resend'])->name('resend');
        Route::delete('invitaciones/{invitacion}', [TeamController::class, 'cancelInvitation'])->name('invitation.cancel');
        Route::put('miembros/{miembro}', [TeamController::class, 'updateMember'])->name('member.update');
        Route::post('miembros/{miembro}/desactivar', [TeamController::class, 'deactivate'])->name('member.deactivate');
        Route::post('miembros/{miembro}/activar', [TeamController::class, 'activate'])->name('member.activate');

        Route::get('perfiles/nuevo', [TeamController::class, 'createRole'])->name('role.create');
        Route::post('perfiles', [TeamController::class, 'storeRole'])->name('role.store');
        Route::get('perfiles/{perfil}/editar', [TeamController::class, 'editRole'])->name('role.edit');
        Route::put('perfiles/{perfil}', [TeamController::class, 'updateRole'])->name('role.update');
        Route::delete('perfiles/{perfil}', [TeamController::class, 'destroyRole'])->name('role.destroy');
    });

    // CRM (Premium)
    Route::middleware(['premium:crm', 'acceso:crm'])->prefix('crm')->name('crm.')->group(function () {
        Route::get('/', [CrmController::class, 'pendientes'])->name('pendientes');
        Route::get('tablero', [CrmController::class, 'tablero'])->name('tablero');
        Route::post('leads/{lead}/mover', [CrmController::class, 'mover'])->name('leads.mover');

        Route::get('importar', [LeadController::class, 'importarForm'])->name('leads.importar.form');
        Route::post('importar', [LeadController::class, 'importar'])->name('leads.importar');

        Route::get('leads/nuevo', [LeadController::class, 'create'])->name('leads.create');
        Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
        Route::get('leads/{lead}', [LeadController::class, 'show'])->whereNumber('lead')->name('leads.show');
        Route::get('leads/{lead}/editar', [LeadController::class, 'edit'])->whereNumber('lead')->name('leads.edit');
        Route::put('leads/{lead}', [LeadController::class, 'update'])->whereNumber('lead')->name('leads.update');
        Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->whereNumber('lead')->name('leads.destroy');
        Route::post('leads/{lead}/convertir', [LeadController::class, 'convertir'])->whereNumber('lead')->name('leads.convertir');
        Route::post('leads/{lead}/descartar', [LeadController::class, 'descartar'])->whereNumber('lead')->name('leads.descartar');
        Route::post('leads/{lead}/asignar', [LeadController::class, 'asignar'])->whereNumber('lead')->name('leads.asignar');
        Route::post('asignar', [CrmController::class, 'asignarEnBloque'])->name('leads.asignar-bloque');
        Route::get('papelera', [LeadController::class, 'papelera'])->name('papelera');
        Route::post('papelera/{id}/restaurar', [LeadController::class, 'restaurar'])->whereNumber('id')->name('leads.restaurar');

        Route::post('leads/{lead}/actividades', [CrmActivityController::class, 'store'])->whereNumber('lead')->name('actividades.store');
        Route::post('actividades/{actividad}/completar', [CrmActivityController::class, 'completar'])->name('actividades.completar');
    });

    // Clients & Suppliers
    Route::resource('clientes', ClientController::class)->except(['show'])->parameters(['clientes' => 'cliente'])->middleware('acceso:clientes');
    Route::resource('proveedores', SupplierController::class)->except(['show'])->parameters(['proveedores' => 'proveedore'])->middleware('acceso:proveedores');

    // Catálogo: productos, categorías, marcas y atributos
    Route::middleware('acceso:productos')->group(function () {
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
    }); // fin catálogo

    // POS Routes (Premium)
    Route::middleware('premium')->group(function () {
    Route::middleware('acceso:pos')->group(function () {
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
    }); // fin punto de venta

    // Sales History
    Route::middleware('acceso:ventas')->group(function () {
    Route::get('/ventas', [App\Http\Controllers\SaleController::class, 'index'])->name('sales.index');
    Route::get('/ventas/{sale}', [App\Http\Controllers\SaleController::class, 'show'])->name('sales.show');
    Route::get('/ventas/{sale}/pdf', [App\Http\Controllers\SaleController::class, 'downloadPdf'])->name('sales.pdf');
    }); // fin ventas

    // Inventory
    Route::resource('inventario', App\Http\Controllers\InventoryController::class)->only(['index', 'update'])->middleware('acceso:inventario');
    }); // fin grupo premium (POS, ventas, inventario)
});

/* ==================== SUPERADMIN ==================== */
Route::middleware(['auth', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', [SuperadminController::class, 'dashboard'])->name('dashboard');
    Route::get('/organizaciones', [SuperadminController::class, 'index'])->name('index');
    Route::get('/uso', [SuperadminController::class, 'uso'])->name('uso');
    Route::get('/org/{organization}', [SuperadminController::class, 'show'])->name('show');
    Route::put('/org/{organization}/suscripcion', [SuperadminController::class, 'updateSubscription'])->name('subscription.update');
    Route::post('/org/{organization}/trial', [SuperadminController::class, 'activateTrial'])->name('subscription.trial');
    Route::post('/impersonate/{user}', [SuperadminController::class, 'impersonate'])->name('impersonate');
    Route::post('/stop-impersonating', [SuperadminController::class, 'stopImpersonating'])->name('stop-impersonating');

    // Planes
    Route::get('/planes', [SuperadminController::class, 'planes'])->name('planes');
    Route::post('/planes', [SuperadminController::class, 'storePlan'])->name('planes.store');
    Route::put('/planes/{plan}', [SuperadminController::class, 'updatePlan'])->name('planes.update');
    Route::delete('/planes/{plan}', [SuperadminController::class, 'destroyPlan'])->name('planes.destroy');
    Route::put('/settings', [SuperadminController::class, 'updateSettings'])->name('settings.update');

    // Invitaciones
    Route::get('/invitaciones', [SuperadminController::class, 'invitations'])->name('invitations');
    Route::post('/invitaciones', [SuperadminController::class, 'storeInvitation'])->name('invitations.store');
    Route::post('/invitaciones/{invitation}/reenviar', [SuperadminController::class, 'resendInvitation'])->name('invitations.resend');
    Route::delete('/invitaciones/{invitation}', [SuperadminController::class, 'destroyInvitation'])->name('invitations.destroy');

    // Solicitudes beta
    Route::get('/beta', [SuperadminController::class, 'betaRequests'])->name('beta');
    Route::post('/beta/{betaRequest}/invitar', [SuperadminController::class, 'inviteBeta'])->name('beta.invite');
    Route::post('/beta/{betaRequest}/descartar', [SuperadminController::class, 'dismissBeta'])->name('beta.dismiss');
});
