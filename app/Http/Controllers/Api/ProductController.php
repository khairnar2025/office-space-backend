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
    /**
     * Display a listing of the products.
     */
    public function index(): JsonResponse
    {
        $products = Product::latest()->paginate(10);
        return $this->sendResponse(ProductResource::collection($products), 'Products fetched successfully.');
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle main thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }

        // Create the product
        $product = Product::create($data);

        // Handle gallery uploads
        if ($request->hasFile('product_images')) {
            $galleryPaths = [];
            foreach ($request->file('product_images') as $image) {
                $galleryPaths[] = $image->store('products/gallery', 'public');
            }
            $product->update(['gallery' => $galleryPaths]);
        }

        return $this->sendResponse(new ProductResource($product), 'Product created successfully.');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        return $this->sendResponse(new ProductResource($product), 'Product details retrieved successfully.');
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();

        // Remove thumbnail if flagged
        if ($request->boolean('remove_thumbnail') && $product->thumbnail) {
            Storage::disk('public')->delete($product->thumbnail);
            $data['thumbnail'] = null;
        }

        // Remove gallery if flagged
        if ($request->boolean('remove_gallery') && is_array($product->gallery)) {
            foreach ($product->gallery as $path) {
                Storage::disk('public')->delete($path);
            }
            $data['gallery'] = null;
        }

        // Replace thumbnail if new uploaded
        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('products/thumbnails', 'public');
        }

        // Replace gallery if new files uploaded
        if ($request->hasFile('product_images')) {
            $galleryPaths = [];
            foreach ($request->file('product_images') as $image) {
                $galleryPaths[] = $image->store('products/gallery', 'public');
            }
            $data['gallery'] = $galleryPaths;
        }

        $product->update($data);

        return $this->sendResponse(new ProductResource($product), 'Product updated successfully.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        // Delete thumbnail
        if ($product->thumbnail && Storage::disk('public')->exists($product->thumbnail)) {
            Storage::disk('public')->delete($product->thumbnail);
        }

        // Delete gallery
        if (is_array($product->gallery)) {
            foreach ($product->gallery as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $product->delete();

        return $this->sendSimpleResponse($product->id, true, 'Product deleted successfully.');
    }
}
