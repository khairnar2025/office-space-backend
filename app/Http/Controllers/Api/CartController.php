<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\DeliveryPincode;
use App\Models\Product;
use App\Models\ShippingSetting;
use Carbon\Carbon;
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
     * Apply a coupon to the cart
     */
    /**
     * Apply or remove a coupon from the cart
     */
    public function applyCoupon(Request $request)
    {
        // Allow coupon_code to be nullable for "remove coupon" action
        $request->validate([
            'coupon_code' => 'nullable|string|exists:coupons,coupon_code',
        ]);

        $user = Auth::guard('sanctum')->user();
        $sessionId = $this->getSessionId($request);

        // Get cart for logged-in user or session
        $cart = $user
            ? Cart::with('items.product')->where('user_id', $user->id)->first()
            : Cart::with('items.product')->where('session_id', $sessionId)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return $this->sendError('Cart is empty.', 400);
        }

        $subtotal = $cart->items->sum(fn($item) => $item->price * $item->quantity);

        // --- Remove coupon logic ---
        if (empty($request->coupon_code)) {
            $cart->update([
                'coupon_code' => null,
                'coupon_discount' => 0,
            ]);

            return $this->sendResponse([
                'cart_id' => $cart->id,
                'coupon_code' => null,
                'discount' => 0,
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ], 'Coupon removed successfully.');
        }

        // --- Apply coupon logic ---
        $coupon = Coupon::where('coupon_code', $request->coupon_code)
            ->where('status', 1)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        if (!$coupon) {
            return $this->sendError('Coupon not valid or expired.', 400);
        }

        // Calculate discount
        $discount = $coupon->discount_type === 'fixed'
            ? $coupon->discount_percentage
            : ($subtotal * $coupon->discount_percentage / 100);

        // Update cart with coupon info
        $cart->update([
            'coupon_code' => $coupon->coupon_code,
            'coupon_discount' => $discount,
        ]);

        return $this->sendResponse([
            'cart_id' => $cart->id,
            'coupon_code' => $coupon->coupon_code,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - $discount),
        ], 'Coupon applied successfully.');
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

    //     $cart = $user
    //         ? Cart::where('user_id', $user->id)
    //         ->with('items.product:id,title', 'items.variant.color')
    //         ->first()
    //         : Cart::where('session_id', $sessionId)
    //         ->with('items.product:id,title', 'items.variant.color')
    //         ->first();

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

    //     // --- Calculate totals ---
    //     $subtotal = $cart->items->sum(fn($item) => $item->price * $item->quantity);

    //     $shippingSetting = ShippingSetting::first();
    //     $shippingCharge = ($subtotal < $shippingSetting->minimum_free_shipping_amount)
    //         ? $shippingSetting->shipping_cost
    //         : 0;

    //     $total = $subtotal + $shippingCharge;

    //     // --- Format the response cleanly ---
    //     $cartData = [
    //         'id' => $cart->id,
    //         'items' => $cart->items->map(fn($item) => [
    //             'id' => $item->id,
    //             'quantity' => $item->quantity,
    //             'price' => $item->price,
    //             'total' => $item->price * $item->quantity,
    //             'product' => [
    //                 'id' => $item->product->id,
    //                 'title' => $item->product->title,
    //             ],
    //             'variant' => [
    //                 'id' => $item->variant?->id,
    //                 'price' => $item->variant?->price,
    //                 'discount_price' => $item->variant?->discount_price,
    //                 'color_id' => $item->variant?->color_id,
    //                 'color_name' => $item->variant?->color_name,
    //                 'thumbnail_url' => $item->variant?->thumbnail_url,
    //             ],
    //         ]),
    //         'subtotal' => $subtotal,
    //         'shipping_charge' => $shippingCharge,
    //         'total' => $total,
    //     ];

    //     return response()
    //         ->json($this->sendResponse(
    //             ['cart' => $cartData, 'session_id' => $sessionId],
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
                    'special_coupon' => null,
                    'session_id' => $sessionId,
                ],
                'message' => 'No cart found for this session.',
            ])->header('X-Session-Id', $sessionId);
        }

        // --- Calculate subtotal ---
        $subtotal = $cart->items->sum(fn($item) => $item->price * $item->quantity);

        // --- Use coupon discount from cart if exists ---
        $discount = $cart->coupon_discount ?? 0;
        $appliedCouponCode = $cart->coupon_code ?? null;

        // --- Calculate shipping ---
        $shippingSetting = ShippingSetting::first();
        $shippingCharge = ($subtotal < $shippingSetting->minimum_free_shipping_amount)
            ? $shippingSetting->shipping_cost
            : 0;

        // --- Final total including discount ---
        $total = max(0, $subtotal - $discount + $shippingCharge);

        // --- Format cart items ---
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
            'discount' => $discount,
            'coupon_code' => $appliedCouponCode, // added applied coupon
            'shipping_charge' => $shippingCharge,
            'total' => $total,
        ];

        // --- Show active specialised coupon info ---
        $specialCoupon = Coupon::where('specialise', 1)
            ->active()
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        return response()
            ->json($this->sendResponse(
                [
                    'cart' => $cartData,
                    'special_coupon' => $specialCoupon ? [
                        'coupon_code' => $specialCoupon->coupon_code,
                        'discount_type' => $specialCoupon->discount_type,
                        'discount_value' => $specialCoupon->discount_percentage,
                        'description' => $specialCoupon->description,
                    ] : null,
                    'session_id' => $sessionId,
                ],
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
