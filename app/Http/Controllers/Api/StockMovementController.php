<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementController extends Controller
{
    // Get stock movement history
    public function index(Request $request)
    {
        $query = StockMovement::with(['product', 'user']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json(
            $query->latest()->paginate(10)
        );
    }

    // Add stock or remove stock
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $movement = DB::transaction(function () use ($validated) {

            $product = Product::whereKey($validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $validated['type'] === 'out' &&
                $product->quantity < $validated['quantity']
            ) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        'Insufficient stock. Available quantity is '
                        . $product->quantity . '.'
                    ]
                ]);
            }

            if ($validated['type'] === 'in') {
                $product->increment('quantity', $validated['quantity']);
            } else {
                $product->decrement('quantity', $validated['quantity']);
            }

            return StockMovement::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'],
            ]);
        });

        return response()->json([
            'message' => 'Stock movement recorded successfully.',
            'movement' => $movement->load('product', 'user'),
        ], 201);
    }

    // View one stock movement
    public function show(StockMovement $stockMovement)
    {
        return response()->json(
            $stockMovement->load('product', 'user')
        );
    }
}
