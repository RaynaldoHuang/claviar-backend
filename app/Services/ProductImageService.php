<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProductImageService
{
    public function store(Product $product, array $files, int $coverIndex = 0): void
    {
        $manager = new ImageManager(new Driver());
        $product->images()->update(['is_cover' => false]);

        foreach ($files as $index => $file) {
            /** @var UploadedFile $file */
            $path = 'products/'.$product->id.'/'.Str::uuid().'.webp';
            $encoded = $manager
                ->decodePath($file->getRealPath())
                ->scaleDown(width: 1800, height: 1800)
                ->encodeUsingFileExtension('webp', quality: 82);
            Storage::disk('public')->put($path, (string) $encoded);
            $product->images()->create(['image' => $path, 'is_cover' => $index === $coverIndex, 'sort_order' => $index]);
        }
    }

    public function delete(Product $product): void
    {
        Storage::disk('public')->deleteDirectory('products/'.$product->id);
    }
}
