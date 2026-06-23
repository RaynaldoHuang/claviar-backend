<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('consignors')
            ->whereNull('stock_status')
            ->orWhereIn('stock_status', ['available', 'not_ready'])
            ->update(['stock_status' => 'selling']);
    }

    public function down(): void
    {
        DB::table('consignors')->where('stock_status', 'selling')->update(['stock_status' => null]);
    }
};
