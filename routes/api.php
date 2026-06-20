<?php

use App\Http\Controllers\Dashboard\AuthController;
use App\Http\Controllers\Dashboard\BranchController as DashboardBranchController;
use App\Http\Controllers\Dashboard\CancelReasonController;
use App\Http\Controllers\Dashboard\CategoryController as DashboardCategoryController;
use App\Http\Controllers\Dashboard\ItemController as DashboardItemController;
use App\Http\Controllers\Dashboard\ItemOptionController;
use App\Http\Controllers\Dashboard\LabelController;
use App\Http\Controllers\Dashboard\OrderController as DashboardOrderController;
use App\Http\Controllers\Dashboard\OrderStatusController;
use App\Http\Controllers\Dashboard\DiscountController as DashboardDiscountController;
use App\Http\Controllers\Dashboard\BranchLocationController;
use App\Http\Controllers\Dashboard\LocationPriceGroupController;
use App\Http\Controllers\Dashboard\MainPageHeaderController;
use App\Http\Controllers\Dashboard\StatsController;
use App\Http\Controllers\Dashboard\MenuController as DashboardMenuController;
use App\Http\Controllers\Dashboard\MenuGroupController;
use App\Http\Controllers\Dashboard\NumberController;
use App\Http\Controllers\Dashboard\RoleController;
use App\Http\Controllers\Dashboard\SettingController;
use App\Http\Controllers\Dashboard\VisaSettingController;
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
use App\Http\Controllers\Front\PaymentController;
use App\Http\Controllers\Front\PosterController;
use App\Http\Controllers\Front\WhatsAppNumberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
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
Route::post('order/initiate-visa', [PaymentController::class, 'initiate']);
Route::post('order/verify-visa', [PaymentController::class, 'verify']);
Route::post('order/webhook/paymob', [PaymentController::class, 'paymobWebhook']);
Route::get('order-details/{order_reference}', [OrderController::class, 'getOrderDetails']);


