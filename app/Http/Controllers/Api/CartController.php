<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryPincode;
use App\Models\Product;
use App\Models\ShippingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CartController extends BaseController
{
    /**
     * Get or generate session_id from request.
     */
    private function getSessionId(Request $request)
    {
        $sessionId = $request->header('X-Session-Id');

        if (!$sessionId) {
            $sessionId = (string) Str::uuid();
        }

        return $sessionId;
    }

    /**
     * Get or create cart based on session or user
     */
    private function getOrCreateCart(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $sessionId = $this->getSessionId($request);

        if ($user) {
            // Authenticated user: find or create by user_id
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        } else {
            // Guest user: find or create by session_id
            $cart = Cart::firstOrCreate(['session_id' => $sessionId]);
        }

        return [$cart, $sessionId];
    }


    /**
     * Add item to cart
     */
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'product_id' => 'required|exists:products,id',
    //         'color_id'   => 'nullable|exists:colors,id',
    //         'quantity'   => 'required|integer|min:1',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors(), 422);
    //     }

    //     [$cart, $sessionId] = $this->getOrCreateCart($request);

    //     $product = Product::findOrFail($request->product_id);

    //     // Check if product already exists in cart
    //     $cartItem = CartItem::where('cart_id', $cart->id)
    //         ->where('product_id', $product->id)
    //         ->where('color_id', $request->color_id)
    //         ->first();

    //     if ($cartItem) {
    //         $cartItem->increment('quantity', $request->quantity);
    //     } else {
    //         $cartItem = CartItem::create([
    //             'cart_id'   => $cart->id,
    //             'product_id' => $product->id,
    //             'color_id'  => $request->color_id,
    //             'quantity'  => $request->quantity,
    //             'price'     => $product->discount_price ?? $product->price,
    //         ]);
    //     }

    //     $cart->load('items.product', 'items.color');

    //     return response()
    //         ->json($this->sendResponse(
    //             [
    //                 'cart' => $cart,
    //                 'session_id' => $sessionId
    //             ],
    //             'Product added to cart successfully.'
    //         ))
    //         ->header('X-Session-Id', $sessionId);
    // }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors(), 422);
        }

        [$cart, $sessionId] = $this->getOrCreateCart($request);

        $product = Product::findOrFail($request->product_id);

        // Determine price: variant price if variant_id provided
        $price = $product->discount_price ?? $product->price;
        $variantId = null;

        if ($request->variant_id) {
            $variant = $product->variants()->with('color')->findOrFail($request->variant_id);
            $price = $variant->discount_price ?? $variant->price;
            $variantId = $variant->id;
        }

        // Check if item already exists in cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->where('variant_id', $variantId)
            ->first();

        if ($cartItem) {
            $cartItem->increment('quantity', $request->quantity);
        } else {
            $cartItem = CartItem::create([
                'cart_id'            => $cart->id,
                'product_id'         => $product->id,
                'variant_id' => $variantId,
                'quantity'           => $request->quantity,
                'price'              => $price,
            ]);
        }

        $cart->load('items.product', 'items.color', 'items.variant');

        return response()
            ->json($this->sendResponse(
                [
                    'cart' => $cart,
                    'session_id' => $sessionId
                ],
                'Product added to cart successfully.'
            ))
            ->header('X-Session-Id', $sessionId);
    }


    // public function index(Request $request)
    // {
    //     $user = Auth::guard('sanctum')->user();
    //     $sessionId = $this->getSessionId($request);

    //     if ($user) {
    //         $cart = Cart::where('user_id', $user->id)
    //             ->with('items.product', 'items.color')
    //             ->first();
    //     } else {
    //         $cart = Cart::where('session_id', $sessionId)
    //             ->with('items.product', 'items.color')
    //             ->first();
    //     }

    //     // If no cart exists, return early
    //     if (!$cart) {
    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'cart' => null,
    //                 'session_id' => $sessionId,
    //             ],
    //             'message' => 'No cart found for this session.',
    //         ])->header('X-Session-Id', $sessionId);
    //     }

    //     // --- Add subtotal and shipping calculation ---
    //     $subtotal = $cart->items->sum(fn($item) => $item->product->final_price * $item->quantity);
    //     $shippingCost = 0;
    //     $pincode = $request->pincode;
    //     if ($pincode) {
    //         $delivery = DeliveryPincode::serviceable()->where('pincode', $pincode)->first();

    //         if ($delivery) {
    //             $shippingCost = $delivery->shipping_cost;
    //         }
    //     }

    //     $total = $subtotal + $shippingCost;

    //     // --- Send response with the extra fields ---
    //     return response()
    //         ->json($this->sendResponse(
    //             [
    //                 'cart' => $cart,
    //                 'subtotal' => $subtotal,
    //                 'shipping_charge' => $shippingCost,
    //                 'total' => $total,
    //                 'session_id' => $sessionId,
    //             ],
    //             'Cart retrieved successfully.'
    //         ))
    //         ->header('X-Session-Id', $sessionId);
    // }
    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $sessionId = $this->getSessionId($request);

        $cart = $user
            ? Cart::where('user_id', $user->id)
            ->with('items.product:id,title', 'items.variant.color')
            ->first()
            : Cart::where('session_id', $sessionId)
            ->with('items.product:id,title', 'items.variant.color')
            ->first();

        if (!$cart) {
            return response()->json([
                'success' => true,
                'data' => [
                    'cart' => null,
                    'session_id' => $sessionId,
                ],
                'message' => 'No cart found for this session.',
            ])->header('X-Session-Id', $sessionId);
        }

        // --- Calculate totals ---
        $subtotal = $cart->items->sum(fn($item) => $item->price * $item->quantity);

        $shippingSetting = ShippingSetting::first();
        $shippingCharge = ($subtotal < $shippingSetting->minimum_free_shipping_amount)
            ? $shippingSetting->shipping_cost
            : 0;

        $total = $subtotal + $shippingCharge;

        // --- Format the response cleanly ---
        $cartData = [
            'id' => $cart->id,
            'items' => $cart->items->map(fn($item) => [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->price * $item->quantity,
                'product' => [
                    'id' => $item->product->id,
                    'title' => $item->product->title,
                ],
                'variant' => [
                    'id' => $item->variant?->id,
                    'price' => $item->variant?->price,
                    'discount_price' => $item->variant?->discount_price,
                    'color_id' => $item->variant?->color_id,
                    'color_name' => $item->variant?->color_name,
                    'thumbnail_url' => $item->variant?->thumbnail_url,
                ],
            ]),
            'subtotal' => $subtotal,
            'shipping_charge' => $shippingCharge,
            'total' => $total,
        ];

        return response()
            ->json($this->sendResponse(
                ['cart' => $cartData, 'session_id' => $sessionId],
                'Cart retrieved successfully.'
            ))
            ->header('X-Session-Id', $sessionId);
    }


    /**
     * Update cart item quantity
     */
    public function update(Request $request, CartItem $item)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);
        $item->update(['quantity' => $request->quantity]);

        $cart = $item->cart()->with('items.product', 'items.color', 'items.variant')->first();

        return $this->sendResponse($cart, 'Cart item updated successfully.');
    }

    /**
     * Remove item from cart
     */
    public function destroy(CartItem $item)
    {
        $cart = $item->cart;
        $item->delete();

        $cart->load('items.product', 'items.color', 'items.variant');

        return $this->sendResponse($cart, 'Cart item removed successfully.');
    }

    /**
     * Clear all items
     */
    public function clear(Request $request)
    {
        [$cart, $sessionId] = $this->getOrCreateCart($request);

        $cart->items()->delete();

        return $this->sendResponse([], 'Cart cleared successfully.');
    }
}
