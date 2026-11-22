<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Get dashboard statistics
    public function index()
    {
        $totalProducts = Product::count();

        $totalCustomers = Customer::count();

        $totalSuppliers = Supplier::count();

        $totalOrders = Order::count();

        $pendingOrders = Order::whereIn('status', [
            'draft',
            'confirmed',
            'packed',
            'dispatched'
        ])->count();

        $lowStockProducts = Product::whereColumn(
            'quantity',
            '<=',
            'minimum_stock'
        )->count();

        $totalInventoryValue = Product::select(
            DB::raw('COALESCE(SUM(quantity * purchase_price), 0) as total')
        )->value('total');

        $recentOrders = Order::with('customer', 'items.product')
            ->latest()
            ->take(5)
            ->get();

        $recentStockMovements = StockMovement::with('product', 'user')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'statistics' => [
                'total_products' => $totalProducts,
                'total_customers' => $totalCustomers,
                'total_suppliers' => $totalSuppliers,
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'low_stock_products' => $lowStockProducts,
                'inventory_value' => round((float) $totalInventoryValue, 2),
            ],

            'recent_orders' => $recentOrders,

            'recent_stock_movements' => $recentStockMovements,
        ]);
    }
}
