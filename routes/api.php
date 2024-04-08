<?php

use App\Http\Controllers\WidgetController;
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

Route::middleware(['ngrok-skip'])->group(function () {
    Route::post('import-leads', [WidgetController::class, 'importLeads']);
    Route::post('webhook', [WidgetController::class, 'webhook']);
    Route::get('new-lead/budget/{price}', [WidgetController::class, 'createLeadWithBudgetOnly']);
    Route::get('lead/{leadId}/budget/{price}/cost/{cost}', [WidgetController::class, 'updateDealBudgetWithCostSet']);
    Route::get('lead-exceed-budget/{leadId}', [WidgetController::class, 'exceedBudgetByUpdatingCost']);
});
