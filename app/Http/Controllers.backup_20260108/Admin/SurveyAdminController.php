<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerSurvey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SurveyAdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.survey.dashboard');
    }

    public function viewResponse($id)
    {
        $survey = CustomerSurvey::with('customer')->findOrFail($id);
        return view('admin.survey.response', compact('survey'));
    }

    public function toggleFlag(Request $request, $id)
    {
        $survey = CustomerSurvey::findOrFail($id);
        $survey->is_flagged = !$survey->is_flagged;
        $survey->save();
        return back()->with('success', 'Flag status updated.');
    }

    public function updateNotes(Request $request, $id)
    {
        $request->validate(['admin_notes' => 'nullable|string|max:1000']);
        $survey = CustomerSurvey::findOrFail($id);
        $survey->admin_notes = $request->admin_notes;
        $survey->save();
        return back()->with('success', 'Notes updated.');
    }

    public function deleteResponse($id)
    {
        $survey = CustomerSurvey::findOrFail($id);
        $survey->delete();
        return redirect()->route('admin.survey.dashboard')->with('success', 'Survey deleted.');
    }

    public function exportExcel(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $quarter = $request->get('quarter');
        $query = CustomerSurvey::query()->where('survey_year', $year);
        if ($quarter) $query->where('survey_quarter', $quarter);
        $surveys = $query->orderBy('created_at', 'desc')->get();
        $filename = 'survey-' . $year . ($quarter ? "-Q{$quarter}" : '') . '.csv';
        
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];
        $callback = function() use ($surveys) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Date', 'Company', 'Position', 'Services', 'Satisfaction', 'NPS', 'NPS Category']);
            foreach ($surveys as $s) {
                fputcsv($file, [$s->id, $s->response_date, $s->company_name ?? $s->display_name, $s->respondent_position, is_array($s->services_used) ? implode(', ', $s->services_used) : $s->services_used, $s->overall_satisfaction, $s->nps_score, $s->nps_category]);
            }
            fclose($file);
        };
        return Response::stream($callback, 200, $headers);
    }

    public function exportReport(Request $request, $format = 'pdf')
    {
        return back()->with('info', 'PDF export coming soon!');
    }
}
