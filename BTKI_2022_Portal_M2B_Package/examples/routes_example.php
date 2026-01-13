<?php

/**
 * Contoh Konfigurasi Routes untuk HS Code Integration
 * Copy code ini ke routes/web.php dan routes/api.php Anda
 */

// ============================================
// WEB ROUTES (routes/web.php)
// ============================================

use App\Http\Livewire\HsCodeSearch;

// HS Code Search Page (Livewire)
Route::get('/hs-code', HsCodeSearch::class)->name('hs-code.search');

// General Rules Page (Optional)
Route::get('/hs-code/general-rules', function() {
    $rules = \App\Models\HsGeneralRule::orderBy('rule_order')->get();
    return view('hs-code.general-rules', compact('rules'));
})->name('hs-code.general-rules');

// Explanatory Notes Page (Optional)
Route::get('/hs-code/explanatory-notes/{hsCode?}', function($hsCode = null) {
    if ($hsCode) {
        $code = \App\Models\HsCode::with('explanatoryNotes')
                                  ->where('hs_code', $hsCode)
                                  ->firstOrFail();
        return view('hs-code.explanatory-notes-detail', compact('code'));
    }
    return view('hs-code.explanatory-notes-index');
})->name('hs-code.explanatory-notes');

// User Favorites (requires authentication)
Route::middleware(['auth'])->group(function() {
    Route::get('/hs-code/favorites', function() {
        $favorites = auth()->user()->hsFavorites()->with('hsCode')->get();
        return view('hs-code.favorites', compact('favorites'));
    })->name('hs-code.favorites');
});


// ============================================
// API ROUTES (routes/api.php)
// ============================================

use App\Http\Controllers\Api\HsCodeApiController;

// Public API endpoints (no authentication required)
Route::prefix('v1')->group(function() {
    
    // Search HS Codes
    // GET /api/v1/hs-codes/search?q=ikan&mode=all&limit=20
    Route::get('/hs-codes/search', [HsCodeApiController::class, 'search']);
    
    // Get HS Code detail with hierarchy
    // GET /api/v1/hs-codes/03.01.11.10
    Route::get('/hs-codes/{hsCode}', [HsCodeApiController::class, 'show']);
    
    // Validate HS Code
    // POST /api/v1/hs-codes/validate
    // Body: {"hs_code": "03.01.11.10"}
    Route::post('/hs-codes/validate', [HsCodeApiController::class, 'validate']);
    
    // Get all chapters
    // GET /api/v1/hs-codes/chapters
    Route::get('/hs-codes/chapters', [HsCodeApiController::class, 'chapters']);
    
    // Get HS Codes by chapter
    // GET /api/v1/hs-codes/chapter/03?level=6
    Route::get('/hs-codes/chapter/{chapterNumber}', [HsCodeApiController::class, 'byChapter']);
    
    // Get hierarchy path for HS Code
    // GET /api/v1/hs-codes/hierarchy/03.01.11.10
    Route::get('/hs-codes/hierarchy/{hsCode}', [HsCodeApiController::class, 'hierarchy']);
    
    // Get general rules for interpretation
    // GET /api/v1/hs-codes/general-rules?language=id
    Route::get('/hs-codes/general-rules', [HsCodeApiController::class, 'generalRules']);
    
    // Autocomplete suggestions
    // GET /api/v1/hs-codes/autocomplete?q=ikan&limit=10
    Route::get('/hs-codes/autocomplete', [HsCodeApiController::class, 'autocomplete']);
});

// Protected API endpoints (requires authentication)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function() {
    
    // Get popular/most viewed HS Codes
    // GET /api/v1/hs-codes/popular?limit=10
    Route::get('/hs-codes/popular', [HsCodeApiController::class, 'popular']);
    
    // User's favorite HS Codes
    Route::get('/hs-codes/favorites', function() {
        return response()->json([
            'success' => true,
            'data' => auth()->user()->hsFavorites()->with('hsCode')->get()
        ]);
    });
    
    // Add to favorites
    Route::post('/hs-codes/favorites/{hsCode}', function($hsCode) {
        \App\Models\HsFavorite::firstOrCreate([
            'user_id' => auth()->id(),
            'hs_code' => $hsCode
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Added to favorites'
        ]);
    });
    
    // Remove from favorites
    Route::delete('/hs-codes/favorites/{hsCode}', function($hsCode) {
        \App\Models\HsFavorite::where('user_id', auth()->id())
                              ->where('hs_code', $hsCode)
                              ->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Removed from favorites'
        ]);
    });
});


// ============================================
// OPTIONAL: Rate Limiting
// ============================================

// Add to RouteServiceProvider or routes file
Route::middleware(['throttle:60,1'])->group(function() {
    // Your API routes here
    // This limits to 60 requests per minute
});


// ============================================
// OPTIONAL: API Versioning
// ============================================

// Version 2 routes (for future updates)
Route::prefix('v2')->group(function() {
    // Future API endpoints
});


// ============================================
// NAVIGATION MENU (resources/views/layouts/app.blade.php)
// ============================================

/*
Add this to your navigation menu:

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('hs-code.*') ? 'active' : '' }}" 
       href="{{ route('hs-code.search') }}">
        <i class="fas fa-search"></i> HS Code
    </a>
</li>

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
        <i class="fas fa-book"></i> Referensi
    </a>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="{{ route('hs-code.general-rules') }}">
                Ketentuan Umum
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route('hs-code.explanatory-notes') }}">
                Explanatory Notes
            </a>
        </li>
        @auth
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item" href="{{ route('hs-code.favorites') }}">
                <i class="fas fa-star"></i> Favorit Saya
            </a>
        </li>
        @endauth
    </ul>
</li>
*/


// ============================================
// USAGE EXAMPLES
// ============================================

/*
// Example 1: Search from another controller
use App\Models\HsCode;

$results = HsCode::active()
    ->search('ikan')
    ->limit(20)
    ->get();

// Example 2: Get hierarchy
$code = HsCode::where('hs_code', '03.01.11.10')->first();
$hierarchy = $code->getHierarchyPath();

// Example 3: Validate HS Code in form request
public function rules()
{
    return [
        'hs_code' => [
            'required',
            function ($attribute, $value, $fail) {
                if (!HsCode::where('hs_code', $value)->where('is_active', true)->exists()) {
                    $fail('HS Code tidak valid');
                }
            }
        ]
    ];
}

// Example 4: Get children of HS Code
$parent = HsCode::where('hs_code', '03.01')->first();
$children = $parent->children;

// Example 5: Full-text search
$results = HsCode::fullTextSearch('ikan laut')->get();
*/
