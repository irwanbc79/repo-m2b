<?php

namespace App\Livewire\Admin;

use App\Models\CustomerSurvey;
use App\Services\SurveyAnalyticsService;
use Livewire\Component;
use Livewire\WithPagination;

class SurveyDashboard extends Component
{
    use WithPagination;

    protected $analyticsService;

    public $selectedYear;
    public $selectedQuarter = '';
    public $filterNpsCategory = '';
    public $search = '';
    public $viewMode = 'overview'; // overview, responses, reports

    public function boot(SurveyAnalyticsService $service)
    {
        $this->analyticsService = $service;
    }

    public function mount()
    {
        $this->selectedYear = date('Y');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function getDashboardDataProperty()
    {
        return $this->analyticsService->getDashboardOverview(
            $this->selectedYear,
            $this->selectedQuarter ?: null
        );
    }

    public function getResponsesProperty()
    {
        $query = CustomerSurvey::with('customer')
            ->where('survey_year', $this->selectedYear)
            ->orderBy('response_date', 'desc');

        if ($this->selectedQuarter) {
            $query->where('survey_quarter', $this->selectedQuarter);
        }

        if ($this->filterNpsCategory) {
            if ($this->filterNpsCategory === 'promoters') {
                $query->promoters();
            } elseif ($this->filterNpsCategory === 'passives') {
                $query->passives();
            } elseif ($this->filterNpsCategory === 'detractors') {
                $query->detractors();
            }
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('company_name', 'like', '%' . $this->search . '%')
                  ->orWhere('appreciate_most', 'like', '%' . $this->search . '%')
                  ->orWhere('needs_improvement', 'like', '%' . $this->search . '%');
            });
        }

        return $query->paginate(10);
    }

    public function flagResponse($id)
    {
        $survey = CustomerSurvey::findOrFail($id);
        $survey->is_flagged = !$survey->is_flagged;
        $survey->save();

        session()->flash('message', 'Survey ' . ($survey->is_flagged ? 'ditandai' : 'unmarked') . ' untuk review.');
    }

    public function deleteResponse($id)
    {
        if (auth()->user()->roles && str_contains(auth()->user()->roles, 'admin')) {
            CustomerSurvey::findOrFail($id)->delete();
            session()->flash('message', 'Survey berhasil dihapus.');
        }
    }

    public function exportExcel()
    {
        // Will be implemented with export service
        session()->flash('message', 'Export sedang diproses...');
    }

    public function render()
    {
        return view('livewire.admin.survey-dashboard', [
            'dashboardData' => $this->dashboardData,
            'responses' => $this->viewMode === 'responses' ? $this->responses : null,
            'years' => range(date('Y'), date('Y') - 5),
            'quarters' => ['Q1', 'Q2', 'Q3', 'Q4'],
        ])->layout('layouts.admin');
    }
}
