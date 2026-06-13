<?php

use App\Http\Controllers\Dashboard\AuthController;
use App\Http\Controllers\Dashboard\BranchController as DashboardBranchController;
use App\Http\Controllers\Dashboard\DiscountController as DashboardDiscountController;
use App\Http\Controllers\Dashboard\BranchLocationController;
use App\Http\Controllers\Dashboard\LocationPriceGroupController;
use App\Http\Controllers\Dashboard\MainPageHeaderController;
use App\Http\Controllers\Dashboard\MenuController as DashboardMenuController;
use App\Http\Controllers\Dashboard\MenuGroupController;
use App\Http\Controllers\Dashboard\NumberController;
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

        Route::get('branches/{branchId}/location-price-groups', [LocationPriceGroupController::class, 'index']);
        Route::post('branches/{branchId}/location-price-groups', [LocationPriceGroupController::class, 'store']);
        Route::put('branches/{branchId}/location-price-groups/{groupId}', [LocationPriceGroupController::class, 'update']);
        Route::delete('branches/{branchId}/location-price-groups/{groupId}', [LocationPriceGroupController::class, 'destroy']);

        Route::get('menu-groups', [MenuGroupController::class, 'index']);
        Route::post('menu-groups', [MenuGroupController::class, 'store']);
        Route::put('menu-groups/{id}', [MenuGroupController::class, 'update']);
        Route::delete('menu-groups/{id}', [MenuGroupController::class, 'destroy']);
        Route::post('menu-groups/{id}/assign-branches', [MenuGroupController::class, 'assignBranches']);
        Route::get('menu-groups/{id}/menus', [MenuGroupController::class, 'menus']);
        Route::post('menu-groups/{id}/menus', [DashboardMenuController::class, 'store']);
        Route::post('menu-groups/{id}/menus/{menuId}', [DashboardMenuController::class, 'update']);
        Route::delete('menu-groups/{id}/menus/{menuId}', [DashboardMenuController::class, 'destroy']);
        Route::post('menu-groups/{id}/sort', [MenuGroupController::class, 'updateSort']);

        Route::get('items', [\App\Http\Controllers\Dashboard\ItemStockController::class, 'index']);
        Route::post('items/{id}/out-of-stock', [\App\Http\Controllers\Dashboard\ItemStockController::class, 'store']);
        Route::delete('item-stock-restrictions/{id}', [\App\Http\Controllers\Dashboard\ItemStockController::class, 'destroy']);

        Route::get('discounts', [DashboardDiscountController::class, 'index']);
        Route::post('discounts', [DashboardDiscountController::class, 'store']);
        Route::get('discounts/form-data/categories', [DashboardDiscountController::class, 'categories']);
        Route::get('discounts/form-data/locations', [DashboardDiscountController::class, 'locations']);
        Route::get('discounts/{id}', [DashboardDiscountController::class, 'show']);
        Route::put('discounts/{id}', [DashboardDiscountController::class, 'update']);
        Route::delete('discounts/{id}', [DashboardDiscountController::class, 'destroy']);
        Route::post('discounts/{id}/toggle-active', [DashboardDiscountController::class, 'toggleActive']);

        Route::apiResource('working-period-groups', WorkingPeriodGroupController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('working-period-groups/{groupId}/periods', [WorkingPeriodController::class, 'store']);
        Route::put('working-period-groups/{groupId}/periods/{periodId}', [WorkingPeriodController::class, 'update']);
        Route::delete('working-period-groups/{groupId}/periods/{periodId}', [WorkingPeriodController::class, 'destroy']);

        Route::get('headers', [MainPageHeaderController::class, 'index']);
        Route::post('headers', [MainPageHeaderController::class, 'store']);
        Route::post('headers/sort', [MainPageHeaderController::class, 'updateSort']);
        Route::put('headers/{sort}', [MainPageHeaderController::class, 'update']);
        Route::delete('headers/{sort}', [MainPageHeaderController::class, 'destroy']);

        Route::get('numbers', [NumberController::class, 'index']);
        Route::post('numbers', [NumberController::class, 'store']);
        Route::delete('numbers/{id}', [NumberController::class, 'destroy']);
    });

});