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
use App\Models\{Order, OrderItem, Cart, DeliveryPincode, User};
use Illuminate\Support\Facades\Mail;
use App\Mail\SystemNotificationMail;
use Illuminate\Support\Facades\DB;
use PDF;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
    public function createOrder(Request $request)
    {
        $request->validate([
            'amount'   => 'required|numeric|min:1',
            'currency' => 'required|string',
            'pincode'  => 'required|string|max:10',
        ]);

        $user = Auth::guard('sanctum')->user();
        $sessionId = $this->getSessionId($request);

        // Get the cart
        $cart = $user
            ? Cart::where('user_id', $user->id)->with('items.product')->first()
            : Cart::where('session_id', $sessionId)->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return $this->sendError('Cart is empty.', 400);
        }

        // Calculate subtotal
        $subtotal = $cart->items->sum(fn($item) => $item->product->final_price * $item->quantity);

        // Get shipping
        $delivery = DeliveryPincode::serviceable()->where('pincode', $request->pincode)->first();
        if (!$delivery) return $this->sendError('This pincode is not serviceable.', 400);

        $totalAmount = $subtotal + $delivery->shipping_cost;

        // Create Razorpay order
        $api = new \Razorpay\Api\Api(config('services.razorpay.key'), config('services.razorpay.secret'));
        $order = $api->order->create([
            'amount' => $totalAmount * 100, // amount in paise
            'currency' => $request->currency,
            'receipt' => 'rcpt_' . time(),
            'notes' => [
                'shipping_cost' => $delivery->shipping_cost,
                'pincode' => $request->pincode
            ]
        ]);

        return $this->sendResponse([
            'order' => $order->toArray(),
            'subtotal' => $subtotal,
            'shipping_cost' => $delivery->shipping_cost,
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
            try {
                $api->utility->verifyPaymentSignature([
                    'razorpay_order_id'   => $request->razorpay_order_id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature'  => $request->razorpay_signature,
                ]);
            } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
                return $this->sendError('Invalid Signature', 400);
            }

            // ✅ Fetch payment status
            $payment = $api->payment->fetch($request->razorpay_payment_id);
            if ($payment->status !== 'captured') {
                return $this->sendError('Payment not captured', 400);
            }

            $user = auth()->user();
            $cart = $user
                ? Cart::with('items.product', 'items.color')->where('user_id', $user->id)->first()
                : Cart::with('items.product', 'items.color')->where('session_id', $request->header('X-Session-Id'))->first();

            if (!$cart || $cart->items->isEmpty()) {
                return $this->sendError('Cart empty or not found', 404);
            }

            DB::beginTransaction();

            $subtotal = $cart->items->sum(fn($i) => $i->product->final_price * $i->quantity);
            $shippingCost = $request->shipping_cost ?? 0;
            $grandTotal = $subtotal + $shippingCost;

            $order = Order::create(array_merge($request->shipping, [
                'order_number'        => 'ORD-' . strtoupper(uniqid()),
                'user_id'             => $user->id ?? null,
                'session_id'          => $cart->session_id,
                'razorpay_order_id'   => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature'  => $request->razorpay_signature,
                'subtotal'            => $subtotal,
                'shipping_cost'       => $shippingCost,
                'total_amount'        => $grandTotal,
                'currency'            => 'INR',
                'status'              => 'paid',
            ]));

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item->product_id,
                    'color_id'   => $item->color_id,
                    'quantity'   => $item->quantity,
                    'price'      => $item->product->final_price,
                ]);
            }

            $cart->items()->delete();

            DB::commit();

            /**
             * Generate PDF Invoice
             */
            $invoicePath = 'invoices/invoice_' . $order->id . '.pdf';

            $pdf = PDF::loadView('pdf.invoice', compact('order'))->setPaper('a4');
            Storage::disk('public')->put($invoicePath, $pdf->output());

            /**
             * Queue Emails (Customer + Admin)
             */
            try {
                $emailHtml = view('emails.order-confirm', compact('order'))->render();
                $adminUser = User::where('role', 'admin')->first();

                if ($order->email) {
                    Mail::to($order->email)->queue(
                        new SystemNotificationMail(
                            $order,
                            "Order Confirmation - #{$order->order_number}",
                            "Invoice Attached",
                            $emailHtml,
                            storage_path('app/public/' . $invoicePath),
                            'invoice_' . $order->id . '.pdf',
                            $order->name
                        )
                    );
                }

                // Admin Email
                if ($adminUser?->email) {
                    Mail::to($adminUser->email)->queue(
                        new SystemNotificationMail(
                            $order,
                            "New Order - #{$order->order_number}",
                            "Invoice Attached",
                            $emailHtml,
                            storage_path('app/public/' . $invoicePath),
                            'invoice_' . $order->id . '.pdf',
                            'Admin'
                        )
                    );
                }
            } catch (\Exception $e) {
                \Log::error("Order Email Failed: " . $e->getMessage());
            }

            return $this->sendResponse([
                'order_id'      => $order->id,
                'order_number'  => $order->order_number,
                'total_amount'  => $grandTotal,
                'shipping_cost' => $shippingCost,
            ], 'Payment Verified & Order Placed Successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Order Placement Failed: " . $e->getMessage());
            return $this->sendError('Payment success but order creation failed. Support will assist.', 500);
        }
    }
}
