<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        return $this->sendResponse($orders, 'Order list retrieved successfully.');
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

        return $this->sendResponse($order, 'Order details retrieved successfully.');
    }

    /**
     * Cancel an order (if not yet shipped)
     */
    public function cancel(Order $order)
    {
        $user = Auth::guard('sanctum')->user();

        if ($order->user_id !== $user->id) {
            return $this->sendError('Unauthorized to cancel this order.', [], 403);
        }

        if ($order->status !== 'paid') {
            return $this->sendError('Order cannot be cancelled.', [], 400);
        }

        $order->update(['status' => 'cancelled']);

        return $this->sendResponse($order, 'Order cancelled successfully.');
    }
}
