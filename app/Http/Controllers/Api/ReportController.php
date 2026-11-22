<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // Inventory Report
    public function inventory()
    {
        $products = Product::latest()->get();

        $summary = [
            'total_products' => Product::count(),
            'total_quantity' => Product::sum('quantity'),
            'low_stock_products' => Product::whereColumn(
                'quantity',
                '<=',
                'minimum_stock'
            )->count(),
            'inventory_value' => (float) Product::sum(
                DB::raw('quantity * purchase_price')
            ),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'products' => $products,
        ]);
    }

    // Stock Movement Report
    public function stockMovements(Request $request)
    {
        $query = StockMovement::with('product', 'user')->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json([
            'success' => true,
            'total_movements' => $query->count(),
            'movements' => $query->get(),
        ]);
    }

    // Orders Report
    public function orders()
    {
        $orders = Order::with('customer', 'items.product')
            ->latest()
            ->get();

        $summary = [
            'total_orders' => Order::count(),
            'draft_orders' => Order::where('status', 'draft')->count(),
            'confirmed_orders' => Order::where('status', 'confirmed')->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'total_order_value' => (float) Order::sum('total_amount'),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'orders' => $orders,
        ]);
    }
}
