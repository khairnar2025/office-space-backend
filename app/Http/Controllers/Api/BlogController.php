<?php

namespace App\Http\Controllers\Api;

use App\Models\Blog;
use App\Models\BlogSection;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Api\StoreBlogRequest;
use App\Http\Requests\Api\UpdateBlogRequest;
use App\Http\Resources\BlogResource;
use App\Http\Resources\AdminBlogResource;
use Illuminate\Http\JsonResponse;

class BlogController extends BaseController
{
    public function index()
    {
        $blogs = Blog::with('sections')->latest()->paginate(10);
        return $this->sendResponse(AdminBlogResource::collection($blogs), 'Blogs retrieved successfully.');
    }
    // Public: limited info
    public function publicIndex(): JsonResponse
    {
        $blogs = Blog::with('sections')->active()->latest()->get();
        return $this->sendResponse(
            BlogResource::collection($blogs),
            'Blogs retrieved successfully.'
        );
    }
    public function store(StoreBlogRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('blogs', 'public');
        }

        $blog = Blog::create($data);

        if (!empty($data['sections'])) {
            foreach ($data['sections'] as $section) {
                $attachment = isset($section['attachment']) && $section['attachment'] instanceof \Illuminate\Http\UploadedFile
                    ? $section['attachment']->store('blogs/sections', 'public')
                    : null;

                $blog->sections()->create([
                    'heading' => $section['heading'] ?? null,
                    'content' => $section['content'] ?? null,
                    'attachment' => $attachment,
                ]);
            }
        }

        return $this->sendResponse(new BlogResource($blog->load('sections')), 'Blog created successfully.');
    }

    public function publicShow($id)
    {
        $blog = Blog::with('sections')->find($id);
        if (!$blog) return $this->sendError('Blog not found.', 404);
        return $this->sendResponse(new BlogResource($blog), 'Blog retrieved successfully.');
    }
    public function show($id)
    {
        $blog = Blog::with('sections')->find($id);
        if (!$blog) return $this->sendError('Blog not found.', 404);
        return $this->sendResponse(new BlogResource($blog), 'Blog retrieved successfully.');
    }
    public function update(UpdateBlogRequest $request, $id)
    {
        $blog = Blog::find($id);
        if (!$blog) {
            return $this->sendError('Blog not found.', 404);
        }

        $data = $request->validated();

        //Update main blog image
        if ($request->hasFile('image')) {
            if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                Storage::disk('public')->delete($blog->image);
            }
            $data['image'] = $request->file('image')->store('blogs', 'public');
        }
        // Delete existing blog image if requested
        if ($request->filled('deleted_image')) {
            if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                Storage::disk('public')->delete($blog->image);
            }
            $data['image'] = null; // Important: clear DB value
        }
        $blog->update($data);

        //Update or create sections
        if (!empty($data['sections'])) {
            foreach ($data['sections'] as $section) {
                $attachment = isset($section['attachment']) && $section['attachment'] instanceof \Illuminate\Http\UploadedFile
                    ? $section['attachment']->store('blogs/sections', 'public')
                    : null;

                BlogSection::updateOrCreate(
                    ['id' => $section['id'] ?? null, 'blog_id' => $blog->id],
                    [
                        'heading'    => $section['heading'] ?? null,
                        'content'    => $section['content'] ?? null,
                        'attachment' => $attachment ?? ($section['existing_attachment'] ?? null),
                    ]
                );
            }
        }

        //Delete sections if provided
        if ($request->filled('deleted_sections')) {
            $sectionsToDelete = BlogSection::whereIn('id', $request->deleted_sections)
                ->where('blog_id', $blog->id)
                ->get();

            foreach ($sectionsToDelete as $section) {
                if ($section->attachment && Storage::disk('public')->exists($section->attachment)) {
                    Storage::disk('public')->delete($section->attachment);
                }
                $section->delete();
            }
        }

        if ($request->filled('deleted_attachments')) {
            $sections = BlogSection::whereIn('id', $request->deleted_attachments)
                ->where('blog_id', $blog->id)
                ->get();

            foreach ($sections as $section) {
                if ($section->attachment && Storage::disk('public')->exists($section->attachment)) {
                    Storage::disk('public')->delete($section->attachment);
                    $section->update(['attachment' => null]);
                }
            }
        }

        $blog->load('sections');

        return $this->sendResponse(
            [new BlogResource($blog)],
            'Blogs retrieved successfully.'
        );
    }


    public function destroy($id)
    {
        $blog = Blog::with('sections')->find($id);
        if (!$blog) return $this->sendError('Blog not found.', 404);

        if ($blog->image && Storage::disk('public')->exists($blog->image)) {
            Storage::disk('public')->delete($blog->image);
        }

        foreach ($blog->sections as $section) {
            if ($section->attachment && Storage::disk('public')->exists($section->attachment)) {
                Storage::disk('public')->delete($section->attachment);
            }
        }

        $blog->delete();
        return $this->sendResponse([], 'Blog deleted successfully.');
    }
}
