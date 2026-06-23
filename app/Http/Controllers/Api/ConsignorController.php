<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConsignorRequest;
use App\Http\Resources\ConsignorResource;
use App\Models\Consignor;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ConsignorController extends Controller
{
    public function __construct(private readonly ProductImageService $images) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ConsignorResource::collection(Consignor::query()->withCount([
            'products',
            'payouts',
            'intakeBatches',
            'products as stock_count' => fn ($q) => $q->whereIn('status', ['available', 'reserved']),
            'products as ready_count' => fn ($q) => $q->where('status', 'available')->where('is_draft', false),
            'products as draft_count' => fn ($q) => $q->where('is_draft', true),
            'products as reserved_count' => fn ($q) => $q->where('status', 'reserved'),
            'products as sold_count' => fn ($q) => $q->where('status', 'sold'),
            'products as order_items_count' => fn ($q) => $q->whereHas('orderItem'),
        ])->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))->when($request->search, fn ($q, $search) => $q->where(fn ($n) => $n->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))->latest()->paginate($request->integer('per_page', 15))->withQueryString());
    }

    public function store(ConsignorRequest $request): ConsignorResource
    {
        return new ConsignorResource(Consignor::create($request->validated()));
    }

    public function show(Consignor $consignor): ConsignorResource
    {
        return new ConsignorResource($consignor->load(['products.category', 'products.brand'])->loadCount('products'));
    }

    public function update(ConsignorRequest $request, Consignor $consignor): ConsignorResource
    {
        $consignor->update($request->validated());

        return new ConsignorResource($consignor->fresh()->loadCount('products'));
    }

    public function destroy(Consignor $consignor): Response
    {
        Gate::authorize('delete', $consignor);

        $productIds = [];

        try {
            DB::transaction(function () use ($consignor, &$productIds): void {
                $lockedConsignor = Consignor::query()->lockForUpdate()->findOrFail($consignor->id);
                $blockers = collect([
                    'riwayat penjualan' => $lockedConsignor->products()->where(fn ($query) => $query->where('status', 'sold')->orWhereHas('sale'))->exists(),
                    'riwayat payout' => $lockedConsignor->payouts()->exists(),
                    'order aktif' => $lockedConsignor->products()->whereHas('orderItem')->exists(),
                ])->filter()->keys();

                abort_if($blockers->isNotEmpty(), 422, 'Consignor tidak dapat dihapus karena masih memiliki '.$blockers->join(', ', ' dan ').'.');
                $productIds = $lockedConsignor->products()->pluck('id')->all();
                $lockedConsignor->products()->delete();
                $lockedConsignor->intakeBatches()->delete();
                $lockedConsignor->delete();
            });
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23000', '23503'], true)) {
                abort(422, 'Consignor tidak dapat dihapus karena masih digunakan oleh data lain.');
            }

            throw $exception;
        }

        foreach ($productIds as $productId) {
            $product = new Product;
            $product->id = $productId;
            $this->images->delete($product);
        }

        return response()->noContent();
    }
}
