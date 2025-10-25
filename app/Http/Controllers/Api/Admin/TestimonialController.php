<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Testimonial;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\StoreTestimonialRequest;
use App\Http\Requests\Api\UpdateTestimonialRequest;
use App\Http\Resources\AdminTestimonialResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\TestimonialResource;

class TestimonialController extends BaseController
{
    // Admin: full management
    public function index(): JsonResponse
    {
        $testimonials = Testimonial::with('client')->latest()->get();
        return $this->sendResponse(
            AdminTestimonialResource::collection($testimonials),
            'Testimonials retrieved successfully.'
        );
    }

    // Public: limited info
    public function publicIndex(): JsonResponse
    {
        $testimonials = Testimonial::with('client')->active()->latest()->get();
        return $this->sendResponse(
            TestimonialResource::collection($testimonials),
            'Testimonials retrieved successfully.'
        );
    }

    public function store(StoreTestimonialRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['client_user_id'] = $request->client_user_id;

        // Handle media upload
        if ($request->hasFile('media_file')) {
            $file = $request->file('media_file');
            $data['media_type'] = str_contains($file->getMimeType(), 'video') ? 'video' : 'image';
            $data['media_url'] = $file->store('testimonials', 'public');

            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                $data['thumbnail_url'] = $thumbnail->store('testimonials/thumbnails', 'public');
            }
        }

        $testimonial = Testimonial::create($data);

        return $this->sendResponse($testimonial, 'Testimonial created successfully.');
    }

    public function show($id): JsonResponse
    {
        $testimonial = Testimonial::with('client')->find($id);
        if (!$testimonial) {
            return $this->sendError('Testimonial not found.', 404);
        }
        return $this->sendResponse($testimonial, 'Testimonial retrieved successfully.');
    }

    public function update(UpdateTestimonialRequest $request, $id): JsonResponse
    {
        $testimonial = Testimonial::find($id);
        if (!$testimonial) {
            return $this->sendError('Testimonial not found.', 404);
        }

        $data = $request->validated();

        if ($request->hasFile('media_file')) {
            // Delete old file
            if ($testimonial->media_url && Storage::disk('public')->exists($testimonial->media_url)) {
                Storage::disk('public')->delete($testimonial->media_url);
            }

            $file = $request->file('media_file');
            $data['media_type'] = str_contains($file->getMimeType(), 'video') ? 'video' : 'image';
            $data['media_url'] = $file->store('testimonials', 'public');

            if ($request->hasFile('thumbnail')) {
                if ($testimonial->thumbnail_url && Storage::disk('public')->exists($testimonial->thumbnail_url)) {
                    Storage::disk('public')->delete($testimonial->thumbnail_url);
                }

                $thumbnail = $request->file('thumbnail');
                $data['thumbnail_url'] = $thumbnail->store('testimonials/thumbnails', 'public');
            }
        }

        $testimonial->update($data);
        return $this->sendResponse($testimonial, 'Testimonial updated successfully.');
    }

    public function destroy($id): JsonResponse
    {
        $testimonial = Testimonial::find($id);
        if (!$testimonial) {
            return $this->sendError('Testimonial not found.', 404);
        }

        // Delete media file
        if ($testimonial->media_url && Storage::disk('public')->exists($testimonial->media_url)) {
            Storage::disk('public')->delete($testimonial->media_url);
        }

        // Delete thumbnail if exists
        if ($testimonial->thumbnail_url && Storage::disk('public')->exists($testimonial->thumbnail_url)) {
            Storage::disk('public')->delete($testimonial->thumbnail_url);
        }

        $testimonial->delete();
        return $this->sendResponse([], 'Testimonial deleted successfully.');
    }
}
