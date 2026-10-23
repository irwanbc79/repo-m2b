<?php

namespace App\Livewire\HsCode;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.admin')]
class Explorer extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedChapter = '';
    public $selectedLevel = ''; // '', 4, 6, 8
    public $selectedCode = null;
    public $hierarchy = [];
    public $showKumModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedChapter' => ['except' => ''],
        'selectedLevel' => ['except' => '']
    ];

    public function updatedSearch()
    {
        $this->resetPage();
        $this->selectedCode = null;
        $this->hierarchy = [];
    }

    public function updatedSelectedChapter()
    {
        $this->resetPage();
    }

    public function updatedSelectedLevel()
    {
        $this->resetPage();
    }

    public function filterByChapter($chapterNumber)
    {
        $this->selectedChapter = (string)$chapterNumber;
        $this->resetPage();
    }

    public function filterByLevel($level)
    {
        $this->selectedLevel = (string)$level;
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->selectedChapter = '';
        $this->selectedLevel = '';
        $this->selectedCode = null;
        $this->hierarchy = [];
        $this->resetPage();
    }

    public function toggleKumModal()
    {
        $this->showKumModal = !$this->showKumModal;
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
        $cleanCode = preg_replace('/\D+/', '', $hsCode);

        $code = DB::table('hs_codes')
            ->where(function($q) use ($hsCode, $cleanCode) {
                $q->where('hs_code', $hsCode)
                  ->orWhereRaw("REPLACE(hs_code, '.', '') = ?", [$cleanCode]);
            })
            ->first();
        if (!$code) return [];

        $result['current'] = $code;
        $chapterNum = $code->chapter_number ?? substr($cleanCode, 0, 2);

        // Section & Chapter
        $chapter = DB::table('hs_chapters')->where('chapter_number', $chapterNum)->first();
        if ($chapter && $chapter->section_id) {
            $section = DB::table('hs_sections')->where('id', $chapter->section_id)->first();
            if ($section) {
                $result['section'] = [
                    'number' => $section->section_number,
                    'title_id' => $section->title_id,
                    'title_en' => $section->title_en,
                    'notes' => $section->section_notes ?? $section->notes
                ];
            }
        }

        if ($chapter) {
            $result['chapter'] = [
                'number' => $chapter->chapter_number,
                'title_id' => $chapter->title_id,
                'title_en' => $chapter->title_en,
                'notes' => $chapter->chapter_notes ?? $chapter->notes
            ];
        }

        // Build levels hierarchy
        $levels = [];

        // Level 4 Heading (Format: xxxx, tanpa titik)
        if (strlen($cleanCode) >= 4) {
            $headingClean = substr($cleanCode, 0, 4);
            $hData = DB::table('hs_codes')
                ->where(function($q) use ($headingClean) {
                    $q->where('hs_code', $headingClean)
                      ->orWhere('hs_code', substr($headingClean, 0, 2) . '.' . substr($headingClean, 2, 2))
                      ->orWhereRaw("REPLACE(hs_code, '.', '') = ?", [$headingClean]);
                })
                ->first();
            if ($hData) {
                $levels[] = [
                    'code' => $headingClean,
                    'level' => 4,
                    'level_label' => 'Pos (Heading)',
                    'description_id' => $hData->description_id,
                    'description_en' => $hData->description_en ?? '',
                    'is_selected' => ($headingClean === $cleanCode)
                ];
            }
        }

        // Level 6 Subheading (Format: xxxx.xx)
        if (strlen($cleanCode) >= 6) {
            $subClean = substr($cleanCode, 0, 6);
            $subFormatted = substr($cleanCode, 0, 4) . '.' . substr($cleanCode, 4, 2);
            $sData = DB::table('hs_codes')
                ->where(function($q) use ($subFormatted, $subClean) {
                    $q->where('hs_code', $subFormatted)
                      ->orWhere('hs_code', substr($subClean, 0, 2) . '.' . substr($subClean, 2, 2) . '.' . substr($subClean, 4, 2))
                      ->orWhereRaw("REPLACE(hs_code, '.', '') = ?", [$subClean]);
                })
                ->first();
            if ($sData) {
                $levels[] = [
                    'code' => $subFormatted,
                    'level' => 6,
                    'level_label' => 'Subpos (Subheading)',
                    'description_id' => $sData->description_id,
                    'description_en' => $sData->description_en ?? '',
                    'is_selected' => ($subClean === $cleanCode)
                ];
            }
        }

        // Level 8 National (Format: xxxx.xx.xx)
        if (strlen($cleanCode) >= 8) {
            $natClean = substr($cleanCode, 0, 8);
            $natFormatted = substr($cleanCode, 0, 4) . '.' . substr($cleanCode, 4, 2) . '.' . substr($cleanCode, 6, 2);
            $nData = DB::table('hs_codes')
                ->where(function($q) use ($natFormatted, $natClean) {
                    $q->where('hs_code', $natFormatted)
                      ->orWhere('hs_code', substr($natClean, 0, 2) . '.' . substr($natClean, 2, 2) . '.' . substr($natClean, 4, 2) . '.' . substr($natClean, 6, 2))
                      ->orWhereRaw("REPLACE(hs_code, '.', '') = ?", [$natClean]);
                })
                ->first();
            if ($nData) {
                $levels[] = [
                    'code' => $natFormatted,
                    'level' => 8,
                    'level_label' => 'Pos Tarif Nasional (8-Digit)',
                    'description_id' => $nData->description_id,
                    'description_en' => $nData->description_en ?? '',
                    'import_duty' => $nData->import_duty,
                    'export_duty' => $nData->export_duty,
                    'ppn' => $nData->ppn,
                    'pph_api' => $nData->pph_api,
                    'pph_non_api' => $nData->pph_non_api,
                    'is_selected' => ($natClean === $cleanCode)
                ];
            }
        }

        $result['levels'] = $levels;

        // Siblings (same parent)
        if ($code->parent_code) {
            $result['siblings'] = DB::table('hs_codes')
                ->where('parent_code', $code->parent_code)
                ->where('hs_code', '!=', $hsCode)
                ->orderBy('hs_code')
                ->limit(12)
                ->get();
        }

        return $result;
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
            $note = DB::table('hs_explanatory_notes')
                ->where('hs_code', $pattern)
                ->first();
            if ($note) return $note;
        }

        return null;
    }

    public function render()
    {
        $chapters = DB::table('hs_chapters')->orderBy('chapter_number')->get();

        $query = DB::table('hs_codes')
            ->select([
                'hs_code',
                'description_id',
                'description_en',
                'description_full',
                'hs_level',
                'chapter_number',
                'parent_code',
                'import_duty',
                'export_duty',
                'ppn',
                'ppnbm',
                'pph_api',
                'pph_non_api'
            ]);

        // Filter Chapter
        if (!empty(trim($this->selectedChapter))) {
            $chap = str_pad(trim($this->selectedChapter), 2, '0', STR_PAD_LEFT);
            $query->where('chapter_number', $chap);
        }

        // Filter Level
        if (!empty(trim($this->selectedLevel))) {
            $query->where('hs_level', (int)$this->selectedLevel);
        }

        // Search text / code
        if (!empty(trim($this->search))) {
            $raw = trim($this->search);
            $cleanDigits = preg_replace('/[^0-9]/', '', $raw);
            $searchTerm = '%' . $raw . '%';

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
                  ->orWhere('description_en', 'LIKE', $searchTerm)
                  ->orWhere('description_full', 'LIKE', $searchTerm);

                if (!empty($cleanDigits) && strlen($cleanDigits) >= 2) {
                    $q->orWhereRaw("REPLACE(hs_code, '.', '') LIKE ?", ['%' . $cleanDigits . '%']);
                }
                if ($formattedDots) {
                    $q->orWhere('hs_code', 'LIKE', '%' . $formattedDots . '%');
                }
            });
        }

        $results = $query->orderBy('hs_code')->paginate(25);

        return view('livewire.hs-code.explorer-styled', [
            'chapters' => $chapters,
            'results' => $results,
            'totalHsCodes' => DB::table('hs_codes')->count(),
            'kumRules' => $this->getKumHs(),
        ]);
    }
}
