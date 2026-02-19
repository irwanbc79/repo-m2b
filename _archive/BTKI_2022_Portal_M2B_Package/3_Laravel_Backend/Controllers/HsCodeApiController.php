<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HsCode;
use App\Models\HsChapter;
use App\Models\HsGeneralRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HsCodeApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/hs-codes/search",
     *     summary="Search HS Codes",
     *     tags={"HS Code"},
     *     @OA\Parameter(name="q", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="mode", in="query", @OA\Schema(type="string", enum={"code", "description", "all"})),
     *     @OA\Parameter(name="chapter", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Search results")
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'mode' => 'in:code,description,all',
            'chapter' => 'nullable|string|size:2',
            'limit' => 'integer|min:1|max:100'
        ]);

        $keyword = $request->query('q');
        $mode = $request->query('mode', 'all');
        $chapter = $request->query('chapter');
        $limit = $request->query('limit', 20);

        $query = HsCode::active();

        // Apply chapter filter
        if ($chapter) {
            $query->byChapter($chapter);
        }

        // Apply search based on mode
        switch ($mode) {
            case 'code':
                $query->where('hs_code', 'LIKE', "{$keyword}%");
                break;
            
            case 'description':
                if (strlen($keyword) >= 3) {
                    $query->fullTextSearch($keyword);
                } else {
                    $query->search($keyword);
                }
                break;
            
            case 'all':
            default:
                $query->search($keyword);
                break;
        }

        $results = $query->orderBy('hs_code')
                        ->limit($limit)
                        ->get()
                        ->map(function($item) {
                            return [
                                'hs_code' => $item->hs_code,
                                'formatted_code' => $item->getFormattedCode(),
                                'level' => $item->hs_level,
                                'level_name' => $item->level_name,
                                'description_id' => $item->description_id,
                                'description_en' => $item->description_en,
                                'chapter_number' => $item->chapter_number,
                                'has_children' => $item->hasChildren(),
                                'has_explanatory_note' => $item->has_explanatory_note
                            ];
                        });

        // Log search
        HsCode::logSearch($keyword, $results->count());

        return response()->json([
            'success' => true,
            'data' => $results,
            'count' => $results->count(),
            'query' => $keyword
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/hs-codes/{hsCode}",
     *     summary="Get HS Code detail with hierarchy",
     *     tags={"HS Code"},
     *     @OA\Parameter(name="hsCode", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="HS Code detail"),
     *     @OA\Response(response=404, description="HS Code not found")
     * )
     */
    public function show(string $hsCode): JsonResponse
    {
        $code = HsCode::with(['parent', 'children', 'chapter', 'explanatoryNotes'])
                      ->where('hs_code', $hsCode)
                      ->first();

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'HS Code tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'hs_code' => $code->hs_code,
                'formatted_code' => $code->getFormattedCode(),
                'level' => $code->hs_level,
                'level_name' => $code->level_name,
                'description_id' => $code->description_id,
                'description_en' => $code->description_en,
                'chapter_number' => $code->chapter_number,
                'chapter_title' => $code->chapter->title_id ?? null,
                'notes' => $code->notes,
                'has_explanatory_note' => $code->has_explanatory_note,
                'explanatory_note_url' => $code->explanatory_note_url,
                'hierarchy' => $code->getHierarchyPath(),
                'parent' => $code->parent ? [
                    'hs_code' => $code->parent->hs_code,
                    'description_id' => $code->parent->description_id
                ] : null,
                'children' => $code->children->map(function($child) {
                    return [
                        'hs_code' => $child->hs_code,
                        'formatted_code' => $child->getFormattedCode(),
                        'description_id' => $child->description_id,
                        'description_en' => $child->description_en,
                        'level' => $child->hs_level
                    ];
                }),
                'explanatory_notes' => $code->explanatoryNotes->map(function($note) {
                    return [
                        'title' => $note->note_title,
                        'content' => $note->note_content,
                        'type' => $note->note_type,
                        'language' => $note->language
                    ];
                })
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/hs-codes/validate",
     *     summary="Validate HS Code format and existence",
     *     tags={"HS Code"},
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="hs_code", type="string", example="01.01.21.00")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Validation result")
     * )
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'hs_code' => 'required|string'
        ]);

        $hsCode = $request->input('hs_code');

        // Format validation
        if (!HsCode::validateFormat($hsCode)) {
            return response()->json([
                'valid' => false,
                'message' => 'Format HS Code tidak valid. Format yang benar: XX.XX.XX.XX',
                'format_example' => '01.01.21.00'
            ]);
        }

        // Database validation
        $code = HsCode::where('hs_code', $hsCode)
                      ->where('is_active', true)
                      ->first();

        if (!$code) {
            return response()->json([
                'valid' => false,
                'message' => 'HS Code tidak ditemukan atau tidak aktif',
                'suggestion' => 'Gunakan endpoint /search untuk mencari HS Code yang tersedia'
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => 'HS Code valid',
            'data' => [
                'hs_code' => $code->hs_code,
                'formatted_code' => $code->getFormattedCode(),
                'description_id' => $code->description_id,
                'description_en' => $code->description_en,
                'level' => $code->hs_level,
                'chapter_number' => $code->chapter_number
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/hs-codes/chapters",
     *     summary="Get all chapters",
     *     tags={"HS Code"},
     *     @OA\Response(response=200, description="List of chapters")
     * )
     */
    public function chapters(): JsonResponse
    {
        $chapters = HsChapter::orderBy('chapter_number')->get();

        return response()->json([
            'success' => true,
            'data' => $chapters->map(function($chapter) {
                return [
                    'chapter_number' => $chapter->chapter_number,
                    'title_id' => $chapter->title_id,
                    'title_en' => $chapter->title_en,
                    'section_id' => $chapter->section_id
                ];
            }),
            'count' => $chapters->count()
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/hs-codes/chapter/{chapterNumber}",
     *     summary="Get HS Codes by chapter",
     *     tags={"HS Code"},
     *     @OA\Parameter(name="chapterNumber", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="level", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="HS Codes in chapter")
     * )
     */
    public function byChapter(string $chapterNumber, Request $request): JsonResponse
    {
        $level = $request->query('level');

        $query = HsCode::where('chapter_number', $chapterNumber)
                      ->where('is_active', true);

        if ($level) {
            $query->byLevel($level);
        }

        $codes = $query->orderBy('hs_code')->get();

        return response()->json([
            'success' => true,
            'chapter_number' => $chapterNumber,
            'data' => $codes->map(function($code) {
                return [
                    'hs_code' => $code->hs_code,
                    'formatted_code' => $code->getFormattedCode(),
                    'level' => $code->hs_level,
                    'description_id' => $code->description_id,
                    'description_en' => $code->description_en
                ];
            }),
            'count' => $codes->count()
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/hs-codes/hierarchy/{hsCode}",
     *     summary="Get hierarchy path for HS Code",
     *     tags={"HS Code"},
     *     @OA\Parameter(name="hsCode", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Hierarchy path")
     * )
     */
    public function hierarchy(string $hsCode): JsonResponse
    {
        $code = HsCode::where('hs_code', $hsCode)->first();

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'HS Code tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'hs_code' => $hsCode,
            'hierarchy' => $code->getHierarchyPath()
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/hs-codes/general-rules",
     *     summary="Get general rules for interpretation",
     *     tags={"HS Code"},
     *     @OA\Parameter(name="language", in="query", @OA\Schema(type="string", enum={"id", "en"})),
     *     @OA\Response(response=200, description="General rules")
     * )
     */
    public function generalRules(Request $request): JsonResponse
    {
        $language = $request->query('language', 'id');

        $rules = HsGeneralRule::where('version', 'BTKI 2022 v1')
                              ->orderBy('rule_order')
                              ->get();

        return response()->json([
            'success' => true,
            'version' => 'BTKI 2022 v1',
            'data' => $rules->map(function($rule) use ($language) {
                return [
                    'order' => $rule->rule_order,
                    'title' => $rule->title,
                    'content' => $language === 'en' ? $rule->content_en : $rule->content_id
                ];
            }),
            'count' => $rules->count()
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/hs-codes/popular",
     *     summary="Get popular/most searched HS Codes",
     *     tags={"HS Code"},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=10)),
     *     @OA\Response(response=200, description="Popular searches")
     * )
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);

        $popular = HsCode::getMostViewed($limit);

        return response()->json([
            'success' => true,
            'data' => $popular->map(function($code) {
                return [
                    'hs_code' => $code->hs_code,
                    'formatted_code' => $code->getFormattedCode(),
                    'description_id' => $code->description_id,
                    'description_en' => $code->description_en,
                    'level' => $code->hs_level
                ];
            }),
            'count' => $popular->count()
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/hs-codes/autocomplete",
     *     summary="Autocomplete suggestions for HS Code search",
     *     tags={"HS Code"},
     *     @OA\Parameter(name="q", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", default=10)),
     *     @OA\Response(response=200, description="Autocomplete suggestions")
     * )
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'limit' => 'integer|min:1|max:50'
        ]);

        $keyword = $request->query('q');
        $limit = $request->query('limit', 10);

        $suggestions = HsCode::active()
                             ->where(function($q) use ($keyword) {
                                 $q->where('hs_code', 'LIKE', "{$keyword}%")
                                   ->orWhere('description_id', 'LIKE', "%{$keyword}%");
                             })
                             ->orderBy('hs_code')
                             ->limit($limit)
                             ->get(['hs_code', 'description_id', 'hs_level']);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions->map(function($item) {
                return [
                    'value' => $item->hs_code,
                    'label' => $item->hs_code . ' - ' . \Str::limit($item->description_id, 80),
                    'level' => $item->hs_level
                ];
            })
        ]);
    }
}
