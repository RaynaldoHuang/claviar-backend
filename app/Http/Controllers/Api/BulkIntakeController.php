<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkIntakeRequest;
use App\Models\Consignor;
use App\Models\IntakeBatch;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BulkIntakeController extends Controller
{
    public function __invoke(BulkIntakeRequest $request, Consignor $consignor): JsonResponse
    {
        $quantity = $request->integer('quantity');
        $batch = DB::transaction(function () use ($request, $consignor, $quantity) {
            $reference = 'INT-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
            $batch = IntakeBatch::create(['reference' => $reference, 'consignor_id' => $consignor->id, 'quantity' => $quantity, 'notes' => $request->input('notes')]);
            $now = now();
            $products = collect(range(1, $quantity))->map(fn (int $number) => [
                'code' => $reference.'-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'intake_batch_id' => $batch->id, 'consignor_id' => $consignor->id,
                'status' => 'available', 'is_draft' => true, 'created_at' => $now, 'updated_at' => $now,
            ])->all();
            Product::insert($products);
            return $batch;
        });

        return response()->json(['message' => "{$quantity} kartu produk berhasil dibuat.", 'data' => ['batch_id' => $batch->id, 'reference' => $batch->reference, 'quantity' => $quantity]], 201);
    }
}
