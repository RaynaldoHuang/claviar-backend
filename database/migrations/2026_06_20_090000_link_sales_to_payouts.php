<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('payout_id')->nullable()->after('product_id');
            $table->index('payout_id', 'sales_payout_id_index');
            $table->foreign('payout_id', 'sales_payout_id_foreign')->references('id')->on('payouts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', fn (Blueprint $table) => $table->dropConstrainedForeignId('payout_id'));
    }
};
