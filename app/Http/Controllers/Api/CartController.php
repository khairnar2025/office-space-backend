<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
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
        $user = $request->user();
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'color_id'   => 'nullable|exists:colors,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        [$cart, $sessionId] = $this->getOrCreateCart($request);

        $product = Product::findOrFail($request->product_id);

        // Check if product already exists in cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->where('color_id', $request->color_id)
            ->first();

        if ($cartItem) {
            $cartItem->increment('quantity', $request->quantity);
        } else {
            $cartItem = CartItem::create([
                'cart_id'   => $cart->id,
                'product_id' => $product->id,
                'color_id'  => $request->color_id,
                'quantity'  => $request->quantity,
                'price'     => $product->discount_price ?? $product->price,
            ]);
        }

        $cart->load('items.product', 'items.color');

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

    /**
     * View cart items
     */
    // public function index(Request $request)
    // {
    //     $user = Auth::guard('sanctum')->user();
    //     if ($user) {
    //         // Use user_id
    //         $cart = Cart::where('user_id', $user->id)
    //             ->with('items.product', 'items.color')
    //             ->first();
    //     } else {
    //         // Guest user
    //         $sessionId = $this->getSessionId($request);
    //         $cart = Cart::where('session_id', $sessionId)
    //             ->with('items.product', 'items.color')
    //             ->first();
    //     }

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

    //     return response()
    //         ->json($this->sendResponse(
    //             [
    //                 'cart' => $cart,
    //                 'session_id' => $sessionId,
    //             ],
    //             'Cart retrieved successfully.'
    //         ))
    //         ->header('X-Session-Id', $sessionId);
    // }

    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        // Get sessionId anyway (needed for response header & data)
        $sessionId = $this->getSessionId($request);

        if ($user) {
            // Use user_id
            $cart = Cart::where('user_id', $user->id)
                ->with('items.product', 'items.color')
                ->first();
        } else {
            // Guest user
            $cart = Cart::where('session_id', $sessionId)
                ->with('items.product', 'items.color')
                ->first();
        }

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

        return response()
            ->json($this->sendResponse(
                [
                    'cart' => $cart,
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

        $cart = $item->cart()->with('items.product', 'items.color')->first();

        return $this->sendResponse($cart, 'Cart item updated successfully.');
    }

    /**
     * Remove item from cart
     */
    public function destroy(CartItem $item)
    {
        $cart = $item->cart;
        $item->delete();

        $cart->load('items.product', 'items.color');

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
