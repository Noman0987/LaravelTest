<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export all translations for all locales in a structured JSON format.
     */
    public function allLocales(Request $request): StreamedResponse
    {
        try {
            $cacheKey = 'export:all';
            $etag = Cache::rememberForever($cacheKey . ':etag', fn () => Str::uuid()->toString());

            if ($request->header('If-None-Match') === $etag) {
                return response('', 304, ['ETag' => $etag]);
            }

            return response()->stream(function () {
                echo '{';
                $firstLocale = true;

                DB::table('translations')
                    ->select('locale', 'key', 'value')
                    ->orderBy('locale')
                    ->orderBy('key')
                    ->chunk(1000, function ($rows) use (&$firstLocale) {
                        $grouped = $rows->groupBy('locale');

                        foreach ($grouped as $locale => $records) {
                            if (!$firstLocale) {
                                echo ',';
                            }
                            $firstLocale = false;

                            echo '"' . $locale . '":' .
                                $records->pluck('value', 'key')->toJson(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                    });

                echo '}';
            }, 200, [
                'Content-Type' => 'application/json',
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=0',
            ]);
        } catch (\Exception $e) {
            Log::error('Export all locales error: ' . $e->getMessage());
            return response()->stream(function () {
                echo json_encode(['error' => 'Failed to export translations']);
            }, 500, ['Content-Type' => 'application/json']);
        }
    }

    /**
     * Export translations for a specific locale.
     */
    public function singleLocale(string $locale, Request $request): StreamedResponse
    {
        try {
            $cacheKey = "export:{$locale}";
            $etag = Cache::rememberForever("{$cacheKey}:etag", fn () => Str::random(32));
            
            if ($request->header('If-None-Match') === $etag) {
                return response('', 304, ['ETag' => $etag]);
            }

            return response()->stream(function () use ($locale) {
                echo '{';
                $first = true;

                DB::table('translations')
                    ->select('key', 'value')
                    ->where('locale', $locale)
                    ->orderBy('key')
                    ->chunk(1000, function ($chunk) use (&$first) {
                        foreach ($chunk as $row) {
                            if (!$first) {
                                echo ',';
                            }
                            echo '"' . $row->key . '":"' . addslashes($row->value) . '"';
                            $first = false;
                        }
                    });

                echo '}';
            }, 200, [
                'Content-Type' => 'application/json',
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=0',
            ]);
        } catch (\Exception $e) {
            Log::error("Export single locale error for {$locale}: " . $e->getMessage());
            return response()->stream(function () {
                echo json_encode(['error' => 'Failed to export translations']);
            }, 500, ['Content-Type' => 'application/json']);
        }
    }

    /**
     * Export translations filtered by tags.
     */
    public function byTags(Request $request): StreamedResponse
    {
        try {
            $request->validate([
                'tags' => 'required|string',
                'locale' => 'string|size:2',
            ]);

            $tags = collect(explode(',', $request->string('tags')))
                ->map(fn ($tag) => trim($tag))
                ->filter()
                ->all();

            $locale = $request->string('locale');
            $cacheKey = 'export:tags:' . md5(implode(',', $tags) . $locale);
            $etag = Cache::rememberForever("{$cacheKey}:etag", fn () => Str::random(32));

            if ($request->header('If-None-Match') === $etag) {
                return response('', 304, ['ETag' => $etag]);
            }

            return response()->stream(function () use ($tags, $locale) {
                echo '{';
                $firstLocale = true;

                $query = DB::table('translations')
                    ->join('tag_translation', 'translations.id', '=', 'tag_translation.translation_id')
                    ->join('tags', 'tag_translation.tag_id', '=', 'tags.id')
                    ->select('translations.locale', 'translations.key', 'translations.value')
                    ->whereIn('tags.name', $tags)
                    ->orderBy('translations.locale')
                    ->orderBy('translations.key');

                if ($locale) {
                    $query->where('translations.locale', $locale);
                }

                $query->chunk(1000, function ($rows) use (&$firstLocale) {
                    $grouped = $rows->groupBy('locale');

                    foreach ($grouped as $locale => $records) {
                        if (!$firstLocale) {
                            echo ',';
                        }
                        $firstLocale = false;

                        echo '"' . $locale . '":' .
                            $records->pluck('value', 'key')->toJson(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                });

                echo '}';
            }, 200, [
                'Content-Type' => 'application/json',
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=0',
            ]);
        } catch (\Exception $e) {
            Log::error('Export by tags error: ' . $e->getMessage());
            return response()->stream(function () {
                echo json_encode(['error' => 'Failed to export translations']);
            }, 500, ['Content-Type' => 'application/json']);
        }
    }

    /**
     * Get available locales.
     */
    public function locales(): \Illuminate\Http\JsonResponse
    {
        try {
            $locales = Cache::remember('export:locales', 3600, function () {
                return DB::table('translations')
                    ->select('locale')
                    ->distinct()
                    ->orderBy('locale')
                    ->pluck('locale');
            });

            return response()->json($locales);
        } catch (\Exception $e) {
            Log::error('Export locales error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch locales'], 500);
        }
    }
}
