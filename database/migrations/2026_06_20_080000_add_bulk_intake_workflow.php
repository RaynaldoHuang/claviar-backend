<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intake_batches', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('consignor_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('intake_batch_id')->nullable()->after('code')->constrained()->nullOnDelete();
            $table->boolean('is_draft')->default(false)->after('status')->index();
            $table->string('name')->nullable()->change();
            $table->foreignId('category_id')->nullable()->change();
            $table->decimal('purchase_price', 15, 2)->nullable()->change();
            $table->decimal('selling_price', 15, 2)->nullable()->change();
            $table->string('condition', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('intake_batch_id');
            $table->dropColumn('is_draft');
        });
        Schema::dropIfExists('intake_batches');
    }
};
