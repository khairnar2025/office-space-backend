<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\StoreProductRequest;
use App\Http\Requests\Api\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProductController extends BaseController
{
    public function index(): JsonResponse
    {
        $products = Product::with('colors')->latest()->get();
        return $this->sendResponse(ProductResource::collection($products), 'Products fetched successfully.');
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('thumbnail'))
            $data['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');

        $gallery = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $img) $gallery[] = $img->store('products/gallery', 'public');
        }
        $data['gallery'] = $gallery ?: null;

        $product = Product::create($data);

        if (!empty($data['colors'])) {
            $product->colors()->sync($data['colors']);
        }

        return $this->sendSimpleResponse($product->id, true, 'Product created successfully.');
    }

    public function show(Product $product): JsonResponse
    {
        $product->load('colors');
        return $this->sendResponse(new ProductResource($product), 'Product details retrieved successfully.');
    }
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();

        /** ---------------------------------
         *  Start with existing gallery images
         * --------------------------------- */
        $gallery = $product->gallery ?? [];

        /** ---------------------------------
         *  Delete selected gallery images
         * --------------------------------- */
        if ($request->filled('delete_gallery')) {
            foreach ((array) $request->delete_gallery as $index) {
                if (isset($gallery[$index]) && is_string($gallery[$index])) {
                    $path = $gallery[$index];
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                    unset($gallery[$index]);
                }
            }
            $gallery = array_values($gallery); // reindex to avoid gaps
        }

        /** ---------------------------------
         *  Add new gallery images
         * --------------------------------- */
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $img) {
                $gallery[] = $img->store('products/gallery', 'public');
            }
        }

        // Save updated gallery
        $data['gallery'] = $gallery;

        /** ---------------------------------
         *  Handle colors (detach + add new)
         * --------------------------------- */
        if ($request->filled('delete_colors')) {
            $product->colors()->detach($request->delete_colors);
        }

        if (!empty($data['colors'])) {
            $product->colors()->syncWithoutDetaching($data['colors']);
        }

        /** ---------------------------------
         *  Update thumbnail
         * --------------------------------- */
        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail && Storage::disk('public')->exists($product->thumbnail)) {
                Storage::disk('public')->delete($product->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }

        /** ---------------------------------
         *  Update product record
         * --------------------------------- */
        $product->update($data);

        /** ---------------------------------
         *  Re-attach any new colors (safety)
         * --------------------------------- */
        if (!empty($data['colors'])) {
            $product->colors()->syncWithoutDetaching($data['colors']);
        }

        return $this->sendSimpleResponse($product->id, true, 'Product updated successfully.');
    }

    // public function update(UpdateProductRequest $request, Product $product): JsonResponse
    // {
    //     $data = $request->validated();
    //     if ($request->filled('delete_gallery') && !empty($product->gallery)) {
    //         $gallery = $product->gallery;

    //         foreach ((array) $request->delete_gallery as $index) {
    //             if (isset($gallery[$index]) && is_string($gallery[$index])) {
    //                 $path = $gallery[$index];
    //                 if (Storage::disk('public')->exists($path)) {
    //                     Storage::disk('public')->delete($path);
    //                 }

    //                 unset($gallery[$index]);
    //             }
    //         }
    //         $data['gallery'] = array_values($gallery);
    //     }


    //     if ($request->filled('delete_colors')) {
    //         $product->colors()->detach($request->delete_colors);
    //     }

    //     if (!empty($data['colors'])) {
    //         $product->colors()->syncWithoutDetaching($data['colors']);
    //     }
    //     if ($request->hasFile('thumbnail')) {
    //         if ($product->thumbnail && Storage::disk('public')->exists($product->thumbnail))
    //             Storage::disk('public')->delete($product->thumbnail);
    //         $data['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
    //     }
    //     // Add new gallery images
    //     if ($request->hasFile('gallery')) {
    //         $gallery = $data['gallery'] ?? $product->gallery ?? [];
    //         foreach ($request->file('gallery') as $img) $gallery[] = $img->store('products/gallery', 'public');
    //         $data['gallery'] = $gallery;
    //     }
    //     $product->update($data);
    //     // Attach new colors
    //     if (!empty($data['colors'])) $product->colors()->syncWithoutDetaching($data['colors']);
    //     return $this->sendSimpleResponse($product->id, true, 'Product updated successfully.');
    // }

    public function destroy(Product $product): JsonResponse
    {
        // Delete thumbnail if exists
        if (!empty($product->thumbnail) && is_string($product->thumbnail)) {
            if (Storage::disk('public')->exists($product->thumbnail)) {
                Storage::disk('public')->delete($product->thumbnail);
            }
        }

        // Delete gallery images
        if (!empty($product->gallery) && is_array($product->gallery)) {
            foreach ($product->gallery as $img) {
                // handle only string paths
                if (is_string($img) && Storage::disk('public')->exists($img)) {
                    Storage::disk('public')->delete($img);
                }
            }
        }

        $product->delete();

        return $this->sendSimpleResponse($product->id, true, 'Product deleted successfully.');
    }
}
