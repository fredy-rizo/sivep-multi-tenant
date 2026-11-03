<?php

use App\Http\Controllers\Api\V1\AnimalController;
use App\Http\Controllers\Api\V1\AnimalEventController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ReportController;
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

            Route::get('billing/current', [BillingController::class, 'current']);
            Route::post('billing/change-plan', [BillingController::class, 'changePlan'])->middleware('permission:billing.manage');
            Route::post('billing/cancel', [BillingController::class, 'cancel'])->middleware('permission:billing.manage');
        });
    });
});
