<?php

use Illuminate\Support\Facades\Route;
use Modules\Landlord\Http\Controllers\LandlordController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('landlords', LandlordController::class)->names('landlord');
});
