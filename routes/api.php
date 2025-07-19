<?php

use Illuminate\Support\Facades\Route;
use Kaely\PosCustomer\Controllers\CustomerController;
use Kaely\PosCustomer\Controllers\CustomerAddressController;

/*
|--------------------------------------------------------------------------
| POS Customer API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the pos customer package.
| These routes are loaded by the PosCustomerServiceProvider.
|
*/

// Clientes
Route::prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::put('/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::post('/{id}/restore', [CustomerController::class, 'restore'])->name('customers.restore');
    
    // Acciones específicas de clientes
    Route::post('/{customer}/activate', [CustomerController::class, 'activate'])->name('customers.activate');
    Route::post('/{customer}/deactivate', [CustomerController::class, 'deactivate'])->name('customers.deactivate');
    
    // Rutas específicas
    Route::get('/rfc/{rfc}', [CustomerController::class, 'byRfc'])->name('customers.by-rfc');
    Route::get('/email/{email}', [CustomerController::class, 'byEmail'])->name('customers.by-email');
    Route::get('/group/{group}', [CustomerController::class, 'byGroup'])->name('customers.by-group');
    Route::get('/with-credit', [CustomerController::class, 'withCredit'])->name('customers.with-credit');
    Route::get('/with-points', [CustomerController::class, 'withPoints'])->name('customers.with-points');
    Route::get('/last-purchase/{date}', [CustomerController::class, 'byLastPurchaseDate'])->name('customers.by-last-purchase');
    Route::get('/purchased-after/{date}', [CustomerController::class, 'purchasedAfter'])->name('customers.purchased-after');
    Route::get('/purchased-before/{date}', [CustomerController::class, 'purchasedBefore'])->name('customers.purchased-before');
    Route::post('/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::get('/statistics', [CustomerController::class, 'statistics'])->name('customers.statistics');
});

// Direcciones de clientes
Route::prefix('customers/{customer}/addresses')->group(function () {
    Route::get('/', [CustomerAddressController::class, 'index'])->name('customer.addresses.index');
    Route::post('/', [CustomerAddressController::class, 'store'])->name('customer.addresses.store');
    Route::get('/{address}', [CustomerAddressController::class, 'show'])->name('customer.addresses.show');
    Route::put('/{address}', [CustomerAddressController::class, 'update'])->name('customer.addresses.update');
    Route::delete('/{address}', [CustomerAddressController::class, 'destroy'])->name('customer.addresses.destroy');
    Route::post('/{id}/restore', [CustomerAddressController::class, 'restore'])->name('customer.addresses.restore');
    
    // Rutas específicas
    Route::get('/billing', [CustomerAddressController::class, 'billing'])->name('customer.addresses.billing');
    Route::get('/shipping', [CustomerAddressController::class, 'shipping'])->name('customer.addresses.shipping');
    Route::get('/default-billing', [CustomerAddressController::class, 'defaultBilling'])->name('customer.addresses.default-billing');
    Route::get('/default-shipping', [CustomerAddressController::class, 'defaultShipping'])->name('customer.addresses.default-shipping');
    Route::post('/search', [CustomerAddressController::class, 'search'])->name('customer.addresses.search');
    Route::get('/statistics', [CustomerAddressController::class, 'statistics'])->name('customer.addresses.statistics');
});

// Rutas de utilidad
Route::prefix('utils')->group(function () {
    Route::get('/customer-groups', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'general' => 'General',
                'vip' => 'VIP',
                'wholesale' => 'Mayoreo',
                'retail' => 'Menudeo',
                'corporate' => 'Corporativo',
                'government' => 'Gobierno',
                'educational' => 'Educativo',
                'healthcare' => 'Salud',
            ],
        ]);
    })->name('utils.customer-groups');

    Route::get('/countries', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'MX' => 'México',
                'US' => 'Estados Unidos',
                'CA' => 'Canadá',
                'ES' => 'España',
                'AR' => 'Argentina',
                'BR' => 'Brasil',
                'CL' => 'Chile',
                'CO' => 'Colombia',
                'PE' => 'Perú',
                'VE' => 'Venezuela',
            ],
        ]);
    })->name('utils.countries');

    Route::get('/mexican-states', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'AGU' => 'Aguascalientes',
                'BCN' => 'Baja California',
                'BCS' => 'Baja California Sur',
                'CAM' => 'Campeche',
                'CHP' => 'Chiapas',
                'CHH' => 'Chihuahua',
                'COA' => 'Coahuila',
                'COL' => 'Colima',
                'CMX' => 'Ciudad de México',
                'DUR' => 'Durango',
                'GUA' => 'Guanajuato',
                'GRO' => 'Guerrero',
                'HID' => 'Hidalgo',
                'JAL' => 'Jalisco',
                'MEX' => 'México',
                'MIC' => 'Michoacán',
                'MOR' => 'Morelos',
                'NAY' => 'Nayarit',
                'NLE' => 'Nuevo León',
                'OAX' => 'Oaxaca',
                'PUE' => 'Puebla',
                'QUE' => 'Querétaro',
                'ROO' => 'Quintana Roo',
                'SLP' => 'San Luis Potosí',
                'SIN' => 'Sinaloa',
                'SON' => 'Sonora',
                'TAB' => 'Tabasco',
                'TAM' => 'Tamaulipas',
                'TLA' => 'Tlaxcala',
                'VER' => 'Veracruz',
                'YUC' => 'Yucatán',
                'ZAC' => 'Zacatecas',
            ],
        ]);
    })->name('utils.mexican-states');

    Route::get('/address-types', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'billing' => 'Facturación',
                'shipping' => 'Envío',
            ],
        ]);
    })->name('utils.address-types');

    Route::get('/point-types', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'earned' => 'Ganados',
                'redeemed' => 'Canjeados',
                'expired' => 'Expirados',
                'adjusted' => 'Ajustados',
            ],
        ]);
    })->name('utils.point-types');
}); 