Route::prefix('dashboard')->group(function () {

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:api', \App\Http\Middleware\LogDashboardActivity::class])->group(function () {

        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);

        // Reverb private-channel authorization (JWT). auth:api sets the default
        // guard to 'api' on success, so Broadcast::auth() resolves the JWT user.
        Route::post('/broadcasting/auth', fn(Request $request) => Broadcast::auth($request));

        // Presence heartbeat (online status + current page). Not activity-logged.
        Route::post('/presence/heartbeat', [\App\Http\Controllers\Dashboard\PresenceController::class, 'heartbeat']);

        // ── General settings ──────────────────────────────────────────────
        Route::get('settings', [SettingController::class, 'show'])->middleware('permission:general_settings.show');
        Route::post('settings', [SettingController::class, 'update'])->middleware('permission:general_settings.edit');

        // ── Visa / payment settings ───────────────────────────────────────
        Route::get('visa-settings', [VisaSettingController::class, 'show'])->middleware('permission:visa_settings.show');
        Route::post('visa-settings', [VisaSettingController::class, 'update'])->middleware('permission:visa_settings.edit');

        // ── Users ─────────────────────────────────────────────────────────
        Route::resource('users', UserController::class)->only(['index', 'show'])->middleware('permission:users.show');
        Route::resource('users', UserController::class)->only(['store'])->middleware('permission:users.add');
        Route::resource('users', UserController::class)->only(['update'])->middleware('permission:users.edit');
        Route::resource('users', UserController::class)->only(['destroy'])->middleware('permission:users.remove');

        // ── Roles & permissions ───────────────────────────────────────────
        // Open lookup (filtered server-side) for the user-form role dropdown.
        Route::get('roles-for-users', [RoleController::class, 'getAllForUsers']);
        // Catalog route declared before roles/{role} so it is not swallowed.
        Route::get('roles/permissions-catalog', [RoleController::class, 'permissionsCatalog'])->middleware('permission:roles.show');
        Route::resource('roles', RoleController::class)->only(['index', 'show'])->middleware('permission:roles.show');
        Route::resource('roles', RoleController::class)->only(['store'])->middleware('permission:roles.add');
        Route::resource('roles', RoleController::class)->only(['update'])->middleware('permission:roles.edit');
        Route::resource('roles', RoleController::class)->only(['destroy'])->middleware('permission:roles.remove');

        // ── Theme / colors settings ───────────────────────────────────────
        Route::resource('theme', ThemeController::class)->only(['index', 'show'])->middleware('permission:colors_settings.show');
        Route::resource('theme', ThemeController::class)->only(['store', 'update', 'destroy'])->middleware('permission:colors_settings.edit');

        // ── Branches ──────────────────────────────────────────────────────
        // GET branches is an open shared lookup (user form, stats, live orders…).
        Route::get('branches', [DashboardBranchController::class, 'index']);
        Route::post('branches', [DashboardBranchController::class, 'store'])->middleware('permission:branches.add');
        Route::put('branches/{id}', [DashboardBranchController::class, 'update'])->middleware('permission:branches.edit');
        Route::delete('branches/{id}', [DashboardBranchController::class, 'destroy'])->middleware('permission:branches.remove');
        Route::post('branches/{id}/toggle-active', [DashboardBranchController::class, 'toggleActive'])->middleware('permission:branches.edit');
        Route::post('branches/{id}/toggle-busy', [DashboardBranchController::class, 'toggleBusy'])->middleware('permission:branches.edit');
        Route::post('branches/{branchId}/assign', [DashboardBranchController::class, 'assign'])->middleware('permission:branches.edit');
        Route::post('branches/{branchId}/unassign', [DashboardBranchController::class, 'unassign'])->middleware('permission:branches.edit');

        Route::get('branches/{branchId}/locations', [BranchLocationController::class, 'index'])->middleware('permission:branches.show');
        Route::post('branches/{branchId}/locations', [BranchLocationController::class, 'store'])->middleware('permission:branches.edit');
        Route::put('branches/{branchId}/locations/{locationId}', [BranchLocationController::class, 'update'])->middleware('permission:branches.edit');
        Route::delete('branches/{branchId}/locations/{locationId}', [BranchLocationController::class, 'destroy'])->middleware('permission:branches.edit');

        Route::get('branches/{branchId}/location-price-groups', [LocationPriceGroupController::class, 'index'])->middleware('permission:branches.show');
        Route::post('branches/{branchId}/location-price-groups', [LocationPriceGroupController::class, 'store'])->middleware('permission:branches.edit');
        Route::put('branches/{branchId}/location-price-groups/{groupId}', [LocationPriceGroupController::class, 'update'])->middleware('permission:branches.edit');
        Route::delete('branches/{branchId}/location-price-groups/{groupId}', [LocationPriceGroupController::class, 'destroy'])->middleware('permission:branches.edit');

        // ── Menus & menu groups ───────────────────────────────────────────
        Route::get('menu-groups', [MenuGroupController::class, 'index'])->middleware('permission:menus.show');
        Route::post('menu-groups', [MenuGroupController::class, 'store'])->middleware('permission:menus.add');
        Route::put('menu-groups/{id}', [MenuGroupController::class, 'update'])->middleware('permission:menus.edit');
        Route::delete('menu-groups/{id}', [MenuGroupController::class, 'destroy'])->middleware('permission:menus.remove');
        Route::post('menu-groups/{id}/assign-branches', [MenuGroupController::class, 'assignBranches'])->middleware('permission:menus.edit');
        Route::get('menu-groups/{id}/menus', [MenuGroupController::class, 'menus'])->middleware('permission:menus.show');
        Route::post('menu-groups/{id}/menus', [DashboardMenuController::class, 'store'])->middleware('permission:menus.add');
        Route::post('menu-groups/{id}/menus/{menuId}', [DashboardMenuController::class, 'update'])->middleware('permission:menus.edit');
        Route::delete('menu-groups/{id}/menus/{menuId}', [DashboardMenuController::class, 'destroy'])->middleware('permission:menus.remove');
        Route::post('menu-groups/{id}/sort', [MenuGroupController::class, 'updateSort'])->middleware('permission:menus.edit');

        // ── Stock ─────────────────────────────────────────────────────────
        Route::get('item-stock', [\App\Http\Controllers\Dashboard\ItemStockController::class, 'index'])->middleware('permission:stock.show');
        Route::post('items/{id}/out-of-stock', [\App\Http\Controllers\Dashboard\ItemStockController::class, 'store'])->middleware('permission:stock.update');
        Route::delete('item-stock-restrictions/{id}', [\App\Http\Controllers\Dashboard\ItemStockController::class, 'destroy'])->middleware('permission:stock.update');

        // ── Discounts ─────────────────────────────────────────────────────
        Route::get('discounts', [DashboardDiscountController::class, 'index'])->middleware('permission:discounts.show');
        Route::post('discounts', [DashboardDiscountController::class, 'store'])->middleware('permission:discounts.add');
        Route::get('discounts/form-data/categories', [DashboardDiscountController::class, 'categories'])->middleware('permission:discounts.show');
        Route::get('discounts/form-data/locations', [DashboardDiscountController::class, 'locations'])->middleware('permission:discounts.show');
        Route::get('discounts/{id}', [DashboardDiscountController::class, 'show'])->middleware('permission:discounts.show');
        Route::put('discounts/{id}', [DashboardDiscountController::class, 'update'])->middleware('permission:discounts.edit');
        Route::delete('discounts/{id}', [DashboardDiscountController::class, 'destroy'])->middleware('permission:discounts.remove');
        Route::post('discounts/{id}/toggle-active', [DashboardDiscountController::class, 'toggleActive'])->middleware('permission:discounts.edit');

        // ── Working periods & groups ──────────────────────────────────────
        Route::apiResource('working-period-groups', WorkingPeriodGroupController::class)->only(['index'])->middleware('permission:working_periods.show');
        Route::apiResource('working-period-groups', WorkingPeriodGroupController::class)->only(['store'])->middleware('permission:working_periods.add');
        Route::apiResource('working-period-groups', WorkingPeriodGroupController::class)->only(['update'])->middleware('permission:working_periods.edit');
        Route::apiResource('working-period-groups', WorkingPeriodGroupController::class)->only(['destroy'])->middleware('permission:working_periods.remove');
        Route::post('working-period-groups/{groupId}/periods', [WorkingPeriodController::class, 'store'])->middleware('permission:working_periods.add');
        Route::put('working-period-groups/{groupId}/periods/{periodId}', [WorkingPeriodController::class, 'update'])->middleware('permission:working_periods.edit');
        Route::delete('working-period-groups/{groupId}/periods/{periodId}', [WorkingPeriodController::class, 'destroy'])->middleware('permission:working_periods.remove');

        // ── Reports / stats ───────────────────────────────────────────────
        Route::get('stats', [StatsController::class, 'index'])->middleware('permission:reports.show');

        // ── Activity logs ─────────────────────────────────────────────────
        Route::get('logs', [\App\Http\Controllers\Dashboard\ActivityLogController::class, 'index'])->middleware('permission:logs.show');

        // ── Main page header media ────────────────────────────────────────
        Route::get('headers', [MainPageHeaderController::class, 'index'])->middleware('permission:headers.show');
        Route::post('headers', [MainPageHeaderController::class, 'store'])->middleware('permission:headers.add');
        Route::post('headers/sort', [MainPageHeaderController::class, 'updateSort'])->middleware('permission:headers.edit');
        Route::put('headers/{sort}', [MainPageHeaderController::class, 'update'])->middleware('permission:headers.edit');
        Route::delete('headers/{sort}', [MainPageHeaderController::class, 'destroy'])->middleware('permission:headers.remove');

        // ── Contact numbers ───────────────────────────────────────────────
        Route::get('numbers', [NumberController::class, 'index'])->middleware('permission:numbers.show');
        Route::post('numbers', [NumberController::class, 'store'])->middleware('permission:numbers.add');
        Route::delete('numbers/{id}', [NumberController::class, 'destroy'])->middleware('permission:numbers.delete');

        // ── Cancel reasons ────────────────────────────────────────────────
        // GET stays open: the live-orders reject modal needs the list.
        Route::get('cancel-reasons', [CancelReasonController::class, 'index']);
        Route::post('cancel-reasons', [CancelReasonController::class, 'store'])->middleware('permission:cancel_reasons.add');
        Route::delete('cancel-reasons/{cancelReason}', [CancelReasonController::class, 'destroy'])->middleware('permission:cancel_reasons.delete');

        // ── Orders (live + history) ───────────────────────────────────────
        Route::get('orders', [DashboardOrderController::class, 'index'])->middleware('permission:live_orders.show|order_history.show');
        Route::get('orders/export', [DashboardOrderController::class, 'export'])->middleware('permission:order_history.show');
        Route::get('orders/{order}', [DashboardOrderController::class, 'show'])->middleware('permission:live_orders.show|order_history.show');
        Route::delete('orders/{order}', [DashboardOrderController::class, 'destroy'])->middleware('permission:order_history.remove');
        Route::put('orders/{order}/status', [OrderStatusController::class, 'update'])->middleware('permission:live_orders.approve|live_orders.cancel');

        // ── Categories ────────────────────────────────────────────────────
        // GET also feeds the items page, so allow items.show holders to read it.
        Route::get('categories', [DashboardCategoryController::class, 'index'])->middleware('permission:categories.show|items.show');
        Route::post('categories', [DashboardCategoryController::class, 'store'])->middleware('permission:categories.add');
        Route::post('categories/sort', [DashboardCategoryController::class, 'updateSort'])->middleware('permission:categories.edit');
        Route::put('categories/{category}', [DashboardCategoryController::class, 'update'])->middleware('permission:categories.edit');
        Route::delete('categories/{category}', [DashboardCategoryController::class, 'destroy'])->middleware('permission:categories.remove');

        // ── Items ─────────────────────────────────────────────────────────
        Route::get('items', [DashboardItemController::class, 'index'])->middleware('permission:items.show');
        Route::post('items', [DashboardItemController::class, 'store'])->middleware('permission:items.add');
        Route::post('items/sort', [DashboardItemController::class, 'updateSort'])->middleware('permission:items.edit'); // before {item} to avoid conflict
        Route::get('items/{item}', [DashboardItemController::class, 'show'])->middleware('permission:items.show');
        Route::post('items/{item}', [DashboardItemController::class, 'update'])->middleware('permission:items.edit'); // POST for file upload
        Route::delete('items/{item}', [DashboardItemController::class, 'destroy'])->middleware('permission:items.remove');
        Route::post('items/{item}/branch-price', [DashboardItemController::class, 'upsertBranchPrice'])->middleware('permission:items.edit');
        Route::delete('items/{item}/branch-price', [DashboardItemController::class, 'deleteBranchPrice'])->middleware('permission:items.edit');
        Route::post('items/{item}/sizes', [DashboardItemController::class, 'storeSizes'])->middleware('permission:items.edit');
        Route::put('items/{item}/sizes/{sizeId}', [DashboardItemController::class, 'updateSize'])->middleware('permission:items.edit');
        Route::delete('items/{item}/sizes/{sizeId}', [DashboardItemController::class, 'destroySize'])->middleware('permission:items.edit');
        Route::post('items/{item}/sizes/{sizeId}/branch-prices', [DashboardItemController::class, 'upsertSizeBranchPrice'])->middleware('permission:items.edit');
        Route::delete('items/{item}/sizes/{sizeId}/branch-prices', [DashboardItemController::class, 'deleteSizeBranchPrice'])->middleware('permission:items.edit');
        Route::post('items/{item}/options', [DashboardItemController::class, 'attachOption'])->middleware('permission:items.edit');
        Route::put('items/{item}/options/{optionId}', [DashboardItemController::class, 'updateOptionPivot'])->middleware('permission:items.edit');
        Route::delete('items/{item}/options/{optionId}', [DashboardItemController::class, 'detachOption'])->middleware('permission:items.edit');

        // ── Labels ────────────────────────────────────────────────────────
        Route::get('labels', [LabelController::class, 'index'])->middleware('permission:items.show');
        Route::post('labels', [LabelController::class, 'store'])->middleware('permission:items.edit');
        Route::put('labels/{label}', [LabelController::class, 'update'])->middleware('permission:items.edit');
        Route::delete('labels/{label}', [LabelController::class, 'destroy'])->middleware('permission:items.edit');

        // ── Shared item options ───────────────────────────────────────────
        // GET also feeds the items page (option picker), so allow items.show too.
        Route::get('item-options', [ItemOptionController::class, 'index'])->middleware('permission:options.show|items.show');
        Route::post('item-options', [ItemOptionController::class, 'store'])->middleware('permission:options.add');
        Route::put('item-options/{itemOption}', [ItemOptionController::class, 'update'])->middleware('permission:options.edit');
        Route::delete('item-options/{itemOption}', [ItemOptionController::class, 'destroy'])->middleware('permission:options.remove');
        Route::post('item-options/{itemOption}/values', [ItemOptionController::class, 'storeValue'])->middleware('permission:options.edit');
        Route::put('item-options/{itemOption}/values/{valueId}', [ItemOptionController::class, 'updateValue'])->middleware('permission:options.edit');
        Route::delete('item-options/{itemOption}/values/{valueId}', [ItemOptionController::class, 'destroyValue'])->middleware('permission:options.edit');
        Route::post('item-options/{itemOption}/values/{valueId}/branch-prices', [ItemOptionController::class, 'upsertValueBranchPrice'])->middleware('permission:options.edit');
        Route::delete('item-options/{itemOption}/values/{valueId}/branch-prices', [ItemOptionController::class, 'deleteValueBranchPrice'])->middleware('permission:options.edit');
    });

});