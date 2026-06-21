<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending')->index();
            $table->string('payment_method', 50)->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamp('paid_at')->nullable(); $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->unique()->constrained()->restrictOnDelete();
            $table->decimal('purchase_price', 15, 2)->nullable(); $table->decimal('sale_price', 15, 2)->nullable();
            $table->timestamp('completed_at')->nullable(); $table->timestamps();
        });
        Schema::table('sales', fn (Blueprint $table) => $table->foreignId('order_id')->nullable()->after('customer_id')->constrained()->nullOnDelete()->index());
    }
    public function down(): void
    {
        Schema::table('sales', fn (Blueprint $table) => $table->dropConstrainedForeignId('order_id'));
        Schema::dropIfExists('order_items'); Schema::dropIfExists('orders');
    }
};
