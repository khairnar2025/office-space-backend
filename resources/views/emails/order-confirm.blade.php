<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; font-size: 14px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .total-row { font-weight: bold; }
        .heading { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
    </style>
</head>
<body>

    <p>Hi {{ $order->name }},</p>
    <p>Thank you for your order! Please find your invoice below.</p>

    <div class="heading">Order Invoice #{{ $order->id }}</div>
    <p><strong>Date:</strong> {{ $order->created_at->format('d M, Y') }}</p>
    <p><strong>Payment ID:</strong> {{ $order->razorpay_payment_id }}</p>

    <h3>Billing & Shipping Info:</h3>
    <p>
        {{ $order->name }} <br>
        {{ $order->address }}<br>
        {{ $order->city }}, {{ $order->state }} - {{ $order->pincode }}<br>
        Phone: {{ $order->phone }}<br>
        Email: {{ $order->email }}
    </p>

    <h3>Order Items:</h3>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Color</th>
                <th>Qty</th>
                <th>Price (₹)</th>
                <th>Total (₹)</th>
            </tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product->title }}</td>
                <td>{{ $item->color->name ?? '-' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->price, 2) }}</td>
                <td>{{ number_format($item->quantity * $item->price, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @php
        $gst = round(($order->subtotal * 0.18), 2); // 18% GST
    @endphp

    <table style="margin-top: 20px;">
        <tr>
            <td>Subtotal</td>
            <td>₹ {{ number_format($order->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>GST (18%)</td>
            <td>₹ {{ number_format($gst, 2) }}</td>
        </tr>
        <tr>
            <td>Shipping Charge</td>
            <td>₹ {{ number_format($order->shipping_cost, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td><strong>Grand Total</strong></td>
            <td><strong>₹ {{ number_format($order->total_amount + $gst, 2) }}</strong></td>
        </tr>
    </table>

    <br>
    <p>Invoice PDF attached ✅</p>
    <p>Thank you for shopping with us!</p>

</body>
</html>
