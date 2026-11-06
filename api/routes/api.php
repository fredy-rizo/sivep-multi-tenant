<?php

use App\Http\Controllers\Api\V1\AnimalController;
use App\Http\Controllers\Api\V1\AnimalEventController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\FarmReportController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
| Autenticación por sesión (Sanctum SPA). Las rutas de negocio requieren:
|   auth:sanctum -> organization -> feature:<código> -> permission:<permiso>
*/

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::get('plans', [BillingController::class, 'plans']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('organizations', [OrganizationController::class, 'index']);
        Route::get('organizations/{organization}', [OrganizationController::class, 'show']);

        Route::middleware('organization')->group(function () {
            // Catálogos de lectura: disponibles para cualquier plan.
            Route::prefix('catalogs')->group(function () {
                Route::get('summary', [CatalogController::class, 'summary']);
                Route::get('species', [CatalogController::class, 'species']);
                Route::get('breeds', [CatalogController::class, 'breeds']);
                Route::get('event-types', [CatalogController::class, 'eventTypes']);
                Route::get('vaccines', [CatalogController::class, 'vaccines']);
                Route::get('lots', [CatalogController::class, 'lots']);
            });

            Route::middleware('feature:livestock.core')->group(function () {
                Route::get('animals', [AnimalController::class, 'index'])->middleware('permission:animals.view');
                Route::post('animals', [AnimalController::class, 'store'])->middleware('permission:animals.create');
                Route::get('animals/{animal}', [AnimalController::class, 'show'])->middleware('permission:animals.view');
                Route::put('animals/{animal}', [AnimalController::class, 'update'])->middleware('permission:animals.update');
                Route::patch('animals/{animal}', [AnimalController::class, 'update'])->middleware('permission:animals.update');
                Route::delete('animals/{animal}', [AnimalController::class, 'destroy'])->middleware('permission:animals.delete');
                Route::get('animals/{animal}/timeline', [AnimalController::class, 'timeline'])->middleware('permission:animals.view');
                Route::get('animals/{animal}/summaries', [AnimalController::class, 'summaries'])->middleware('permission:animals.view');
                Route::post('animals/{animal}/events', [AnimalEventController::class, 'store'])->middleware('permission:events.create');

                Route::get('events', [AnimalEventController::class, 'index'])->middleware('permission:animals.view');
                Route::delete('events/{event}', [AnimalEventController::class, 'destroy'])->middleware('permission:events.delete');

                Route::get('reports/summary', [ReportController::class, 'summary'])->middleware(['feature:livestock.reports', 'permission:reports.view']);
                Route::get('reports/weight-evolution', [ReportController::class, 'weightEvolution'])->middleware(['feature:livestock.reports', 'permission:reports.view']);
                Route::get('reports/upcoming-vaccines', [ReportController::class, 'upcomingVaccines'])->middleware(['feature:livestock.reports', 'permission:reports.view']);
                Route::get('reports/monthly-events', [ReportController::class, 'monthlyEvents'])->middleware(['feature:livestock.reports', 'permission:reports.view']);

                Route::post('catalogs/lots', [CatalogController::class, 'storeLot'])->middleware('permission:catalogs.manage');
                Route::put('catalogs/lots/{lot}', [CatalogController::class, 'updateLot'])->middleware('permission:catalogs.manage');
                Route::delete('catalogs/lots/{lot}', [CatalogController::class, 'destroyLot'])->middleware('permission:catalogs.manage');
            });

            // Núcleo de granja: inventario de productos.
            Route::middleware('feature:inventory.core')->group(function () {
                Route::get('inventory/summary', [InventoryController::class, 'summary'])->middleware('permission:inventory.view');
                Route::get('inventory/movements', [InventoryController::class, 'index'])->middleware('permission:inventory.view');
                Route::post('inventory/movements', [InventoryController::class, 'store'])->middleware('permission:inventory.manage');

                Route::get('products', [ProductController::class, 'index'])->middleware('permission:inventory.view');
                Route::post('products', [ProductController::class, 'store'])->middleware('permission:inventory.manage');
                Route::get('products/{product}', [ProductController::class, 'show'])->middleware('permission:inventory.view');
                Route::put('products/{product}', [ProductController::class, 'update'])->middleware('permission:inventory.manage');
                Route::patch('products/{product}', [ProductController::class, 'update'])->middleware('permission:inventory.manage');
                Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware('permission:inventory.manage');
            });

            // Clientes de la organización.
            Route::middleware('feature:clients.core')->group(function () {
                Route::get('customers', [CustomerController::class, 'index'])->middleware('permission:customers.view');
                Route::get('customers/summary', [CustomerController::class, 'summary'])->middleware('permission:customers.view');
                Route::post('customers', [CustomerController::class, 'store'])->middleware('permission:customers.manage');
                Route::get('customers/{customer}', [CustomerController::class, 'show'])->middleware('permission:customers.view');
                Route::put('customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:customers.manage');
                Route::patch('customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:customers.manage');
                Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->middleware('permission:customers.manage');
            });

            // Ventas y reportes comerciales.
            Route::middleware('feature:sales.core')->group(function () {
                Route::get('sales', [SaleController::class, 'index'])->middleware('permission:sales.view');
                Route::post('sales', [SaleController::class, 'store'])->middleware('permission:sales.create');
                Route::get('sales/{sale}', [SaleController::class, 'show'])->middleware('permission:sales.view');
                Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->middleware('permission:sales.cancel');

                Route::get('reports/farm', [FarmReportController::class, 'summary'])->middleware('permission:reports.view');
            });

            // Usuarios y roles de la organización (requiere plan multiusuario).
            Route::middleware('feature:users.multi')->group(function () {
                Route::get('users/summary', [UserController::class, 'summary'])->middleware('permission:users.manage');
                Route::get('users', [UserController::class, 'index'])->middleware('permission:users.manage');
                Route::post('users', [UserController::class, 'store'])->middleware('permission:users.manage');
                Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:users.manage');
                Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:users.manage');
                Route::patch('users/{user}', [UserController::class, 'update'])->middleware('permission:users.manage');
                Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.manage');
            });

            Route::get('billing/current', [BillingController::class, 'current']);
            Route::post('billing/change-plan', [BillingController::class, 'changePlan'])->middleware('permission:billing.manage');
            Route::post('billing/cancel', [BillingController::class, 'cancel'])->middleware('permission:billing.manage');
        });
    });
});
