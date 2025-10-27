<?php

// namespace App\Http\Controllers\Api;

// use App\Models\Blog;
// use App\Models\BlogSection;
// use Illuminate\Support\Facades\Storage;

// class BlogSectionController extends BaseController
// {
//     /**
//      * Delete a specific section or its attachment.
//      *
//      * Endpoint: DELETE /api/blogs/{blog}/sections/{section}
//      */
//     public function destroy(Blog $blog, BlogSection $section)
//     {
//         // Check if the section belongs to the blog
//         if ($section->blog_id !== $blog->id) {
//             return $this->sendError('This section does not belong to the specified blog.', 403);
//         }

//         // Delete the attachment file (if exists)
//         if ($section->attachment && Storage::disk('public')->exists($section->attachment)) {
//             Storage::disk('public')->delete($section->attachment);
//         }

//         // Delete the section itself
//         $section->delete();

//         return $this->sendResponse([], 'Blog section deleted successfully.');
//     }
//     public function deleteAttachment(Blog $blog, BlogSection $section)
//     {
//         if ($section->blog_id !== $blog->id) {
//             return $this->sendError('This section does not belong to the specified blog.', 403);
//         }

//         if ($section->attachment && Storage::disk('public')->exists($section->attachment)) {
//             Storage::disk('public')->delete($section->attachment);
//             $section->update(['attachment' => null]);
//         }

//         return $this->sendResponse([], 'Attachment deleted successfully.');
//     }
// }
