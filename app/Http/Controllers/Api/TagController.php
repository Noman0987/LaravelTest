<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TagController extends Controller
{
    /**
     * Display a listing of tags.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Tag::query();

            if ($request->filled('search')) {
                $query->byName($request->string('search'));
            }

            $perPage = min((int) $request->integer('per_page', 50), 100);
            $tags = $query->select('id', 'name')
                ->orderBy('name')
                ->paginate($perPage);

            return response()->json($tags);
        } catch (\Exception $e) {
            Log::error('Tag index error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch tags'], 500);
        }
    }

    /**
     * Store a newly created tag.
     */
    public function store(StoreTagRequest $request): JsonResponse
    {
        try {
            $tag = Tag::firstOrCreate(['name' => trim($request->validated()['name'])]);
            return response()->json($tag, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Tag store error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create tag'], 500);
        }
    }

    /**
     * Display the specified tag.
     */
    public function show(Tag $tag): JsonResponse
    {
        try {
            return response()->json($tag->load('translations:id,key,locale,value'));
        } catch (\Exception $e) {
            Log::error('Tag show error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch tag'], 500);
        }
    }

    /**
     * Update the specified tag.
     */
    public function update(Request $request, Tag $tag): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:64|unique:tags,name,' . $tag->id,
            ]);

            $tag->update(['name' => trim($request->string('name'))]);
            return response()->json($tag);
        } catch (\Exception $e) {
            Log::error('Tag update error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update tag'], 500);
        }
    }

    /**
     * Remove the specified tag.
     */
    public function destroy(Tag $tag): JsonResponse
    {
        try {
            $tag->delete();
            return response()->json(['message' => 'Tag deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Tag destroy error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete tag'], 500);
        }
    }

    /**
     * Get all tags for dropdown/select purposes.
     */
    public function all(): JsonResponse
    {
        try {
            $tags = Tag::getAllCached();
            return response()->json($tags);
        } catch (\Exception $e) {
            Log::error('Tag all error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch tags'], 500);
        }
    }
}
