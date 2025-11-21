<?php

use App\Http\Controllers\Front\BranchController;
use App\Http\Controllers\Front\CategoryController;
use App\Http\Controllers\Front\ConfigurationsController;
use App\Http\Controllers\Front\HomePageController;
use App\Http\Controllers\Front\MenuController;
use App\Http\Controllers\Front\OrderController;
use App\Http\Controllers\Front\PosterController;
use App\Http\Controllers\Front\WhatsAppNumberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('poster', [PosterController::class, 'index']);
Route::get('whatsapp-numbers', [WhatsAppNumberController::class, 'index']);
Route::get('home-data', [HomePageController::class, 'index']);
Route::get('configurations', [ConfigurationsController::class, 'index']);
Route::get('menu', [MenuController::class, 'index']);
Route::get('branches/{branchId}/validate', [BranchController::class, 'validateBranch']);
Route::get('branch/{branchId}', [BranchController::class, 'getBranchDetails']);
Route::get('branches', [BranchController::class, 'getBranches']);
Route::get('categories', [CategoryController::class, 'getCategories']);
Route::post('validateCart', [OrderController::class, 'validateCart']);
Route::get('branch/{branchId}/locations', [BranchController::class, 'getLocations']);
Route::post('validate-user-info', [OrderController::class, 'validateUserInfo']);
