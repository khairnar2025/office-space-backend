<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseController extends Controller
{
    /**
     * Success response method.
     */
    public function sendResponse($result, string $message): JsonResponse
    {
        // ✅ Detect paginated collection
        if (
            is_object($result) &&
            property_exists($result, 'resource') &&
            $result->resource instanceof LengthAwarePaginator
        ) {
            $pagination = [
                'current_page'   => $result->resource->currentPage(),
                'last_page'      => $result->resource->lastPage(),
                'per_page'       => $result->resource->perPage(),
                'total'          => $result->resource->total(),
                'next_page_url'  => $result->resource->nextPageUrl(),
                'prev_page_url'  => $result->resource->previousPageUrl(),
            ];

            return response()->json([
                'success'    => true,
                'message'    => $message,
                'data'       => $result,
                'pagination' => $pagination,
            ], 200);
        }

        // ✅ Normal response
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $result,
        ], 200);
    }

    /**
     * Error response.
     */
    public function sendError($error, int $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data'    => null,
            'message' => $error,
        ], $code);
    }

    /**
     * Simple response for basic updates or deletes.
     */
    public function sendSimpleResponse($id, $status, $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'id'      => $id,
            'message' => $message,
        ], 200);
    }
}
