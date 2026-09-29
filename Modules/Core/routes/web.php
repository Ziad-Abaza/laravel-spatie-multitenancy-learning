<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\LocaleController;
use Modules\Core\Http\Controllers\ThemeController;

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
Route::post('/theme', [ThemeController::class, 'update'])->name('theme.update');
