<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\StoreCategoryRequest;
use App\Http\Requests\Api\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends BaseController
{
    public function index(): JsonResponse
    {
        $categories = Category::latest()->get();
        return $this->sendResponse(CategoryResource::collection($categories), 'Categories fetched successfully.');
    }
    // Public: limited info
    public function publicIndex(): JsonResponse
    {
        $categories = Category::select('id', 'name')->active()->latest()->get();
        return $this->sendResponse(
            CategoryResource::collection($categories),
            'Categories retrieved successfully.'
        );
    }
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());
        return $this->sendSimpleResponse($category->id, true, 'Category created successfully.');
    }

    public function show(Category $category): JsonResponse
    {
        return $this->sendResponse(new CategoryResource($category), 'Category details retrieved successfully.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());
        return $this->sendSimpleResponse($category->id, true, 'Category updated successfully.');
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        return $this->sendResponse([], 'Category deleted successfully.');
    }
}
