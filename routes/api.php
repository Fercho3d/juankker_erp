<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/* ==================== CRM ====================
 | Token: php artisan crm:token tu@correo.com
 | Uso:   curl -H "Authorization: Bearer <token>" -H "Accept: application/json" \
 |             https://tu-erp.test/api/crm/resumen
 */
Route::middleware(['auth:sanctum', 'acceso:crm'])->prefix('crm')->name('api.crm.')->group(function () {
    $crm = \App\Http\Controllers\Api\CrmApiController::class;

    Route::get('resumen', [$crm, 'resumen'])->name('resumen');
    Route::get('etapas', [$crm, 'etapas'])->name('etapas');
    Route::get('pendientes', [$crm, 'pendientes'])->name('pendientes');

    Route::get('leads', [$crm, 'index'])->name('leads.index');
    Route::post('leads', [$crm, 'store'])->name('leads.store');
    Route::post('leads/importar', [$crm, 'importar'])->name('leads.importar');
    Route::get('leads/{lead}', [$crm, 'show'])->whereNumber('lead')->name('leads.show');
    Route::put('leads/{lead}', [$crm, 'update'])->whereNumber('lead')->name('leads.update');
    Route::post('leads/{lead}/mover', [$crm, 'mover'])->whereNumber('lead')->name('leads.mover');
    Route::post('leads/{lead}/actividades', [$crm, 'actividad'])->whereNumber('lead')->name('leads.actividad');
});
