<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\StoreColorRequest;
use App\Http\Requests\Api\UpdateColorRequest;
use App\Http\Resources\ColorResource;
use App\Models\Color;
use Illuminate\Http\JsonResponse;

class ColorController extends BaseController
{
    public function index(): JsonResponse
    {
        $colors = Color::latest()->get();
        return $this->sendResponse(ColorResource::collection($colors), 'Colors fetched successfully.');
    }
    // Public: limited info
    public function publicIndex(): JsonResponse
    {
        $colors = Color::select('id', 'name')->active()->latest()->get();
        return $this->sendResponse(
            ColorResource::collection($colors),
            'Colors retrieved successfully.'
        );
    }
    public function store(StoreColorRequest $request): JsonResponse
    {
        $color = Color::create($request->validated());
        return $this->sendSimpleResponse($color->id, true, 'Color created successfully.');
    }

    public function show(Color $color): JsonResponse
    {
        return $this->sendResponse(new ColorResource($color), 'Color details retrieved successfully.');
    }

    public function update(UpdateColorRequest $request, Color $color): JsonResponse
    {
        $color->update($request->validated());
        return $this->sendSimpleResponse($color->id, true, 'Color updated successfully.');
    }

    public function destroy(Color $color): JsonResponse
    {
        $color->delete();
        return $this->sendResponse([], 'Color deleted successfully.');
    }
}
