<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterUserRequest;
use App\Http\Resources\UserResource;
use App\Mail\SystemNotificationMail;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AuthController extends BaseController
{
    /**
     * Register a new user (Client)
     */
    public function register(RegisterUserRequest $request)
    {
        $validated = $request->validated();
        try {
            $userName = $this->generateUserNameFromEmail($validated['email']);
            $user = User::create([
                'name'     => $userName,
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role'     => 'user',
                'status'   => true,
            ]);

            $subject = 'Welcome to ' . config('app.name');
            $title   = 'Welcome to ' . config('app.name');
            $content = 'Thank you for registering with <strong>' . config('app.name') . '</strong>. We’re glad to have you on board!';
            Mail::to($user->email)->send(new SystemNotificationMail(
                $user,
                $subject,
                $title,
                $content,
                null,
                null
            ));
            return $this->sendResponse([
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ]
            ], 'Registration successful. Welcome email has been sent.');
        } catch (\Exception $e) {
            Log::error('User registration failed: ' . $e->getMessage());

            return $this->sendError('Registration failed. Please try again later.', 500);
        }
    }

    /**
     * Generate username based on email prefix
     */
    private function generateUserNameFromEmail($email): string
    {
        $prefix = explode('@', $email)[0];
        return ucfirst(preg_replace('/[^A-Za-z0-9]/', '', $prefix));
    }
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email. Please register first.'
            ], 404);
        }

        if (!$user->status) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.'
            ], 403);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The password you entered is incorrect. Please try again.'
            ], 401);
        }

        $token = $user->createToken('API Token')->plainTextToken;

        $sessionId = $request->header('X-Session-Id');
        $mergedCart = null;

        if ($sessionId) {
            $guestCart = Cart::where('session_id', $sessionId)->with('items.product', 'items.color')->first();

            if ($guestCart) {
                // If user already has a cart, merge guest cart into user cart
                $userCart = Cart::where('user_id', $user->id)
                    ->with('items.product', 'items.color')
                    ->first();

                if ($userCart) {
                    foreach ($guestCart->items as $item) {
                        $existing = $userCart->items()
                            ->where('product_id', $item->product_id)
                            ->where('color_id', $item->color_id)
                            ->first();

                        if ($existing) {
                            $existing->increment('quantity', $item->quantity);
                        } else {
                            $userCart->items()->create([
                                'product_id' => $item->product_id,
                                'color_id'   => $item->color_id,
                                'quantity'   => $item->quantity,
                                'price'      => $item->price,
                            ]);
                        }
                    }

                    // Delete guest cart
                    $guestCart->delete();
                    $mergedCart = $userCart->fresh('items.product', 'items.color');
                } else {
                    $guestCart->update([
                        'user_id' => $user->id,
                        'session_id' => null,
                    ]);
                    $mergedCart = $guestCart->fresh('items.product', 'items.color');
                }
            }
        }

        $responseData = [
            'success' => true,
            'message' => 'Login successful! Welcome back.',
            'data' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ];
        if ($mergedCart) {
            $responseData['data']['cart'] = $mergedCart;
        }

        return response()->json($responseData, 200);
    }

    // public function login(LoginRequest $request)
    // {
    //     $validated = $request->validated();
    //     $user = User::where('email', $validated['email'])->first();
    //     if (!$user) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'No account found with this email. Please register first.'
    //         ], 404);
    //     }

    //     if (!$user->status) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Your account is inactive. Please contact support.'
    //         ], 403);
    //     }
    //     if (!Hash::check($validated['password'], $user->password)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'The password you entered is incorrect. Please try again.'
    //         ], 401);
    //     }

    //     $token = $user->createToken('API Token')->plainTextToken;

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Login successful! Welcome back.',
    //         'data' => [
    //             'user' => [
    //                 'id'    => $user->id,
    //                 'name'  => $user->name,
    //                 'email' => $user->email,
    //                 'role'  => $user->role,
    //             ],
    //             'token' => $token,
    //             'token_type' => 'Bearer'
    //         ]
    //     ], 200);
    // }
    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
            'message' => 'User retrieved successfully',
        ]);
    }
}
