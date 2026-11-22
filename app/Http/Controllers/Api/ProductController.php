<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    // Display all products with search and low-stock filter
    public function index(Request $request)
    {
        $query = Product::with('supplier');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('quantity', '<=', 'minimum_stock');
        }

        return response()->json(
            $query->latest()->paginate(10)
        );
    }

    // Add a new product
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'description' => 'nullable|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product
        ], 201);
    }

    // Show a single product
    public function show(Product $product)
    {
        return response()->json(
            $product->load('supplier', 'stockMovements')
        );
    }

    // Update product details
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'name' => 'sometimes|required|string|max:255',
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
            'description' => 'nullable|string',
            'purchase_price' => 'sometimes|required|numeric|min:0',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'quantity' => 'sometimes|required|integer|min:0',
            'minimum_stock' => 'sometimes|required|integer|min:0',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product->fresh()->load('supplier')
        ]);
    }

    // Delete a product
    public function destroy(Product $product)
    {
        if (
            $product->orderItems()->exists() ||
            $product->stockMovements()->exists()
        ) {
            return response()->json([
                'message' => 'This product has transaction history and cannot be deleted.'
            ], 409);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.'
        ]);
    }
}
