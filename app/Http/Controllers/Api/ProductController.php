<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Helpers\SkuGenerator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 12);
        $products = Product::with('variants.color', 'deliveryPincodes', 'category')->latest()->paginate($perPage);
        return ProductResource::collection($products);
    }

    /**
     * Store product + product gallery + variants (variant thumbnail + variant gallery)
     *
     * Payload (multipart/form-data):
     * - title, description, category_id, status
     * - delivery_pincodes[] (ids)
     * - thumbnail (file) -- optional product-level thumbnail
     * - gallery_images[] (files) -- optional product-level gallery
     *
     * - variants (JSON or form fields): variants[] or variants JSON string
     * - variant files: variant_thumbnail[0], variant_gallery[0][], variant_thumbnail[1], ...
     */
    public function store(StoreProductRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $product = Product::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'],
                'status' => $data['status'] ?? true,
            ]);

            // product-level files (optional) - store under product folder
            $productFolder = "products/{$product->id}";

            if ($request->hasFile('thumbnail')) {
                $productThumb = $request->file('thumbnail')->store("$productFolder/thumbnail", 'public');
                // if your products table has thumbnail column, uncomment:
                // $product->thumbnail = $productThumb;
                // $product->save();
            }

            // product gallery images
            $galleryPaths = [];
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $img) {
                    if ($img->isValid()) {
                        $galleryPaths[] = $img->store("$productFolder/gallery", 'public');
                    }
                }
                // if product table has gallery json column:
                // $product->gallery = $galleryPaths;
                // $product->save();
            }

            // sync delivery pincodes pivot
            if (!empty($data['delivery_pincodes'])) {
                $product->deliveryPincodes()->sync($data['delivery_pincodes']);
            }

            // VARIANTS creation
            // variants might come as array in $data['variants'] or as JSON string (if using form-data)
            $variantsInput = $data['variants'];

            // handle variant files arrays indexed by variant position
            $variantThumbnails = $request->file('variant_thumbnail', []); // array keyed by index
            $variantGalleries = $request->file('variant_gallery', []); // array keyed by index -> array of files

            foreach ($variantsInput as $index => $v) {
                $sku = $v['sku'] ?? SkuGenerator::generate($product->id, $v['color_id'] ?? null);

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'color_id' => $v['color_id'] ?? null,
                    'price' => $v['price'],
                    'discount_price' => $v['discount_price'] ?? null,
                    'quantity' => $v['quantity'] ?? 0,
                    'in_stock' => ($v['quantity'] ?? 0) > 0,
                    'thumbnail' => null,
                    'gallery' => null,
                ]);

                $variantFolder = "products/{$product->id}/variants/{$variant->id}";

                // variant thumbnail
                if (isset($variantThumbnails[$index]) && $variantThumbnails[$index]->isValid()) {
                    $tPath = $variantThumbnails[$index]->store("$variantFolder/thumbnail", 'public');
                    $variant->thumbnail = $tPath;
                }

                // variant gallery
                $vGalleryPaths = [];
                if (isset($variantGalleries[$index]) && is_array($variantGalleries[$index])) {
                    foreach ($variantGalleries[$index] as $img) {
                        if ($img->isValid()) {
                            $vGalleryPaths[] = $img->store("$variantFolder/gallery", 'public');
                        }
                    }
                }

                if (!empty($vGalleryPaths)) $variant->gallery = $vGalleryPaths;
                $variant->save();
            }

            DB::commit();
            $product->load('variants.color', 'deliveryPincodes', 'category');

            return (new ProductResource($product))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Product store failed: ' . $e->getMessage());
            return response()->json(['message' => 'Product creation failed.'], 500);
        }
    }

    public function show(Product $product)
    {
        $product->load('variants.color', 'deliveryPincodes', 'category');
        return new ProductResource($product);
    }

    /**
     * Update product + variants
     *
     * Supports:
     * - add/update variants (variants array each item may contain id)
     * - delete variants via delete_variants[] (ids)
     * - delete product gallery images via delete_gallery_images[] (filenames/paths)
     * - delete variant gallery images via delete_variant_gallery_images[<variant_id>][] keys
     * - replace product thumbnail/gallery if new files provided
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $product->update([
                'title' => $data['title'] ?? $product->title,
                'description' => $data['description'] ?? $product->description,
                'category_id' => $data['category_id'] ?? $product->category_id,
                'status' => $data['status'] ?? $product->status,
            ]);

            $productFolder = "products/{$product->id}";

            // Product thumbnail replace
            if ($request->hasFile('thumbnail')) {
                // delete old if exists and if product table has thumbnail column
                // Storage::disk('public')->delete($product->thumbnail);
                $thumbPath = $request->file('thumbnail')->store("$productFolder/thumbnail", 'public');
                // $product->thumbnail = $thumbPath; $product->save();
            }

            // Product gallery add (append)
            if ($request->hasFile('gallery_images')) {
                $existing = $product->gallery ?? [];
                foreach ($request->file('gallery_images') as $img) {
                    if ($img->isValid()) {
                        $existing[] = $img->store("$productFolder/gallery", 'public');
                    }
                }
                if (!empty($existing)) {
                    // if gallery column present on product
                    // $product->gallery = $existing; $product->save();
                }
            }

            // delete product gallery images if filenames provided
            if ($request->filled('delete_gallery_images')) {
                $toDelete = $request->input('delete_gallery_images', []);
                // expects filenames or stored paths; we will check in storage/product folder
                foreach ($toDelete as $file) {
                    $path = ltrim($file, '/'); // sanitize
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                        // remove from product->gallery if you store gallery
                        // $product->gallery = array_values(array_diff($product->gallery ?? [], [$path]));
                    }
                }
                // if saved, $product->save();
            }

            // Sync delivery pincodes (if provided)
            if (array_key_exists('delivery_pincodes', $data)) {
                $product->deliveryPincodes()->sync($data['delivery_pincodes'] ?? []);
            }

            // Delete whole variants if requested
            if (!empty($data['delete_variants'])) {
                foreach ($data['delete_variants'] as $vid) {
                    $variant = ProductVariant::where('id', $vid)->where('product_id', $product->id)->first();
                    if ($variant) {
                        // remove files
                        if ($variant->thumbnail && Storage::disk('public')->exists($variant->thumbnail)) {
                            Storage::disk('public')->delete($variant->thumbnail);
                        }
                        if (!empty($variant->gallery) && is_array($variant->gallery)) {
                            foreach ($variant->gallery as $p) {
                                if (Storage::disk('public')->exists($p)) Storage::disk('public')->delete($p);
                            }
                        }
                        $variant->delete();
                    }
                }
            }

            // handle variant file arrays indexed by variant position
            $variantThumbnails = $request->file('variant_thumbnail', []); // may contain files for positions
            $variantGalleries = $request->file('variant_gallery', []); // may contain arrays

            // Variants add/update
            if (!empty($data['variants'])) {
                foreach ($data['variants'] as $index => $v) {
                    // update existing variant
                    if (!empty($v['id'])) {
                        $variant = ProductVariant::where('id', $v['id'])->where('product_id', $product->id)->first();
                        if (!$variant) continue;

                        $variant->color_id = $v['color_id'] ?? $variant->color_id;
                        if (array_key_exists('price', $v)) $variant->price = $v['price'];
                        if (array_key_exists('discount_price', $v)) $variant->discount_price = $v['discount_price'];
                        if (array_key_exists('quantity', $v)) {
                            $variant->quantity = $v['quantity'];
                            $variant->in_stock = ($v['quantity'] > 0);
                        }

                        // replace thumbnail if new file exists at same index
                        if (isset($variantThumbnails[$index]) && $variantThumbnails[$index]->isValid()) {
                            // delete old
                            if ($variant->thumbnail && Storage::disk('public')->exists($variant->thumbnail)) {
                                Storage::disk('public')->delete($variant->thumbnail);
                            }
                            $tPath = $variantThumbnails[$index]->store("products/{$product->id}/variants/{$variant->id}/thumbnail", 'public');
                            $variant->thumbnail = $tPath;
                        }

                        // variant gallery replace or append? We'll replace if provided
                        if (isset($variantGalleries[$index]) && is_array($variantGalleries[$index])) {
                            // delete old gallery files
                            if (!empty($variant->gallery) && is_array($variant->gallery)) {
                                foreach ($variant->gallery as $p) {
                                    if (Storage::disk('public')->exists($p)) Storage::disk('public')->delete($p);
                                }
                            }
                            $newG = [];
                            foreach ($variantGalleries[$index] as $img) {
                                if ($img->isValid()) {
                                    $newG[] = $img->store("products/{$product->id}/variants/{$variant->id}/gallery", 'public');
                                }
                            }
                            $variant->gallery = $newG;
                        }

                        $variant->save();
                    } else {
                        // create new variant
                        $variant = ProductVariant::create([
                            'product_id' => $product->id,
                            'color_id' => $v['color_id'] ?? null,
                            'price' => $v['price'],
                            'discount_price' => $v['discount_price'] ?? null,
                            'quantity' => $v['quantity'] ?? 0,
                            'in_stock' => ($v['quantity'] ?? 0) > 0,
                        ]);

                        // files for new variant at same index
                        if (isset($variantThumbnails[$index]) && $variantThumbnails[$index]->isValid()) {
                            $tPath = $variantThumbnails[$index]->store("products/{$product->id}/variants/{$variant->id}/thumbnail", 'public');
                            $variant->thumbnail = $tPath;
                        }

                        $newG = [];
                        if (isset($variantGalleries[$index]) && is_array($variantGalleries[$index])) {
                            foreach ($variantGalleries[$index] as $img) {
                                if ($img->isValid()) {
                                    $newG[] = $img->store("products/{$product->id}/variants/{$variant->id}/gallery", 'public');
                                }
                            }
                        }
                        if (!empty($newG)) $variant->gallery = $newG;
                        $variant->save();
                    }
                }
            }

            // Delete variant-specific gallery images if asked:
            // expects keys like: delete_variant_gallery_images[123][]=path1&delete_variant_gallery_images[123][]=path2
            $deleteVariantGallery = $request->input('delete_variant_gallery_images', []);
            if (is_array($deleteVariantGallery)) {
                foreach ($deleteVariantGallery as $vid => $files) {
                    $variant = ProductVariant::where('id', $vid)->where('product_id', $product->id)->first();
                    if (!$variant || empty($files) || !is_array($files)) continue;
                    $remaining = $variant->gallery ?? [];
                    foreach ($files as $f) {
                        $path = ltrim($f, '/');
                        if (Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->delete($path);
                        }
                        // remove from gallery array
                        $remaining = array_values(array_diff($remaining, [$path]));
                    }
                    $variant->gallery = $remaining;
                    $variant->save();
                }
            }

            DB::commit();
            $product->load('variants.color', 'deliveryPincodes', 'category');
            return new ProductResource($product);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Product update failed: ' . $e->getMessage());
            return response()->json(['message' => 'Product update failed.'], 500);
        }
    }

    public function destroy(Product $product)
    {
        // delete all variant files
        foreach ($product->variants as $variant) {
            if ($variant->thumbnail && Storage::disk('public')->exists($variant->thumbnail)) {
                Storage::disk('public')->delete($variant->thumbnail);
            }
            if (!empty($variant->gallery) && is_array($variant->gallery)) {
                foreach ($variant->gallery as $p) {
                    if (Storage::disk('public')->exists($p)) Storage::disk('public')->delete($p);
                }
            }
        }

        // delete product folder
        $folder = "products/{$product->id}";
        if (Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->deleteDirectory($folder);
        }

        $product->delete();
        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
