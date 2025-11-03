<?php

// namespace App\Http\Controllers\Api;

// use Razorpay\Api\Api;
// use Illuminate\Http\Request;
// use App\Models\Order;
// use App\Models\OrderItem;
// use App\Models\Cart;
// use Illuminate\Support\Facades\DB;

// class RazorpayController extends BaseController
// {
//     public function createOrder(Request $request)
//     {
//         $request->validate([
//             'amount'   => 'required|numeric|min:1',
//             'currency' => 'required|string'
//         ]);
//         try {
//             $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

//             $order = $api->order->create([
//                 'amount'   => $request->amount * 100,
//                 'currency' => $request->currency,
//                 'receipt'  => $request->receipt ?? 'rcpt_' . time()
//             ]);
//             return $this->sendResponse(['order' => $order->toArray()], 'Order created successfully');
//         } catch (\Exception $e) {
//             return $this->sendError($e->getMessage(), 500);
//         }
//     }

//     public function verifyPayment(Request $request)
//     {
//         $request->validate([
//             'razorpay_order_id'   => 'required|string',
//             'razorpay_payment_id' => 'required|string',
//             'razorpay_signature'  => 'required|string',
//             'shipping'            => 'required|array',
//         ]);

//         try {
//             $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
//             $generatedSignature = hash_hmac(
//                 'sha256',
//                 $request->razorpay_order_id . "|" . $request->razorpay_payment_id,
//                 config('services.razorpay.secret')
//             );

//             if ($generatedSignature !== $request->razorpay_signature) {
//                 return $this->sendError('Invalid Signature', 400);
//             }
//             $payment = $api->payment->fetch($request->razorpay_payment_id);
//             if ($payment->status !== 'captured') {
//                 return $this->sendError('Payment not captured', 400);
//             }
//             $user = auth()->user();
//             $cart = $user
//                 ? Cart::where('user_id', $user->id)->first()
//                 : Cart::where('session_id', $request->header('X-Session-Id'))->first();

//             if (!$cart) {
//                 return $this->sendError('Cart not found', 404);
//             }

//             DB::beginTransaction();
//             $order = Order::create(array_merge($request->shipping, [
//                 'user_id'             => $user->id ?? null,
//                 'session_id'          => $cart->session_id,
//                 'razorpay_order_id'   => $request->razorpay_order_id,
//                 'razorpay_payment_id' => $request->razorpay_payment_id,
//                 'razorpay_signature'  => $request->razorpay_signature,
//                 'total_amount'        => $cart->items->sum(fn($i) => $i->subtotal),
//                 'currency'            => 'INR',
//                 'status'              => 'paid'
//             ]));
//             foreach ($cart->items as $item) {
//                 OrderItem::create([
//                     'order_id'   => $order->id,
//                     'product_id' => $item->product_id,
//                     'color_id'   => $item->color_id,
//                     'quantity'   => $item->quantity,
//                     'price'      => $item->product->final_price
//                 ]);
//             }
//             $cart->items()->delete();
//             DB::commit();
//             return $this->sendResponse(['order_id' => $order->id], 'Payment Verified & Order Placed');
//         } catch (\Exception $e) {
//             DB::rollBack();
//             return $this->sendError($e->getMessage(), 500);
//         }
//     }
// }
namespace App\Http\Controllers\Api;

