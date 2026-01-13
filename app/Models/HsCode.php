<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class HsCode extends Model
{
    protected $fillable = [
        'hs_code',
        'hs_level',
        'parent_code',
        'description_id',
        'description_en',
        'chapter_number',
        'section_number',
        'is_active',
        'effective_date',
        'notes',
        'has_explanatory_note',
        'explanatory_note_url',
        'explanatory_note_content',
        'import_batch_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_explanatory_note' => 'boolean',
        'effective_date' => 'date'
    ];

    protected $appends = ['level_name', 'short_description'];

    /**
     * Relasi ke parent HS Code
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(HsCode::class, 'parent_code', 'hs_code');
    }

    /**
     * Relasi ke children HS Codes
     */
    public function children(): HasMany
    {
        return $this->hasMany(HsCode::class, 'parent_code', 'hs_code')
            ->where('is_active', true)
            ->orderBy('hs_code');
    }

    /**
     * Relasi ke Chapter
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(HsChapter::class, 'chapter_number', 'chapter_number');
    }

    /**
     * Relasi ke Explanatory Notes
     */
    public function explanatoryNotes(): HasMany
    {
        return $this->hasMany(HsExplanatoryNote::class, 'hs_code', 'hs_code');
    }

    /**
     * Relasi ke Favorites
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(HsFavorite::class, 'hs_code', 'hs_code');
    }

    /**
     * Scope untuk filter by level
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('hs_level', $level);
    }

    /**
     * Scope untuk filter active only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk filter by chapter
     */
    public function scopeByChapter($query, string $chapterNumber)
    {
        return $query->where('chapter_number', $chapterNumber);
    }

    /**
     * Scope untuk search (basic)
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function($q) use ($keyword) {
            $q->where('hs_code', 'LIKE', "%{$keyword}%")
              ->orWhere('description_id', 'LIKE', "%{$keyword}%")
              ->orWhere('description_en', 'LIKE', "%{$keyword}%");
        });
    }

    /**
     * Scope untuk full-text search (lebih cepat untuk data besar)
     */
    public function scopeFullTextSearch($query, string $keyword)
    {
        return $query->whereRaw(
            "MATCH(description_id, description_en) AGAINST(? IN BOOLEAN MODE)",
            [$keyword]
        );
    }

    /**
     * Scope untuk mendapatkan tree hierarki
     */
    public function scopeWithHierarchy($query)
    {
        return $query->with(['parent', 'children']);
    }

    /**
     * Get level name attribute
     */
    protected function levelName(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->hs_level) {
                2 => 'Bab / Chapter',
                4 => 'Pos / Heading',
                6 => 'Subpos / Subheading',
                8 => 'Detail',
                10 => 'Subdetail',
                default => 'Unknown'
            }
        );
    }

    /**
     * Get short description (truncated)
     */
    protected function shortDescription(): Attribute
    {
        return Attribute::make(
            get: fn () => \Str::limit($this->description_id, 100)
        );
    }

    /**
     * Get full hierarchy path (breadcrumb)
     */
    public function getHierarchyPath(): array
    {
        $path = collect([$this]);
        $current = $this;

        // Traverse up to get parents
        while ($current->parent) {
            $path->prepend($current->parent);
            $current = $current->parent;
        }

        return $path->map(function($item) {
            return [
                'hs_code' => $item->hs_code,
                'description' => $item->description_id,
                'level' => $item->hs_level
            ];
        })->toArray();
    }

    /**
     * Check if this HS Code has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get all descendants (recursive)
     */
    public function getAllDescendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }
        
        return $descendants;
    }

    /**
     * Format HS Code for display
     */
    public function getFormattedCode(): string
    {
        $digits = str_replace('.', '', $this->hs_code);
        
        return match($this->hs_level) {
            2 => substr($digits, 0, 2),
            4 => substr($digits, 0, 2) . '.' . substr($digits, 2, 2),
            6 => substr($digits, 0, 2) . '.' . substr($digits, 2, 2) . '.' . substr($digits, 4, 2),
            8 => substr($digits, 0, 2) . '.' . substr($digits, 2, 2) . '.' . substr($digits, 4, 2) . '.' . substr($digits, 6, 2),
            10 => substr($digits, 0, 2) . '.' . substr($digits, 2, 2) . '.' . substr($digits, 4, 2) . '.' . substr($digits, 6, 2) . '.' . substr($digits, 8, 2),
            default => $this->hs_code
        };
    }

    /**
     * Validate HS Code format
     */
    public static function validateFormat(string $hsCode): bool
    {
        // Remove dots and check if numeric
        $digits = str_replace('.', '', $hsCode);
        
        if (!is_numeric($digits)) {
            return false;
        }
        
        // Check length (2, 4, 6, 8, or 10 digits)
        $length = strlen($digits);
        return in_array($length, [2, 4, 6, 8, 10]);
    }

    /**
     * Log search activity
     */
    public static function logSearch(string $query, int $resultCount, ?string $selectedHsCode = null)
    {
        HsSearchLog::create([
            'search_query' => $query,
            'result_count' => $resultCount,
            'selected_hs_code' => $selectedHsCode,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Get popular searches
     */
    public static function getPopularSearches(int $limit = 10): array
    {
        return HsSearchLog::select('search_query')
            ->selectRaw('COUNT(*) as search_count')
            ->groupBy('search_query')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->pluck('search_query')
            ->toArray();
    }

    /**
     * Get frequently accessed HS Codes
     */
    public static function getMostViewed(int $limit = 10)
    {
        return HsSearchLog::select('selected_hs_code')
            ->selectRaw('COUNT(*) as view_count')
            ->whereNotNull('selected_hs_code')
            ->groupBy('selected_hs_code')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get()
            ->map(function($log) {
                return HsCode::where('hs_code', $log->selected_hs_code)->first();
            })
            ->filter();
    }
}
