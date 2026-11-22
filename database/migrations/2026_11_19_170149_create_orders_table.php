
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Customer who placed the order
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            // Staff member who created the order
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Unique order identification
            $table->string('order_number')->unique();

            // Current order status
            $table->enum('status', [
                'draft',
                'confirmed',
                'packed',
                'dispatched',
                'delivered',
                'cancelled'
            ])->default('draft');

            // Order amounts
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
