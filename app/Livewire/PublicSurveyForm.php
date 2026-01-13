<?php

namespace App\Livewire;

use App\Models\CustomerSurvey;
use App\Models\Customer;
use Livewire\Component;
use Illuminate\Support\Facades\Request;

class PublicSurveyForm extends Component
{
    // Step management
    public $currentStep = 1;
    public $totalSteps = 6;
    public $startTime;
    
    // A. RESPONDER INFO
    public $is_anonymous = false;
    public $company_name = '';
    public $respondent_position = '';
    public $respondent_position_other = '';
    public $services_used = [];
    
    // B. SATISFACTION
    public $overall_satisfaction;
    public $service_fit_needs;
    
    // C. OPERATIONAL QUALITY
    public $timely_delivery;
    public $shipment_info_clarity;
    public $document_accuracy;
    public $problem_handling;
    public $coordination_quality;
    
    // D. COMMUNICATION
    public $responsiveness;
    public $explanation_clarity;
    public $staff_professionalism;
    public $contact_ease;
    
    // E. PRICING
    public $price_fairness;
    public $cost_transparency;
    public $invoice_accuracy;
    
    // F. PORTAL
    public $portal_used = false;
    public $portal_ease_of_use;
    public $portal_info_clarity;
    public $portal_usefulness;
    
    // G. LOYALTY
    public $likelihood_reuse;
    public $nps_score;
    
    // H. OPEN FEEDBACK
    public $appreciate_most = '';
    public $needs_improvement = '';
    public $future_features = '';
    
    // I. FOLLOW-UP
    public $willing_to_contact = false;
    public $contact_email = '';
    public $contact_phone = '';

    protected $rules = [
        // Step 1 validation
        'company_name' => 'required_if:is_anonymous,false|max:255',
        'respondent_position' => 'required|in:owner,director,manager,staff,other',
        'services_used' => 'required|array|min:1',
        
        // Step 2 validation
        'overall_satisfaction' => 'required|integer|min:1|max:5',
        'service_fit_needs' => 'required|integer|min:1|max:5',
        
        // Step 3 validation
        'timely_delivery' => 'required|integer|min:1|max:5',
        'shipment_info_clarity' => 'required|integer|min:1|max:5',
        'document_accuracy' => 'required|integer|min:1|max:5',
        'problem_handling' => 'required|integer|min:1|max:5',
        'coordination_quality' => 'required|integer|min:1|max:5',
        
        // Step 4 validation
        'responsiveness' => 'required|integer|min:1|max:5',
        'explanation_clarity' => 'required|integer|min:1|max:5',
        'staff_professionalism' => 'required|integer|min:1|max:5',
        'contact_ease' => 'required|integer|min:1|max:5',
        'price_fairness' => 'required|integer|min:1|max:5',
        'cost_transparency' => 'required|integer|min:1|max:5',
        'invoice_accuracy' => 'required|integer|min:1|max:5',
        
        // Step 5 validation (portal - conditional)
        'portal_ease_of_use' => 'required_if:portal_used,true|nullable|integer|min:1|max:5',
        'portal_info_clarity' => 'required_if:portal_used,true|nullable|integer|min:1|max:5',
        'portal_usefulness' => 'required_if:portal_used,true|nullable|integer|min:1|max:5',
        
        // Step 6 validation
        'likelihood_reuse' => 'required|integer|min:1|max:5',
        'nps_score' => 'required|integer|min:0|max:10',
        'contact_email' => 'required_if:willing_to_contact,true|nullable|email',
        'contact_phone' => 'nullable|max:50',
    ];

    public function mount()
    {
        $this->startTime = now();
    }

    public function updatedIsAnonymous($value)
    {
        if ($value) {
            $this->company_name = '';
        }
    }

