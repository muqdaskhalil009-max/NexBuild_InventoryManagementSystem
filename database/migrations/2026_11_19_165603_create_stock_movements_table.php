
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            // Product whose stock is changing
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            // Staff member who performed the movement
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Stock coming in or going out
            $table->enum('type', ['in', 'out']);

            $table->unsignedInteger('quantity');

            // Reason for stock movement
            $table->string('reason');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
