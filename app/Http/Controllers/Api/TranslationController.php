<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslationController extends Controller
{
    /**
     * Display a listing of translations with filtering and pagination.
     */
    public function index(Request $request): JsonResponse|StreamedResponse
    {
        $hasFilters = $request->filled(['locale', 'key', 'q', 'tag']);
        
        if (!$hasFilters) {
            return $this->fastAllTranslations($request);
        }

        try {
            $query = Translation::query()->with('tags:id,name');

            if ($request->filled('locale')) {
                $query->byLocale($request->string('locale'));
            }

            if ($request->filled('key')) {
                $query->byKey($request->string('key'));
            }

            if ($request->filled('q')) {
                $query->byContent($request->string('q'));
            }

            if ($request->filled('tag')) {
                $tags = collect(explode(',', $request->string('tag')))
                    ->map(fn ($tag) => trim($tag))
                    ->filter()
                    ->all();

                $query->byTags($tags);
            }

            $perPage = min((int) $request->integer('per_page', 50), 100);
            $translations = $query->paginate($perPage);

            return response()->json($translations);
        } catch (\Exception $e) {
            Log::error('Translation index error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch translations'], 500);
        }
    }

    /**
     * Fast streaming response for all translations without filters.
     */
    protected function fastAllTranslations(Request $request): StreamedResponse
    {
        $cacheKey = 'translations:all:json';
        $etagKey = $cacheKey . ':etag';

        $etag = Cache::get($etagKey);
        if ($etag && $request->header('If-None-Match') === $etag) {
            return response('', 304, ['ETag' => $etag]);
        }

        return response()->stream(function () {
            echo '[';
            $first = true;

            DB::table('translations')
                ->select('id', 'key', 'locale', 'value')
                ->orderBy('id')
                ->chunk(2000, function ($chunk) use (&$first) {
                    foreach ($chunk as $row) {
                        if (!$first) {
                            echo ',';
                        }
                        echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $first = false;
                    }
                });

            echo ']';
        }, 200, [
            'Content-Type' => 'application/json',
            'ETag' => $etag ?? md5('translations:' . now()),
            'Cache-Control' => 'public, max-age=0',
        ]);
    }

    /**
     * Store a newly created translation.
     */
    public function store(StoreTranslationRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $tagNames = $data['tags'] ?? [];
            unset($data['tags']);

            $translation = DB::transaction(function () use ($data, $tagNames) {
                $translation = Translation::create($data);

                if ($tagNames) {
                    $tagIds = collect($tagNames)
                        ->map(fn ($name) => ['name' => trim($name)])
                        ->map(fn ($attrs) => Tag::firstOrCreate($attrs)->id)
                        ->all();

                    $translation->tags()->sync($tagIds);
                }

                return $translation->load('tags:id,name');
            });

            return response()->json($translation, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Translation store error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create translation'], 500);
        }
    }

    /**
     * Display the specified translation.
     */
    public function show(Translation $translation): JsonResponse
    {
        try {
            return response()->json($translation->load('tags:id,name'));
        } catch (\Exception $e) {
            Log::error('Translation show error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch translation'], 500);
        }
    }

    /**
     * Update the specified translation.
     */
    public function update(UpdateTranslationRequest $request, Translation $translation): JsonResponse
    {
        try {
            $data = $request->validated();
            $tagNames = $data['tags'] ?? null;
            unset($data['tags']);

            DB::transaction(function () use ($translation, $data, $tagNames) {
                $translation->update($data);

                if (!is_null($tagNames)) {
                    $tagIds = collect($tagNames)
                        ->map(fn ($name) => Tag::firstOrCreate(['name' => trim($name)])->id)
                        ->all();

                    $translation->tags()->sync($tagIds);
                }
            });

            return response()->json($translation->fresh()->load('tags:id,name'));
        } catch (\Exception $e) {
            Log::error('Translation update error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update translation'], 500);
        }
    }

    /**
     * Remove the specified translation.
     */
    public function destroy(Translation $translation): JsonResponse
    {
        try {
            $translation->delete();
            return response()->json(['message' => 'Translation deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Translation destroy error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete translation'], 500);
        }
    }

    /**
     * Search translations by various criteria.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'q' => 'required|string|min:2',
                'locale' => 'string|size:2',
                'tag' => 'string',
            ]);

            $query = Translation::query()->with('tags:id,name');

            // Search in key and value
            $searchTerm = $request->string('q');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('key', 'like', '%' . $searchTerm . '%')
                  ->orWhere('value', 'like', '%' . $searchTerm . '%');
            });

            if ($request->filled('locale')) {
                $query->byLocale($request->string('locale'));
            }

            if ($request->filled('tag')) {
                $tags = collect(explode(',', $request->string('tag')))
                    ->map(fn ($tag) => trim($tag))
                    ->filter()
                    ->all();

                $query->byTags($tags);
            }

            $perPage = min((int) $request->integer('per_page', 20), 50);
            $results = $query->paginate($perPage);

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Translation search error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to search translations'], 500);
        }
    }
}
