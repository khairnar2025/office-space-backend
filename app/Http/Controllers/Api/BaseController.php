<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * Success response method.
     *
     * @param  mixed  $result
     * @param  string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResponse($result, string $message): JsonResponse
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];

        return response()->json($response, 200);
    }

    /**
     * Return error response.
     *
     * @param  string|array  $error
     * @param  int           $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendError($error, int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'data'    => null,
            'message' => $error,
        ];

        return response()->json($response, $code);
    }
}
