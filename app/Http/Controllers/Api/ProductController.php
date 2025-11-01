<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreProductRequest;
use App\Http\Requests\Api\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends BaseController
{
    public function index()
    {
        $products = Product::with(['variants.color', 'deliveryPincodes', 'category'])
            ->latest()
            ->paginate(10);

        return $this->sendResponse(
            ProductResource::collection($products),
            'Products retrieved successfully'
        );
    }

    public function publicIndex()
    {
        $products = Product::with(['variants.color', 'deliveryPincodes', 'category'])
            ->active()
            ->latest()
            ->paginate(10);

        return $this->sendResponse(
            ProductResource::collection($products),
            'Products retrieved successfully'
        );
    }

    public function publicShow($id)
    {
        $product = Product::with(['variants.color', 'deliveryPincodes', 'category'])->find($id);

        if (!$product || !$product->status) {
            return $this->sendError('Not available', 404);
        }

        return $this->sendResponse(new ProductResource($product), 'Product retrieved successfully');
    }
    public function show($id)
    {
        $product = Product::with(['variants.color', 'deliveryPincodes', 'category'])->find($id);

        if (!$product || !$product->status) {
            return $this->sendError('Not available', 404);
        }

        return $this->sendResponse(new ProductResource($product), 'Product retrieved successfully');
    }
    public function store(StoreProductRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            $product = Product::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'status' => $data['status'] ?? true,
            ]);

            $productFolder = "products/{$product->id}";

            // ✅ Product Thumbnail Upload
            if ($request->hasFile('thumbnail')) {
                $product->thumbnail = $request->file('thumbnail')
                    ->store("$productFolder/thumbnail", 'public');
                $product->save();
            }

            // ✅ Product Gallery
            if ($request->hasFile('gallery_images')) {
                $gallery = [];
                foreach ($request->file('gallery_images') as $img) {
                    $gallery[] = $img->store("$productFolder/gallery", 'public');
                }
                $product->gallery = $gallery;
                $product->save();
            }

            // ✅ Delivery Pincodes Sync
            if (!empty($data['delivery_pincodes'])) {
                $product->deliveryPincodes()->sync($data['delivery_pincodes']);
            }

            // ✅ Variants
            foreach ($data['variants'] as $index => $variantData) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'color_id' => $variantData['color_id'],
                    'price' => $variantData['price'],
                    'discount_price' => $variantData['discount_price'] ?? null,
                    'quantity' => $variantData['quantity'] ?? 0,
                    'in_stock' => ($variantData['quantity'] ?? 0) > 0,
                ]);

                $variantFolder = "$productFolder/variants/{$variant->id}";

                // ✅ Variant Thumbnail Upload
                if ($request->hasFile("variants.$index.thumbnail")) {
                    $variant->thumbnail = $request->file("variants.$index.thumbnail")
                        ->store("$variantFolder/thumbnail", 'public');
                }

                // ✅ Variant Gallery Upload
                if ($request->hasFile("variants.$index.gallery")) {
                    $paths = [];
                    foreach ($request->file("variants.$index.gallery") as $img) {
                        $paths[] = $img->store("$variantFolder/gallery", 'public');
                    }
                    $variant->gallery = $paths;
                }

                $variant->save();
            }

            DB::commit();
            return $this->sendResponse(
                new ProductResource($product->load('variants.color', 'deliveryPincodes', 'category')),
                'Product created successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Store Product Error: ' . $e->getMessage());
            return $this->sendError('Failed to create product', 500);
        }
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $productFolder = "products/{$product->id}";

            // ✅ Update fields
            $product->update($request->only(['title', 'description', 'status', 'category_id']));

            // ✅ Sync delivery pincodes
            if (isset($data['delivery_pincodes'])) {
                $product->deliveryPincodes()->sync($data['delivery_pincodes']);
            }
            // Delete specific pincodes if provided
            if (!empty($data['delete_pincodes'])) {
                $product->deliveryPincodes()->detach($data['delete_pincodes']);
            }
            // ✅ Delete entire variants
            if (!empty($data['delete_variants'])) {
                ProductVariant::whereIn('id', $data['delete_variants'])->delete();
            }

            // ✅ Variants Update/Add
            if (!empty($data['variants'])) {
                foreach ($data['variants'] as $vIndex => $variantData) {

                    $variant = !empty($variantData['id'])
                        ? ProductVariant::find($variantData['id'])
                        : new ProductVariant(['product_id' => $product->id]);

                    $variant->fill([
                        'color_id' => $variantData['color_id'] ?? $variant->color_id,
                        'price' => $variantData['price'] ?? $variant->price,
                        'discount_price' => $variantData['discount_price'] ?? $variant->discount_price,
                        'quantity' => $variantData['quantity'] ?? $variant->quantity,
                        'in_stock' => isset($variantData['quantity'])
                            ? $variantData['quantity'] > 0
                            : $variant->in_stock,
                        'status' => array_key_exists('status', $variantData)
                            ? $variantData['status']
                            : $variant->status,
                    ]);


                    $variant->save();
                    $variantFolder = "$productFolder/variants/{$variant->id}";

                    // ✅ DELETE Thumbnail
                    if ($request->input("delete_variant_thumbnail.$vIndex") == 1) {
                        if ($variant->thumbnail) {
                            Storage::disk('public')->delete($variant->thumbnail);
                        }
                        $variant->thumbnail = null;
                    }

                    // ✅ DELETE Gallery by index
                    if (!empty($data['delete_variant_gallery_images'][$vIndex])) {
                        $gallery = $variant->gallery ?? [];
                        foreach ($data['delete_variant_gallery_images'][$vIndex] as $imgIndex) {
                            if (isset($gallery[$imgIndex])) {
                                Storage::disk('public')->delete($gallery[$imgIndex]);
                                unset($gallery[$imgIndex]);
                            }
                        }
                        $variant->gallery = array_values($gallery);
                    }

                    // ✅ Replace Thumbnail
                    if ($request->hasFile("variants.$vIndex.thumbnail")) {
                        if ($variant->thumbnail) {
                            Storage::disk('public')->delete($variant->thumbnail);
                        }
                        $variant->thumbnail = $request->file("variants.$vIndex.thumbnail")
                            ->store("$variantFolder/thumbnail", 'public');
                    }

                    // ✅ Append new gallery images
                    if ($request->hasFile("variants.$vIndex.gallery")) {
                        $gallery = $variant->gallery ?? [];
                        foreach ($request->file("variants.$vIndex.gallery") as $img) {
                            $gallery[] = $img->store("$variantFolder/gallery", 'public');
                        }
                        $variant->gallery = array_values($gallery);
                    }

                    $variant->save();
                }
            }

            DB::commit();

            return $this->sendResponse(
                new ProductResource($product->load('variants.color', 'deliveryPincodes', 'category')),
                'Product updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update Product Error: ' . $e->getMessage());
            return $this->sendError('Failed to update product', 500);
        }
    }

    public function destroy(Product $product)
    {
        Storage::disk('public')->deleteDirectory("products/{$product->id}");
        $product->variants()->delete();
        $product->delete();

        return $this->sendSimpleResponse($product->id, true, 'Product deleted successfully');
    }
}
