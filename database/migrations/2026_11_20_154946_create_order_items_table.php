
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            // Link each item to its order
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // Link item to its product
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            // Quantity ordered
            $table->unsignedInteger('quantity');

            // Product price at the time of ordering
            $table->decimal('unit_price', 10, 2);

            // Total price for this product in the order
            $table->decimal('line_total', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
