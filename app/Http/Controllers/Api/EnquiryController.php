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


        // Validate request
        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'email'     => 'required|email',
            'contact_number' => 'required|string|max:20',
            'message'   => 'required|string',
        ]);

        $data = (object) $request->only('full_name', 'email', 'message'); // cast to object for Mailable

        try {
            // 1️⃣ Get the first Admin user from database
            $adminUser = User::where('role', 'admin')->first();

            if (!$adminUser) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Admin not found. Cannot send email.',
                ], 500);
            }

            // 2️⃣ Send Email to Admin
            $adminSubject = 'New Contact Us Message from ' . $data->full_name;
            $adminTitle = 'Contact Us Message';
            $adminContent = "Name: {$data->full_name}<br>Email: {$data->email}<br>Message: {$data->message}";
            Mail::to($adminUser->email)
                ->send(new SystemNotificationMail(
                    $data,
                    $adminSubject,
                    $adminTitle,
                    $adminContent
                ));

            // 3️⃣ Send Confirmation Email to Customer
            $customerSubject = 'Thank you for contacting ' . config('app.name');
            $customerTitle = 'We received your message';
            $customerContent = 'Hi ' . $data->full_name . ',<br>We have received your message and will get back to you shortly.';

            Mail::to($data->email)
                ->send(new SystemNotificationMail(
                    $data,
                    $customerSubject,
                    $customerTitle,
                    $customerContent
                ));

            // 4️⃣ Return Success Response
            return response()->json([
                'status' => 200,
                'message' => 'Your message has been sent successfully!',
            ]);
        } catch (\Exception $e) {
            \Log::error('Contact Us Mail failed: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Failed to send your message. Please try again.',
            ], 500);
        }
    }
}
