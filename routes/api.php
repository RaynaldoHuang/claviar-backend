<?php

use App\Http\Controllers\Api\{AuthController, BulkIntakeController, CheckoutController, CompleteProductSaleController, ConsignorController, CustomerController, DashboardController, GlobalSearchController, LookupController, OrderController, PayoutController, ProductController, ReportController, SaleController};
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']); Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::put('/auth/profile', [AuthController::class, 'profile']); Route::put('/auth/password', [AuthController::class, 'password']);
    Route::get('/dashboard', DashboardController::class);
    Route::get('/search', GlobalSearchController::class);
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    Route::put('/customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
    Route::get('/orders', [OrderController::class, 'index']); Route::post('/orders', [OrderController::class, 'store']); Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::get('/checkouts', [CheckoutController::class, 'index']);
    Route::post('/orders/{order}/items/{product}', [OrderController::class, 'completeItem']); Route::post('/orders/{order}/pay', [OrderController::class, 'pay']); Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::delete('/orders/{order}/items/{product}', [OrderController::class, 'removeItem']);
    Route::post('/consignors/{consignor}/intake', BulkIntakeController::class);
    Route::post('/products/{product}/complete-sale', CompleteProductSaleController::class);
    Route::get('/payouts/outstanding', [PayoutController::class, 'outstanding']);
    Route::apiResources(['consignors' => ConsignorController::class, 'products' => ProductController::class, 'sales' => SaleController::class, 'payouts' => PayoutController::class]);
    Route::patch('/payouts/{payout}/mark-paid', [PayoutController::class, 'markPaid']);
    Route::get('/categories', [LookupController::class, 'categories']); Route::post('/categories', [LookupController::class, 'storeCategory']); Route::put('/categories/{category}', [LookupController::class, 'updateCategory']); Route::delete('/categories/{category}', [LookupController::class, 'destroyCategory']);
    Route::get('/brands', [LookupController::class, 'brands']); Route::post('/brands', [LookupController::class, 'storeBrand']); Route::put('/brands/{brand}', [LookupController::class, 'updateBrand']); Route::delete('/brands/{brand}', [LookupController::class, 'destroyBrand']);
    Route::get('/reports/{type}', ReportController::class);
});
