<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\SystemNotificationMail;
use App\Models\User;

class OrderController extends BaseController
{
    /**
     * List all orders for authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        $orders = Order::where('user_id', $user->id)
            ->with('items.product', 'items.color')
            ->latest()
            ->get();

        return $this->sendResponse(
            OrderResource::collection($orders),
            'Order list retrieved successfully.'
        );
    }

    /**
     * Admin: List all orders with filters
     */
    public function adminIndex(Request $request)
    {
        $query = Order::with(['items.product', 'items.color', 'user'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        $orders = $query->paginate(10);

        return $this->sendResponse(
            OrderResource::collection($orders),
            'All orders retrieved successfully.'
        );
    }
    /**
     * Show single order details
     */
    public function show(Order $order)
    {
        $user = Auth::guard('sanctum')->user();

        if ($order->user_id !== $user->id) {
            return $this->sendError('Unauthorized access to this order.', [], 403);
        }

        $order->load('items.product', 'items.color');

        return $this->sendResponse(
            new OrderResource($order),
            'Order details retrieved successfully.'
        );
    }


    /**
     * Cancel an order (if not yet shipped)
     */
    public function cancel(Order $order)
    {
        $user = Auth::guard('sanctum')->user();

        if ($order->user_id !== $user->id) {
            return $this->sendError('Unauthorized to cancel this order.', 403);
        }

        if ($order->status !== 'paid') {
            return $this->sendError('Order cannot be cancelled.', 400);
        }

        $order->update(['status' => 'cancelled']);

        /**
         * 📨 Send Cancellation Emails
         */

        try {
            // 1️⃣ Prepare Data
            $adminUser = User::where('role', 'admin')->first(); // or your own admin logic
            $orderId = $order->id;

            // 2️⃣ Send Email to Admin
            $adminSubject = "Order #{$orderId} Cancelled by {$user->name}";
            $adminTitle = 'Order Cancelled Notification';
            $adminContent = "
            Order ID: {$orderId}<br>
            Customer Name: {$user->name}<br>
            Customer Email: {$user->email}<br>
            Status: Cancelled<br>
        ";

            Mail::to($adminUser->email)
                ->send(new SystemNotificationMail(
                    $order,
                    $adminSubject,
                    $adminTitle,
                    $adminContent,
                    null,
                    null,
                    'Admin'
                ));

            // 3️⃣ Send Confirmation Email to Customer
            $userSubject = "Your Order #{$orderId} Has Been Cancelled";
            $userTitle = 'Order Cancellation Confirmation';
            $userContent = "
            Your order #{$orderId} has been successfully cancelled.<br>
            If you have any questions, please contact support.<br><br>
            Thank you.
        ";

            Mail::to($user->email)
                ->send(new SystemNotificationMail(
                    $order,
                    $userSubject,
                    $userTitle,
                    $userContent,
                    null,
                    null,
                    $user->name
                ));
        } catch (\Exception $e) {
            // Optional: Log any mail errors without breaking the response
            \Log::error('Cancel mail failed: ' . $e->getMessage());
        }

        return $this->sendResponse($order, 'Order cancelled successfully.');
    }
}
