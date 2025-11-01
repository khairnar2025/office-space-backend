<?php

namespace App\Http\Controllers\Api;

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
        $testimonials = Testimonial::latest()->paginate(10);
        return $this->sendResponse(
            AdminTestimonialResource::collection($testimonials),
            'Testimonials retrieved successfully.'
        );
    }

    // Public: limited info
    public function publicIndex(): JsonResponse
    {
        $testimonials = Testimonial::active()->latest()->get();
        return $this->sendResponse(
            TestimonialResource::collection($testimonials),
            'Testimonials retrieved successfully.'
        );
    }

    public function store(StoreTestimonialRequest $request): JsonResponse
    {
        $data = $request->validated();
        // Handle media upload
        if ($request->hasFile('media_file')) {
            $file = $request->file('media_file');
            $data['media_url'] = $file->store('testimonials', 'public');

            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                $data['thumbnail_url'] = $thumbnail->store('testimonials/thumbnails', 'public');
            }
        }
        if ($request->hasFile('profile_image')) {
            $thumbnail = $request->file('profile_image');
            $data['profile_image'] = $thumbnail->store('testimonials/profile_image', 'public');
        }
        $testimonial = Testimonial::create($data);
        return $this->sendSimpleResponse($testimonial->id, $testimonial->status, 'Testimonial created successfully.');
    }

    public function show($id): JsonResponse
    {
        $testimonial = Testimonial::find($id);
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
        // ✅ Remove old files if requested
        if ($request->boolean('remove_media_file')) {
            if ($testimonial->media_url && Storage::disk('public')->exists($testimonial->media_url)) {
                Storage::disk('public')->delete($testimonial->media_url);
            }
            $data['media_url'] = null;
        }

        if ($request->boolean('remove_thumbnail')) {
            if ($testimonial->thumbnail_url && Storage::disk('public')->exists($testimonial->thumbnail_url)) {
                Storage::disk('public')->delete($testimonial->thumbnail_url);
            }
            $data['thumbnail_url'] = null;
        }

        if ($request->boolean('remove_profile_image')) {
            if ($testimonial->profile_image && Storage::disk('public')->exists($testimonial->profile_image)) {
                Storage::disk('public')->delete($testimonial->profile_image);
            }
            $data['profile_image'] = null;
        }
        if ($request->hasFile('media_file')) {
            // Delete old file
            if ($testimonial->media_url && Storage::disk('public')->exists($testimonial->media_url)) {
                Storage::disk('public')->delete($testimonial->media_url);
            }

            $file = $request->file('media_file');
            $data['media_url'] = $file->store('testimonials', 'public');
        }
        if ($request->hasFile('thumbnail')) {
            if ($testimonial->thumbnail_url && Storage::disk('public')->exists($testimonial->thumbnail_url)) {
                Storage::disk('public')->delete($testimonial->thumbnail_url);
            }

            $thumbnail = $request->file('thumbnail');
            $data['thumbnail_url'] = $thumbnail->store('testimonials/thumbnails', 'public');
        }
        if ($request->hasFile('profile_image')) {
            if ($testimonial->profile_image && Storage::disk('public')->exists($testimonial->profile_image)) {
                Storage::disk('public')->delete($testimonial->profile_image);
            }
            $thumbnail = $request->file('profile_image');
            $data['profile_image'] = $thumbnail->store('testimonials/profile_image', 'public');
        }
        $testimonial->update($data);
        return $this->sendSimpleResponse($testimonial->id, $testimonial->status, 'Testimonial updated successfully.');
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
