<?php
namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.customer')]
class HsCodeExplorer extends Component
{
    use WithPagination;
    
    public $search = '';
    public $selectedChapter = '';
    public $selectedCode = null;
    public $hierarchy = [];
    
    protected $queryString = ['search' => ['except' => '']];
    
    public function updatedSearch()
    {
        $this->resetPage();
        $this->selectedCode = null;
        $this->hierarchy = [];
    }

    public function filterByChapter($chap)
    {
        $this->selectedChapter = $chap;
        $this->search = '';
        $this->resetPage();
    }

    public function setSearch($term)
    {
        $this->search = $term;
        $this->selectedChapter = '';
        $this->resetPage();
    }
    
    public function showHierarchy($hsCode)
    {
        $this->selectedCode = $hsCode;
        $this->hierarchy = $this->buildHierarchy($hsCode);
    }
    
    public function closeHierarchy()
    {
        $this->selectedCode = null;
        $this->hierarchy = [];
    }
    
    private function buildHierarchy($hsCode)
    {
        $result = [];
        
        $code = DB::table('hs_codes')->where('hs_code', $hsCode)->first();
        if (!$code) return [];
        
        $chapterNum = $code->chapter_number ?? substr(str_replace('.', '', $hsCode), 0, 2);
        
        // Section
        $chapter = DB::table('hs_chapters')->where('chapter_number', $chapterNum)->first();
        if ($chapter && $chapter->section_id) {
            $section = DB::table('hs_sections')->where('id', $chapter->section_id)->first();
            if ($section) {
                $result['section'] = [
                    'number' => $section->section_number,
                    'title_id' => $section->title_id,
                    'title_en' => $section->title_en
                ];
            }
        }
        
        // Chapter
        if ($chapter) {
            $result['chapter'] = [
                'number' => $chapter->chapter_number,
                'title_id' => $chapter->title_id,
                'title_en' => $chapter->title_en
            ];
        }
        
        // Build levels
        $cleanCode = str_replace('.', '', $hsCode);
        $levels = [];
        
        // Level 4 Heading
        if (strlen($cleanCode) >= 4) {
            $heading = substr($cleanCode, 0, 2) . '.' . substr($cleanCode, 2, 2);
            $hData = DB::table('hs_codes')->where('hs_code', $heading)->first();
            if ($hData) {
                $levels[] = ['code' => $heading, 'level' => 4, 'description_id' => $hData->description_id, 'description_en' => $hData->description_en ?? '', 'is_selected' => ($heading == $hsCode)];
            }
        }
        
        // Level 6 Subheading
        if (strlen($cleanCode) >= 6) {
            $sub = substr($cleanCode, 0, 4) . '.' . substr($cleanCode, 4, 2);
            $sData = DB::table('hs_codes')->where('hs_code', $sub)->first();
            if ($sData) {
                $levels[] = ['code' => $sub, 'level' => 6, 'description_id' => $sData->description_id, 'description_en' => $sData->description_en ?? '', 'is_selected' => ($sub == $hsCode)];
            }
        }
        
        // Level 8 National
        if (strlen($cleanCode) >= 8) {
            $nat = substr($cleanCode, 0, 4) . '.' . substr($cleanCode, 4, 2) . '.' . substr($cleanCode, 6, 2);
            $nData = DB::table('hs_codes')->where('hs_code', $nat)->first();
            if ($nData) {
                $levels[] = ['code' => $nat, 'level' => 8, 'description_id' => $nData->description_id, 'description_en' => $nData->description_en ?? '', 'is_selected' => ($nat == $hsCode)];
            }
        }
        
        $result['levels'] = $levels;
        
        // Siblings
        if ($code->parent_code) {
            $result['siblings'] = DB::table('hs_codes')
                ->where('parent_code', $code->parent_code)
                ->where('hs_code', '!=', $hsCode)
                ->orderBy('hs_code')->limit(10)->get();
        }
        
        return $result;
    }
    
    public function render()
    {
        $chapters = DB::table('hs_chapters')->orderBy('chapter_number')->get();
        
        $query = DB::table('hs_codes')
            ->select('hs_code', 'description_id', 'description_en', 'hs_level', 'chapter_number', 'parent_code', 'import_duty', 'export_duty');
            
        if (!empty(trim($this->selectedChapter))) {
            $query->where('chapter_number', $this->selectedChapter);
        }

        if (!empty(trim($this->search))) {
            $raw = trim($this->search);
            $searchTerm = '%' . $raw . '%';
            $cleanDigits = preg_replace('/[^0-9]/', '', $raw);

            $formattedDots = null;
            if (strlen($cleanDigits) === 8) {
                $formattedDots = substr($cleanDigits, 0, 4) . '.' . substr($cleanDigits, 4, 2) . '.' . substr($cleanDigits, 6, 2);
            } elseif (strlen($cleanDigits) === 6) {
                $formattedDots = substr($cleanDigits, 0, 4) . '.' . substr($cleanDigits, 4, 2);
            } elseif (strlen($cleanDigits) === 4) {
                $formattedDots = substr($cleanDigits, 0, 2) . '.' . substr($cleanDigits, 2, 2);
            }

            $query->where(function($q) use ($searchTerm, $cleanDigits, $formattedDots) {
                $q->where('hs_code', 'LIKE', $searchTerm)
                  ->orWhere('description_id', 'LIKE', $searchTerm)
                  ->orWhere('description_en', 'LIKE', $searchTerm);

                if (!empty($cleanDigits) && strlen($cleanDigits) >= 2) {
                    $q->orWhereRaw("REPLACE(hs_code, '.', '') LIKE ?", ['%' . $cleanDigits . '%']);
                }
                if ($formattedDots) {
                    $q->orWhere('hs_code', 'LIKE', '%' . $formattedDots . '%');
                }
            });
        }
        
        $results = $query->orderBy('hs_code')->paginate(20);
        
        return view('livewire.customer.hs-code-explorer', [
            'chapters' => $chapters,
            'results' => $results,
        ]);
    }

    public function getKumHs()
    {
        return DB::table('hs_kum')->orderBy('rule_number')->get();
    }
    
    public function getExplanatoryNote($hsCode)
    {
        $cleanCode = str_replace('.', '', $hsCode);
        
        $patterns = [];
        if (strlen($cleanCode) >= 8) {
            $patterns[] = substr($cleanCode, 0, 4) . '.' . substr($cleanCode, 4, 2) . '.' . substr($cleanCode, 6, 2);
            $patterns[] = substr($cleanCode, 0, 8);
        }
        if (strlen($cleanCode) >= 6) {
            $patterns[] = substr($cleanCode, 0, 4) . '.' . substr($cleanCode, 4, 2);
            $patterns[] = substr($cleanCode, 0, 6);
        }
        if (strlen($cleanCode) >= 4) {
            $patterns[] = substr($cleanCode, 0, 4);
            $patterns[] = substr($cleanCode, 0, 2) . '.' . substr($cleanCode, 2, 2);
        }
        
        foreach ($patterns as $pattern) {
            $note = DB::table('hs_explanatory_notes')->where('hs_code', $pattern)->first();
            if ($note) return $note;
        }
        
        return null;
    }

}