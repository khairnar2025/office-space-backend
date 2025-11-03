<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SystemNotificationMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class EnquiryController extends Controller
{
    public function submit(Request $request): JsonResponse
{
    $request->validate([
        'type'            => 'required|string|in:contactus,enquiry',
        'full_name'       => 'required|string|max:100',
        'email'           => 'required|email',
        'contact_number'  => 'required|string|max:20',
        'message'         => 'required|string',
        'product_id'      => 'required_if:type,enquiry|array',
        'product_id.*'    => 'exists:products,id',
    ]);

    $type = $request->input('type');
    $data = (object) $request->only('full_name', 'email', 'contact_number', 'message', 'product_id');

    try {
        $adminUser = User::where('role', 'admin')->first();

        if (!$adminUser) {
            return response()->json([
                'status'  => 500,
                'message' => 'Admin not found. Cannot send email.',
            ], 500);
        }

        if ($type === 'contactus') {
            $adminSubject = 'New Contact Us Message from ' . $data->full_name;
            $adminTitle   = 'Contact Us Message';
            $adminContent = "Name: {$data->full_name}<br>"
                          . "Email: {$data->email}<br>"
                          . "Contact Number: {$data->contact_number}<br>"
                          . "Message: {$data->message}";
        } else { // enquiry
            $productTitles = 'N/A';
            if (!empty($data->product_id) && is_array($data->product_id)) {
                $products = \App\Models\Product::whereIn('id', $data->product_id)
                                               ->pluck('title')->toArray();
                $productTitles = implode(', ', $products);
            }
            $adminSubject = 'New Product Enquiry from ' . $data->full_name;
            $adminTitle   = 'Product Enquiry';
            $adminContent = "Name: {$data->full_name}<br>"
                          . "Email: {$data->email}<br>"
                          . "Contact Number: {$data->contact_number}<br>"
                          . "Products: {$productTitles}<br>"
                          . "Message: {$data->message}";
        }

        // Send email to admin
        Mail::to($adminUser->email)
            ->send(new SystemNotificationMail(
                $data,
                $adminSubject,
                $adminTitle,
                $adminContent
            ));

        // Send confirmation email to customer
        $customerSubject = 'Thank you for contacting ' . config('app.name');
        $customerTitle   = 'We received your message';
        $customerContent = 'Hi ' . $data->full_name . ',<br>'
                         . 'We have received your message and will get back to you shortly.';

        Mail::to($data->email)
            ->send(new SystemNotificationMail(
                $data,
                $customerSubject,
                $customerTitle,
                $customerContent
            ));

        return response()->json([
            'status'  => 200,
            'message' => 'Your message has been sent successfully!',
        ]);
    } catch (\Exception $e) {
        \Log::error('Contact/Enquiry Mail failed: ' . $e->getMessage());

        return response()->json([
            'status'  => 500,
            'message' => 'Failed to send your message. Please try again.',
        ], 500);
    }
}

}
