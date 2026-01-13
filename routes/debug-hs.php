<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/debug-hs-codes', function() {
    try {
        $component = new \App\Livewire\HsCode\Explorer();
        $chapters = DB::table('hs_chapters')->orderBy('chapter_number')->get();
        
        return response()->json([
            'status' => 'ok',
            'component_exists' => true,
            'chapters_count' => $chapters->count(),
            'component_properties' => [
                'search' => $component->search,
                'viewMode' => $component->viewMode,
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->middleware('auth');