    public function nextStep()
    {
        // Validate current step before moving
        $this->validateCurrentStep();
        
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep($step)
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            $this->currentStep = $step;
        }
    }

    protected function validateCurrentStep()
    {
        $rules = [];
        
        switch ($this->currentStep) {
            case 1:
                $rules = [
                    'company_name' => 'required_if:is_anonymous,false|max:255',
                    'respondent_position' => 'required|in:owner,director,manager,staff,other',
                    'services_used' => 'required|array|min:1',
                ];
                break;
            
            case 2:
                $rules = [
                    'overall_satisfaction' => 'required|integer|min:1|max:5',
                    'service_fit_needs' => 'required|integer|min:1|max:5',
                ];
                break;
            
            case 3:
                $rules = [
                    'timely_delivery' => 'required|integer|min:1|max:5',
                    'shipment_info_clarity' => 'required|integer|min:1|max:5',
                    'document_accuracy' => 'required|integer|min:1|max:5',
                    'problem_handling' => 'required|integer|min:1|max:5',
                    'coordination_quality' => 'required|integer|min:1|max:5',
                ];
                break;
            
            case 4:
                $rules = [
                    'responsiveness' => 'required|integer|min:1|max:5',
                    'explanation_clarity' => 'required|integer|min:1|max:5',
                    'staff_professionalism' => 'required|integer|min:1|max:5',
                    'contact_ease' => 'required|integer|min:1|max:5',
                    'price_fairness' => 'required|integer|min:1|max:5',
                    'cost_transparency' => 'required|integer|min:1|max:5',
                    'invoice_accuracy' => 'required|integer|min:1|max:5',
                ];
                break;
            
            case 5:
                if ($this->portal_used) {
                    $rules = [
                        'portal_ease_of_use' => 'required|integer|min:1|max:5',
                        'portal_info_clarity' => 'required|integer|min:1|max:5',
                        'portal_usefulness' => 'required|integer|min:1|max:5',
                    ];
                }
                break;
            
            case 6:
                $rules = [
                    'likelihood_reuse' => 'required|integer|min:1|max:5',
                    'nps_score' => 'required|integer|min:0|max:10',
                ];
                break;
        }
        
        if (!empty($rules)) {
            $this->validate($rules);
        }
    }

    public function submitSurvey()
    {
        // Final validation
        $this->validate();
        
        $completionTime = now()->diffInSeconds($this->startTime);
        
        // Try to match with existing customer
        $customerId = null;
        if (!$this->is_anonymous && $this->company_name) {
            $customer = Customer::where('company_name', 'like', '%' . $this->company_name . '%')->first();
            $customerId = $customer?->id;
        }
        
        // Save survey
        CustomerSurvey::create([
            'survey_year' => date('Y'),
            'response_date' => now(),
            
            // Responder info
            'is_anonymous' => $this->is_anonymous,
            'company_name' => $this->is_anonymous ? null : $this->company_name,
            'respondent_position' => $this->respondent_position,
            'respondent_position_other' => $this->respondent_position === 'other' ? $this->respondent_position_other : null,
            'customer_id' => $customerId,
            'services_used' => $this->services_used,
            
            // Satisfaction
            'overall_satisfaction' => $this->overall_satisfaction,
            'service_fit_needs' => $this->service_fit_needs,
            
            // Operational
            'timely_delivery' => $this->timely_delivery,
            'shipment_info_clarity' => $this->shipment_info_clarity,
            'document_accuracy' => $this->document_accuracy,
            'problem_handling' => $this->problem_handling,
            'coordination_quality' => $this->coordination_quality,
            
            // Communication
            'responsiveness' => $this->responsiveness,
            'explanation_clarity' => $this->explanation_clarity,
            'staff_professionalism' => $this->staff_professionalism,
            'contact_ease' => $this->contact_ease,
            
            // Pricing
            'price_fairness' => $this->price_fairness,
            'cost_transparency' => $this->cost_transparency,
            'invoice_accuracy' => $this->invoice_accuracy,
            
            // Portal
            'portal_used' => $this->portal_used,
            'portal_ease_of_use' => $this->portal_used ? $this->portal_ease_of_use : null,
            'portal_info_clarity' => $this->portal_used ? $this->portal_info_clarity : null,
            'portal_usefulness' => $this->portal_used ? $this->portal_usefulness : null,
            
            // Loyalty
            'likelihood_reuse' => $this->likelihood_reuse,
            'nps_score' => $this->nps_score,
            
            // Feedback
            'appreciate_most' => $this->appreciate_most,
            'needs_improvement' => $this->needs_improvement,
            'future_features' => $this->future_features,
            
            // Follow-up
            'willing_to_contact' => $this->willing_to_contact,
            'contact_email' => $this->willing_to_contact ? $this->contact_email : null,
            'contact_phone' => $this->contact_phone,
            
            // Metadata
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
            'completion_time' => $completionTime,
            'is_complete' => true,
        ]);
        
        // Redirect to thank you page
        return redirect()->route('survey.thank-you');
    }

    public function render()
    {
        return view('livewire.public-survey-form');
    }
}
