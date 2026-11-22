
<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;

// API Health Check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Inventory Management API is running.'
    ]);
});

// Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {

    // User Authentication
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', function (\Illuminate\Http\Request $request) {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Products
    Route::apiResource('products', ProductController::class);

    // Suppliers
    Route::apiResource('suppliers', SupplierController::class);

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Stock Movements
    Route::get('/stock-movements', [StockMovementController::class, 'index']);
    Route::post('/stock-movements', [StockMovementController::class, 'store']);
    Route::get('/stock-movements/{stockMovement}', [StockMovementController::class, 'show']);

    // Orders
    Route::apiResource('orders', OrderController::class)
        ->except(['update', 'destroy']);

    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);

    // Reports
    Route::get('/reports/inventory', [ReportController::class, 'inventory']);
    Route::get('/reports/stock-movements', [ReportController::class, 'stockMovements']);
    Route::get('/reports/orders', [ReportController::class, 'orders']);

});