use Razorpay\Api\Api;
use Illuminate\Http\Request;
use App\Models\{Order, OrderItem, Cart, DeliveryPincode, ShippingSetting, User, Coupon};
use Illuminate\Support\Facades\Mail;
use App\Mail\SystemNotificationMail;
use Illuminate\Support\Facades\DB;
use PDF;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class RazorpayController extends BaseController
{
    private function getSessionId(Request $request)
    {
        $sessionId = $request->header('X-Session-Id');

        if (!$sessionId) {
            $sessionId = (string) Str::uuid();
        }

        return $sessionId;
    }
    /**
     * Create Razorpay Order
     */
    // public function createOrder(Request $request)
    // {
    //     $request->validate([
    //         'amount'   => 'required|numeric|min:1',
    //         'currency' => 'required|string',
    //         'pincode'  => 'required|string|max:10',
    //     ]);

    //     $user = Auth::guard('sanctum')->user();
    //     $sessionId = $this->getSessionId($request);

    //     // Get the cart
    //     $cart = $user
    //         ? Cart::where('user_id', $user->id)->with('items.product')->first()
    //         : Cart::where('session_id', $sessionId)->with('items.product')->first();

    //     if (!$cart || $cart->items->isEmpty()) {
    //         return $this->sendError('Cart is empty.', 400);
    //     }

    //     // Calculate subtotal
    //     $subtotal = $cart->items->sum(fn($item) => $item->product->final_price * $item->quantity);

    //     // Get shipping
    //     $delivery = DeliveryPincode::serviceable()->where('pincode', $request->pincode)->first();
    //     if (!$delivery) return $this->sendError('This pincode is not serviceable.', 400);

    //     $totalAmount = $subtotal + $delivery->shipping_cost;

    //     // Create Razorpay order
    //     $api = new \Razorpay\Api\Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    //     $order = $api->order->create([
    //         'amount' => $totalAmount * 100, // amount in paise
    //         'currency' => $request->currency,
    //         'receipt' => 'rcpt_' . time(),
    //         'notes' => [
    //             'shipping_cost' => $delivery->shipping_cost,
    //             'pincode' => $request->pincode
    //         ]
    //     ]);

    //     return $this->sendResponse([
    //         'order' => $order->toArray(),
    //         'subtotal' => $subtotal,
    //         'shipping_cost' => $delivery->shipping_cost,
    //         'total' => $totalAmount
    //     ], 'Order created successfully');
    // }

    // ---------------- CREATE ORDER ----------------
    public function createOrder(Request $request)
    {
        // Step 1: Validate input
        $request->validate([
            'amount'   => 'required|numeric|min:1',
            'currency' => 'required|string',
            'pincode'  => 'required|string|max:10',
        ]);

        $user = Auth::guard('sanctum')->user();
        $sessionId = $this->getSessionId($request);

        // Step 2: Fetch cart with variant + product
        $cart = $user
            ? Cart::where('user_id', $user->id)
            ->with(['items.variant', 'items.product'])
            ->first()
            : Cart::where('session_id', $sessionId)
            ->with(['items.variant', 'items.product'])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            \Log::warning('Cart empty', ['user_id' => $user->id ?? null, 'session_id' => $sessionId]);
            return $this->sendError('Cart is empty.', 400);
        }

        // Step 3: Calculate subtotal using product variants
        $subtotal = $cart->items->sum(function ($item) {
            $variant = $item->variant;

            if (!$variant) {
                \Log::warning('Cart item missing variant', ['cart_item_id' => $item->id]);
                return 0;
            }

            // Use discount price if available, otherwise base price
            $price = $variant->discount_price ?? $variant->price;
            return $price * $item->quantity;
        });

        if ($subtotal <= 0) {
            \Log::warning('Cart subtotal is zero', ['cart_id' => $cart->id, 'subtotal' => $subtotal]);
            return $this->sendError('Cart subtotal is zero. Please add valid products.', 400);
        }

        // Step 4: Apply coupon discount
        $discount = 0;
        if ($cart->coupon_code) {
            $coupon = Coupon::where('coupon_code', $cart->coupon_code)
                ->where('status', 1)
                ->first();

            if ($coupon) {
                $discount = $coupon->discount_type === 'fixed'
                    ? $coupon->discount_percentage
                    : ($subtotal * $coupon->discount_percentage / 100);
            }
        }

        $subtotalAfterDiscount = max(0, $subtotal - $discount);

        // Step 5: Fetch shipping settings
        $shippingSetting = ShippingSetting::first();
        if (!$shippingSetting) {
            \Log::error('Shipping settings not configured');
            return $this->sendError('Shipping settings are not configured.', 500);
        }

        // Step 6: Calculate shipping cost
        $shippingCost = $subtotalAfterDiscount < $shippingSetting->minimum_free_shipping_amount
            ? $shippingSetting->shipping_cost
            : 0;

        $totalAmount = $subtotalAfterDiscount + $shippingCost;

        // Step 7: Validate total amount
        if ($totalAmount <= 0) {
            \Log::warning('Attempt to create order with zero total', [
                'user_id' => $user->id ?? null,
                'cart_id' => $cart->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount
            ]);

            return $this->sendError('Cart total is zero. Please add valid products before checkout.', 400);
        }

        \Log::info('Cart debug info', [
            'user_id' => $user->id ?? null,
            'session_id' => $cart->session_id,
            'cart_items_count' => $cart->items->count(),
            'cart_subtotal' => $subtotal,
            'coupon_code' => $cart->coupon_code ?? null,
            'discount' => $discount,
            'subtotal_after_discount' => $subtotalAfterDiscount,
            'shipping_cost' => $shippingCost,
            'total_amount' => $totalAmount
        ]);

        // Step 8: Create Razorpay order
        try {
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            $order = $api->order->create([
                'amount' => $totalAmount * 100, // amount in paise
                'currency' => $request->currency,
                'receipt' => 'rcpt_' . time(),
                'notes' => [
                    'shipping_cost' => $shippingCost,
                    'pincode' => $request->pincode,
                    'coupon_code' => $cart->coupon_code ?? null,
                    'coupon_discount' => $discount,
                ]
            ]);
        } catch (\Razorpay\Api\Errors\BadRequestError $e) {
            \Log::error('Razorpay BadRequestError', ['message' => $e->getMessage()]);
            if (str_contains($e->getMessage(), 'Order amount less than minimum amount allowed')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your order amount is too low to process payment. Please add more items.',
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed. Please try again.',
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Razorpay general exception', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating payment order.',
            ], 500);
        }

        // Step 9: Return success response
        return $this->sendResponse([
            'order' => $order->toArray(),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_cost' => $shippingCost,
            'total' => $totalAmount
        ], 'Order created successfully');
    }




    /**
     * Verify Razorpay Payment
     */
    // public function verifyPayment(Request $request)
    // {
    //     $request->validate([
    //         'razorpay_order_id'   => 'required|string',
    //         'razorpay_payment_id' => 'required|string',
    //         'razorpay_signature'  => 'required|string',
    //         'shipping'            => 'required|array',
    //         'shipping_cost'       => 'nullable|numeric|min:0',
    //     ]);

    //     try {
    //         $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

    //         // ✅ Changed: Use Razorpay's official utility method for signature verification
    //         $attributes = [
    //             'razorpay_order_id' => $request->razorpay_order_id,
    //             'razorpay_payment_id' => $request->razorpay_payment_id,
    //             'razorpay_signature' => $request->razorpay_signature,
    //         ];

    //         try {
    //             $api->utility->verifyPaymentSignature($attributes);
    //         } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
    //             return $this->sendError('Invalid Signature: ' . $e->getMessage(), 400);
    //         }

    //         // ✅ Fetch payment from Razorpay to ensure it is captured
    //         $payment = $api->payment->fetch($request->razorpay_payment_id);
    //         if ($payment->status !== 'captured') {
    //             return $this->sendError('Payment not captured', 400);
    //         }

    //         // ✅ Get user or guest cart
    //         $user = auth()->user();
    //         $cart = $user
    //             ? Cart::where('user_id', $user->id)->first()
    //             : Cart::where('session_id', $request->header('X-Session-Id'))->first();

    //         if (!$cart) {
    //             return $this->sendError('Cart not found', 404);
    //         }

    //         DB::beginTransaction();

    //         // ✅ Create order
    //         $order = Order::create(array_merge($request->shipping, [
    //             'user_id'             => $user->id ?? null,
    //             'session_id'          => $cart->session_id,
    //             'razorpay_order_id'   => $request->razorpay_order_id,
    //             'razorpay_payment_id' => $request->razorpay_payment_id,
    //             'razorpay_signature'  => $request->razorpay_signature,
    //             'total_amount' => $cart->items->sum(function ($item) {
    //                 return ($item->product->final_price * $item->quantity);
    //             }),

    //             'currency'            => 'INR',
    //             'status'              => 'paid'
    //         ]));

    //         // ✅ Create order items
    //         foreach ($cart->items as $item) {
    //             OrderItem::create([
    //                 'order_id'   => $order->id,
    //                 'product_id' => $item->product_id,
    //                 'color_id'   => $item->color_id,
    //                 'quantity'   => $item->quantity,
    //                 'price'      => $item->product->final_price
    //             ]);
    //         }

    //         // ✅ Clear cart
    //         $cart->items()->delete();

    //         DB::commit();

    //         return $this->sendResponse(['order_id' => $order->id], 'Payment Verified & Order Placed');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return $this->sendError($e->getMessage(), 500);
    //     }
    // }
    // ---------------- VERIFY PAYMENT ----------------
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
            'shipping'            => 'required|array',
            'shipping_cost'       => 'nullable|numeric|min:0',
        ]);

        try {
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

            // Step 1: Verify Razorpay signature
            try {
                $api->utility->verifyPaymentSignature([
                    'razorpay_order_id'   => $request->razorpay_order_id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature'  => $request->razorpay_signature,
                ]);
            } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
                return $this->sendError('Invalid Signature', 400);
            }

            // Step 2: Fetch payment info from Razorpay
            $payment = $api->payment->fetch($request->razorpay_payment_id);
            if ($payment->status !== 'captured') {
                return $this->sendError('Payment not captured', 400);
            }

            // Step 3: Fetch cart with product variant and product
            $user = Auth::guard('sanctum')->user();
            $cart = $user
                ? Cart::with(['items.variant', 'items.product'])->where('user_id', $user->id)->first()
                : Cart::with(['items.variant', 'items.product'])->where('session_id', $request->header('X-Session-Id'))->first();

            if (!$cart || $cart->items->isEmpty()) {
                return $this->sendError('Cart empty or not found', 404);
            }

            DB::beginTransaction();

            // Step 4: Calculate subtotal based on variant pricing
            $subtotal = $cart->items->sum(function ($item) {
                $variant = $item->variant;
                if (!$variant) {
                    \Log::warning('Cart item missing variant', ['cart_item_id' => $item->id]);
                    return 0;
                }

                $price = $variant->discount_price ?? $variant->price;
                return $price * $item->quantity;
            });

            // Step 5: Apply coupon discount
            $discount = 0;
            if ($cart->coupon_code) {
                $coupon = Coupon::where('coupon_code', $cart->coupon_code)
                    ->where('status', 1)
                    ->first();

                if ($coupon) {
                    $discount = $coupon->discount_type === 'fixed'
                        ? $coupon->discount_percentage
                        : ($subtotal * $coupon->discount_percentage / 100);
                }
            }

            $subtotalAfterDiscount = max(0, $subtotal - $discount);
            $shippingCost = $request->shipping_cost ?? 0;
            $grandTotal = $subtotalAfterDiscount + $shippingCost;

            // Step 6: Create order
            $order = Order::create(array_merge($request->shipping, [
                'order_number'        => 'ORD-' . strtoupper(uniqid()),
                'user_id'             => $user->id ?? null,
                'session_id'          => $cart->session_id,
                'razorpay_order_id'   => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature'  => $request->razorpay_signature,
                'subtotal'            => $subtotal,
                'discount'            => $discount,
                'coupon_code'         => $cart->coupon_code,
                'shipping_cost'       => $shippingCost,
                'total_amount'        => $grandTotal,
                'currency'            => 'INR',
                'status'              => 'paid',
            ]));

            // Step 7: Save each order item using variant pricing
            foreach ($cart->items as $item) {
                $variant = $item->variant;
                if (!$variant) {
                    continue;
                }

                $price = $variant->discount_price ?? $variant->price;

                OrderItem::create([
                    'order_id'    => $order->id,
                    'product_id'  => $item->product_id,
                    'variant_id'  => $variant->id,     // store variant reference
                    'color_id'    => $variant->color_id ?? null,
                    'quantity'    => $item->quantity,
                    'price'       => $price,
                ]);

                // Step 8: Reduce stock quantity
                $variant->decrement('quantity', $item->quantity);
                if ($variant->quantity <= 0) {
                    $variant->update(['in_stock' => false]);
                }
            }

            // Step 9: Clear cart
            $cart->items()->delete();

            DB::commit();

            return $this->sendResponse([
                'order_id'      => $order->id,
                'order_number'  => $order->order_number,
                'total_amount'  => $grandTotal,
                'shipping_cost' => $shippingCost,
                'discount'      => $discount,
                'coupon_code'   => $cart->coupon_code,
            ], 'Payment Verified & Order Placed Successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Order Placement Failed: " . $e->getMessage());
            return $this->sendError('Payment success but order creation failed. Support will assist.', 500);
        }
    }
}
