<?php

use App\Http\Controllers\Dashboard\AuthController;
use App\Http\Controllers\Dashboard\BranchController as DashboardBranchController;
use App\Http\Controllers\Dashboard\BranchLocationController;
use App\Http\Controllers\Dashboard\RoleController;
use App\Http\Controllers\Dashboard\SettingController;
use App\Http\Controllers\Dashboard\ThemeController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\WorkingPeriodController;
use App\Http\Controllers\Dashboard\WorkingPeriodGroupController;
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

        Route::get('settings', [SettingController::class, 'show']);
        Route::post('settings', [SettingController::class, 'update']);

        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class);
        Route::get('roles-for-users', [RoleController::class, 'getAllForUsers']);

        Route::resource('theme', ThemeController::class);

        Route::get('branches', [DashboardBranchController::class, 'index']);
        Route::post('branches', [DashboardBranchController::class, 'store']);
        Route::put('branches/{id}', [DashboardBranchController::class, 'update']);
        Route::delete('branches/{id}', [DashboardBranchController::class, 'destroy']);
        Route::post('branches/{id}/toggle-active', [DashboardBranchController::class, 'toggleActive']);
        Route::post('branches/{id}/toggle-busy', [DashboardBranchController::class, 'toggleBusy']);
        Route::post('branches/{branchId}/assign', [DashboardBranchController::class, 'assign']);
        Route::post('branches/{branchId}/unassign', [DashboardBranchController::class, 'unassign']);

        Route::get('branches/{branchId}/locations', [BranchLocationController::class, 'index']);
        Route::post('branches/{branchId}/locations', [BranchLocationController::class, 'store']);
        Route::put('branches/{branchId}/locations/{locationId}', [BranchLocationController::class, 'update']);
        Route::delete('branches/{branchId}/locations/{locationId}', [BranchLocationController::class, 'destroy']);

        Route::apiResource('working-period-groups', WorkingPeriodGroupController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('working-period-groups/{groupId}/periods', [WorkingPeriodController::class, 'store']);
        Route::put('working-period-groups/{groupId}/periods/{periodId}', [WorkingPeriodController::class, 'update']);
        Route::delete('working-period-groups/{groupId}/periods/{periodId}', [WorkingPeriodController::class, 'destroy']);
    });

});