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
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;

class RazorpayController extends BaseController
{
    /**
     * Create Razorpay Order
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'amount'   => 'required|numeric|min:1',
            'currency' => 'required|string'
        ]);

        try {
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

            $order = $api->order->create([
                'amount'   => $request->amount, // convert to paise
                'currency' => $request->currency,
                'receipt'  => $request->receipt ?? 'rcpt_' . time()
            ]);

            // ✅ Convert object to array before sending response
            return $this->sendResponse(['order' => $order->toArray()], 'Order created successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
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
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
            'shipping'            => 'required|array',
            'shipping_cost'       => 'nullable|numeric|min:0', // 👈 optional shipping cost
        ]);

        try {
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));

            // Verify signature
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ];

            try {
                $api->utility->verifyPaymentSignature($attributes);
            } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
                return $this->sendError('Invalid Signature: ' . $e->getMessage(), 400);
            }

            // Fetch payment
            $payment = $api->payment->fetch($request->razorpay_payment_id);
            if ($payment->status !== 'captured') {
                return $this->sendError('Payment not captured', 400);
            }

            $user = auth()->user();
            $cart = $user
                ? Cart::where('user_id', $user->id)->first()
                : Cart::where('session_id', $request->header('X-Session-Id'))->first();

            if (!$cart) {
                return $this->sendError('Cart not found', 404);
            }

            DB::beginTransaction();

            // 🧮 Calculate totals
            $productTotal = $cart->items->sum(function ($item) {
                return $item->product->final_price * $item->quantity;
            });

            $shippingCost = $request->shipping_cost ?? 0;
            $grandTotal   = $productTotal + $shippingCost;

            // ✅ Create order
            $order = Order::create(array_merge($request->shipping, [
                'user_id'             => $user->id ?? null,
                'session_id'          => $cart->session_id,
                'razorpay_order_id'   => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature'  => $request->razorpay_signature,
                'subtotal'            => $productTotal,
                'shipping_cost'       => $shippingCost,
                'total_amount'        => $grandTotal,
                'currency'            => 'INR',
                'status'              => 'paid',
            ]));

            // ✅ Create order items
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item->product_id,
                    'color_id'   => $item->color_id,
                    'quantity'   => $item->quantity,
                    'price'      => $item->product->final_price
                ]);
            }

            // ✅ Clear cart
            $cart->items()->delete();

            DB::commit();

            return $this->sendResponse([
                'order_id' => $order->id,
                'total_amount' => $grandTotal,
                'shipping_cost' => $shippingCost
            ], 'Payment Verified & Order Placed');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
