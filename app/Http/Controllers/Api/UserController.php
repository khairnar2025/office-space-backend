<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    /**
     * Display a paginated list of users
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::where('role', 'user')->orderBy('id', 'desc')->paginate(10);

        return $this->sendResponse(
            UserResource::collection($users),
            'User list retrieved successfully.'
        );
    }

    /**
     * Change user active/inactive status
     */
    public function changeStatus($id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return $this->sendError('User not found', 404);
        }

        $user->status = !$user->status;
        $user->save();

        return $this->sendSimpleResponse(
            $user->id,
            $user->status,
            'User status updated successfully.'
        );
    }

    /**
     * Show user billing and shipping addresses
     */
    public function showAddresses($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->sendError('User not found', 404);
        }

        $data = [
            'billing_address'  => $user->billing_address ? json_decode($user->billing_address, true) : null,
            'shipping_address' => $user->shipping_address ? json_decode($user->shipping_address, true) : null,
        ];

        return $this->sendResponse($data, 'User addresses retrieved successfully.');
    }
}
