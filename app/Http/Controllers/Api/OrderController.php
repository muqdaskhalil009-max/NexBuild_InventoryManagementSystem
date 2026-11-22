<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    // Get all orders
    public function index(Request $request)
    {
        $query = Order::with('customer', 'items.product');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(
            $query->latest()->paginate(10)
        );
    }

    // Create a new draft order
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'shipping_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id|distinct',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = DB::transaction(function () use ($validated) {

            $subtotal = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::whereKey($item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $unitPrice = $product->selling_price;
                $lineTotal = $unitPrice * $item['quantity'];

                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $shippingCost = $validated['shipping_cost'] ?? 0;

            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'user_id' => auth()->id(),
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'status' => 'draft',
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total_amount' => $subtotal + $shippingCost,
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'old_status' => null,
                'new_status' => 'draft',
                'note' => 'Order created.',
            ]);

            return $order;
        });

        return response()->json([
            'message' => 'Draft order created successfully.',
            'order' => $order->load('customer', 'items.product'),
        ], 201);
    }

    // Get one order with its details
    public function show(Order $order)
    {
        return response()->json(
            $order->load('customer', 'items.product', 'statusHistory')
        );
    }

    // Change order status
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,confirmed,packed,dispatched,delivered,cancelled',
            'note' => 'nullable|string|max:1000',
        ]);

        $newStatus = $validated['status'];

        $updatedOrder = DB::transaction(function () use (
            $order,
            $newStatus,
            $validated
        ) {
            $order = Order::whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $order->status;

            $allowedTransitions = [
                'draft' => ['confirmed', 'cancelled'],
                'confirmed' => ['packed', 'cancelled'],
                'packed' => ['dispatched', 'cancelled'],
                'dispatched' => ['delivered'],
                'delivered' => [],
                'cancelled' => [],
            ];

            if (!in_array($newStatus, $allowedTransitions[$oldStatus] ?? [])) {
                throw ValidationException::withMessages([
                    'status' => ['This order status transition is not allowed.'],
                ]);
            }

            // Confirm order and deduct stock
            if ($newStatus === 'confirmed') {
                $order->load('items');

                foreach ($order->items as $item) {
                    $product = Product::whereKey($item->product_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($product->quantity < $item->quantity) {
                        throw ValidationException::withMessages([
                            'stock' => [
                                'Insufficient stock for product: '
                                . $product->name
                                . '. Available: ' . $product->quantity
                            ],
                        ]);
                    }

                    $product->decrement('quantity', $item->quantity);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'out',
                        'quantity' => $item->quantity,
                        'reason' => 'Stock reserved for order '
                            . $order->order_number,
                    ]);
                }
            }

            // Cancel a reserved order and restore its stock
            if (
                $newStatus === 'cancelled' &&
                in_array($oldStatus, ['confirmed', 'packed'])
            ) {
                $order->load('items');

                foreach ($order->items as $item) {
                    $product = Product::whereKey($item->product_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $product->increment('quantity', $item->quantity);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'in',
                        'quantity' => $item->quantity,
                        'reason' => 'Stock restored from cancelled order '
                            . $order->order_number,
                    ]);
                }
            }

            $order->update([
                'status' => $newStatus,
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $validated['note'] ?? null,
            ]);

            return $order->fresh()->load(
                'customer',
                'items.product',
                'statusHistory'
            );
        });

        return response()->json([
            'message' => 'Order status updated successfully.',
            'order' => $updatedOrder,
        ]);
    }
}
