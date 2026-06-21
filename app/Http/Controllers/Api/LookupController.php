<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LookupRequest;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function categories(): JsonResponse { return response()->json(['data' => Category::orderBy('name')->get()]); }
    public function brands(): JsonResponse { return response()->json(['data' => Brand::orderBy('name')->get()]); }
    public function storeCategory(LookupRequest $request): JsonResponse { return $this->created(Category::create($request->validated())); }
    public function storeBrand(LookupRequest $request): JsonResponse { return $this->created(Brand::create($request->validated())); }
    public function updateCategory(LookupRequest $request, Category $category): JsonResponse { $category->update($request->validated()); return response()->json(['data' => $category]); }
    public function updateBrand(LookupRequest $request, Brand $brand): JsonResponse { $brand->update($request->validated()); return response()->json(['data' => $brand]); }
    public function destroyCategory(Request $request, Category $category): mixed { abort_unless($request->user()->can('manage products'), 403); return $this->destroyLookup($category); }
    public function destroyBrand(Request $request, Brand $brand): mixed { abort_unless($request->user()->can('manage products'), 403); return $this->destroyLookup($brand); }
    private function created(Model $model): JsonResponse { return response()->json(['data' => $model], 201); }
    private function destroyLookup(Model $model): mixed { abort_if($model->products()->exists(), 422, 'Data masih digunakan produk.'); $model->delete(); return response()->noContent(); }
}
