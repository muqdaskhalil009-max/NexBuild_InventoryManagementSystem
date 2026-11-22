<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // Get all customers
    public function index(Request $request)
    {
        $query = Customer::withCount('orders');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->latest()->paginate(10)
        );
    }

    // Create a customer
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'message' => 'Customer created successfully.',
            'customer' => $customer
        ], 201);
    }

    // Get one customer with order history
    public function show(Customer $customer)
    {
        return response()->json(
            $customer->load('orders')
        );
    }

    // Update a customer
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
        ]);

        $customer->update($validated);

        return response()->json([
            'message' => 'Customer updated successfully.',
            'customer' => $customer->fresh()
        ]);
    }

    // Delete a customer
    public function destroy(Customer $customer)
    {
        if ($customer->orders()->exists()) {
            return response()->json([
                'message' => 'This customer has order history and cannot be deleted.'
            ], 409);
        }

        $customer->delete();

        return response()->json([
            'message' => 'Customer deleted successfully.'
        ]);
    }
}
