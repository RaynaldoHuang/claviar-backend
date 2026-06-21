<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('payout_id')->constrained()->nullOnDelete()->index();
        });

        DB::table('sales')->orderBy('id')->get()->each(function ($sale) {
            $customerId = $sale->customer_phone ? DB::table('customers')->where('phone', $sale->customer_phone)->value('id') : null;
            if (! $customerId) {
                $customerId = DB::table('customers')->insertGetId(['name' => $sale->customer_name, 'phone' => $sale->customer_phone, 'created_at' => $sale->created_at, 'updated_at' => $sale->updated_at]);
            }
            DB::table('sales')->where('id', $sale->id)->update(['customer_id' => $customerId]);
        });
    }

    public function down(): void
    {
        Schema::table('sales', fn (Blueprint $table) => $table->dropConstrainedForeignId('customer_id'));
        Schema::dropIfExists('customers');
    }
};
