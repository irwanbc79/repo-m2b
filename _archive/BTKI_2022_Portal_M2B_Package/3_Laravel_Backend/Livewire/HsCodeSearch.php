<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\HsCode;
use App\Models\HsChapter;
use Illuminate\Support\Collection;

class HsCodeSearch extends Component
{
    // Search parameters
    public string $search = '';
    public string $searchMode = 'code'; // 'code', 'description', 'all'
    public ?string $selectedChapter = null;
    public ?int $selectedLevel = null;
    public string $language = 'id'; // 'id' or 'en'
    
    // Results
    public Collection $results;
    public ?HsCode $selectedCode = null;
    public Collection $hierarchy;
    public Collection $children;
    
    // UI state
    public bool $showResults = false;
    public bool $showHierarchy = false;
    public bool $isLoading = false;
    
    // Pagination
    public int $perPage = 20;
    
    // Available chapters for filter
    public Collection $chapters;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'searchMode' => ['except' => 'code'],
        'selectedChapter' => ['except' => null],
        'language' => ['except' => 'id']
    ];

    public function mount()
    {
        $this->results = collect();
        $this->hierarchy = collect();
        $this->children = collect();
        $this->chapters = HsChapter::orderBy('chapter_number')->get();
        
        // Auto-search if query string exists
        if ($this->search) {
            $this->performSearch();
        }
    }

    public function updated($field)
    {
        // Auto-search when typing (with debounce)
        if ($field === 'search' && strlen($this->search) >= 2) {
            $this->performSearch();
        }
    }

    public function updatedSearchMode()
    {
        if ($this->search) {
            $this->performSearch();
        }
    }

    public function updatedSelectedChapter()
    {
        if ($this->search) {
            $this->performSearch();
        }
    }

    public function performSearch()
    {
        if (strlen($this->search) < 2) {
            $this->results = collect();
            $this->showResults = false;
            return;
        }

        $this->isLoading = true;

        try {
            $query = HsCode::active();

            // Apply chapter filter
            if ($this->selectedChapter) {
                $query->byChapter($this->selectedChapter);
            }

            // Apply level filter
            if ($this->selectedLevel) {
                $query->byLevel($this->selectedLevel);
            }

            // Apply search based on mode
            switch ($this->searchMode) {
                case 'code':
                    $query->where('hs_code', 'LIKE', "{$this->search}%");
                    break;
                
                case 'description':
                    // Use full-text search for better performance
                    if (strlen($this->search) >= 3) {
                        $query->fullTextSearch($this->search);
                    } else {
                        $query->search($this->search);
                    }
                    break;
                
                case 'all':
                default:
                    $query->where(function($q) {
                        $q->where('hs_code', 'LIKE', "{$this->search}%")
                          ->orWhere('description_id', 'LIKE', "%{$this->search}%")
                          ->orWhere('description_en', 'LIKE', "%{$this->search}%");
                    });
                    break;
            }

            $this->results = $query->orderBy('hs_code')
                                  ->limit(50)
                                  ->get();

            $this->showResults = true;

            // Log search
            HsCode::logSearch($this->search, $this->results->count());

        } catch (\Exception $e) {
            \Log::error('HS Code search error: ' . $e->getMessage());
            $this->results = collect();
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectCode(string $hsCode)
    {
        try {
            $this->selectedCode = HsCode::with(['parent', 'children', 'chapter', 'explanatoryNotes'])
                                        ->where('hs_code', $hsCode)
                                        ->firstOrFail();
            
            // Get hierarchy
            $this->hierarchy = collect($this->selectedCode->getHierarchyPath());
            
            // Get children
            $this->children = $this->selectedCode->children;
            
            $this->showHierarchy = true;
            $this->showResults = false;

            // Log selection
            HsCode::logSearch($this->search, $this->results->count(), $hsCode);

            // Emit event for other components
            $this->emit('hsCodeSelected', $hsCode);

        } catch (\Exception $e) {
            \Log::error('Error selecting HS Code: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat memuat HS Code');
        }
    }

    public function clearSelection()
    {
        $this->selectedCode = null;
        $this->hierarchy = collect();
        $this->children = collect();
        $this->showHierarchy = false;
        $this->showResults = true;
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->selectedChapter = null;
        $this->selectedLevel = null;
        $this->results = collect();
        $this->showResults = false;
        $this->clearSelection();
    }

    public function toggleFavorite()
    {
        if (!auth()->check()) {
            session()->flash('error', 'Silakan login terlebih dahulu');
            return;
        }

        if (!$this->selectedCode) {
            return;
        }

        $favorite = \App\Models\HsFavorite::where('user_id', auth()->id())
                                          ->where('hs_code', $this->selectedCode->hs_code)
                                          ->first();

        if ($favorite) {
            $favorite->delete();
            session()->flash('message', 'HS Code dihapus dari favorit');
        } else {
            \App\Models\HsFavorite::create([
                'user_id' => auth()->id(),
                'hs_code' => $this->selectedCode->hs_code
            ]);
            session()->flash('message', 'HS Code ditambahkan ke favorit');
        }

        $this->emit('favoriteToggled');
    }

    public function exportResults()
    {
        if ($this->results->isEmpty()) {
            session()->flash('error', 'Tidak ada hasil untuk diekspor');
            return;
        }

        $filename = 'hs_code_search_' . date('Y-m-d_His') . '.csv';
        $filepath = storage_path('app/public/' . $filename);

        $file = fopen($filepath, 'w');
        
        // Header
        fputcsv($file, ['HS Code', 'Level', 'Deskripsi ID', 'Description EN', 'Chapter']);
        
        // Data
        foreach ($this->results as $result) {
            fputcsv($file, [
                $result->hs_code,
                $result->level_name,
                $result->description_id,
                $result->description_en,
                $result->chapter_number
            ]);
        }
        
        fclose($file);

        return response()->download($filepath)->deleteFileAfterSend();
    }

    public function render()
    {
        return view('livewire.hs-code-search', [
            'popularSearches' => HsCode::getPopularSearches(5),
            'isFavorite' => $this->selectedCode && auth()->check() 
                ? \App\Models\HsFavorite::where('user_id', auth()->id())
                                        ->where('hs_code', $this->selectedCode->hs_code)
                                        ->exists()
                : false
        ]);
    }
}
