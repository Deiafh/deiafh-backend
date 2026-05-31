<?php

use App\Http\Controllers\Dashboard\AuthController;
use App\Http\Controllers\Dashboard\RoleController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Front\BranchController;
use App\Http\Controllers\Front\CategoryController;
use App\Http\Controllers\Front\ConfigurationsController;
use App\Http\Controllers\Front\DiscountsController;
use App\Http\Controllers\Front\HomePageController;
use App\Http\Controllers\Front\MenuController;
use App\Http\Controllers\Front\OrderController;
use App\Http\Controllers\Front\PosterController;
use App\Http\Controllers\Front\WhatsAppNumberController;
use Illuminate\Support\Facades\Route;

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
Route::post('discounts/get-public-discounts', [DiscountsController::class, 'getPublicDiscounts']);
Route::post('discounts/validate-discounts', [DiscountsController::class, 'checkDiscountCode']);
Route::post('order/get-final-info', [OrderController::class, 'getFinalInfo']);
Route::post('order/place-order', [OrderController::class, 'placeOrder']);
Route::get('order-details/{order_reference}', [OrderController::class, 'getOrderDetails']);


Route::prefix('dashboard')->group(function () {

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {

        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);

        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
        Route::get('roles-for-users', [RoleController::class, 'getAllForUsers']);
    });

});