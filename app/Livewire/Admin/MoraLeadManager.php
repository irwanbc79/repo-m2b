<?php

namespace App\Livewire\Admin;

use App\Models\MoraLeadNotification;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class MoraLeadManager extends Component
{
    use WithPagination;

    public string $filter = 'all';
    public string $search = '';

    // Stats
    public int $totalNew;
    public int $totalHot;
    public int $totalUnread;
    public int $totalFollowUp;
    public float $potentialPipeline;

    // Active Lead Detail ID
    public $selectedLeadId = null;

    // Lead Update Fields
    public string $leadStatus = 'new';
    public $leadAssignedTo = null;
    public string $leadFollowUpAt = '';
    public string $leadDealValue = '';
    public string $newNoteText = '';

    // WhatsApp Templates
    public string $selectedTemplateKey = '';
    public string $whatsappUrl = '';

    // Manual Lead Form Fields
    public bool $showCreateModal = false;
    public string $newLeadName = '';
    public string $newLeadCompany = '';
    public string $newLeadPhone = '';
    public string $newLeadEmail = '';
    public string $newLeadScore = 'cold';
    public string $newLeadService = 'other';
    public string $newLeadSummary = '';
    public string $newLeadDealValue = '';

    protected $queryString = ['filter', 'search'];

    public function mount(): void
    {
        $this->refreshStats();
    }

    public function refreshStats(): void
    {
        $this->totalNew = MoraLeadNotification::where('status', 'new')->count();
        $this->totalHot = MoraLeadNotification::where('score', 'hot')->count();
        $this->totalUnread = MoraLeadNotification::whereNull('read_at')->count();
        $this->totalFollowUp = MoraLeadNotification::whereNotNull('follow_up_at')
            ->whereNotIn('status', ['won', 'lost'])
            ->count();
        
        $this->potentialPipeline = MoraLeadNotification::whereNotIn('status', ['won', 'lost'])
            ->sum('deal_value') ?? 0;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function selectLead($id): void
    {
        $this->selectedLeadId = $id;
        $lead = MoraLeadNotification::find($id);

        if ($lead) {
            if ($lead->isUnread()) {
                $lead->markRead();
                $this->refreshStats();
            }

            $this->leadStatus = $lead->status ?? 'new';
            $this->leadAssignedTo = $lead->assigned_to;
            $this->leadFollowUpAt = $lead->follow_up_at ? $lead->follow_up_at->format('Y-m-d\TH:i') : '';
            $this->leadDealValue = $lead->deal_value ? (float)$lead->deal_value : '';
            $this->newNoteText = '';
            $this->selectedTemplateKey = '';
            $this->whatsappUrl = $lead->waUrl();
        }
    }

    public function closeDetail(): void
    {
        $this->selectedLeadId = null;
    }

    public function updatedSelectedTemplateKey($value): void
    {
        $lead = MoraLeadNotification::find($this->selectedLeadId);
        if (!$lead || !$value) {
            $this->whatsappUrl = $lead ? $lead->waUrl() : '';
            return;
        }

        $templates = MoraLeadNotification::getWaTemplates($lead->name, $lead->serviceLabel() ?? '');
        $message = $templates[$value] ?? '';
        
        $phone = preg_replace('/[^0-9]/', '', $lead->phone);
        $phone = preg_replace('/^0/', '62', $phone);
        
        $this->whatsappUrl = "https://wa.me/{$phone}?text=" . urlencode($message);
    }

    public function saveLeadUpdate(): void
    {
        if (!$this->selectedLeadId) return;

        $lead = MoraLeadNotification::find($this->selectedLeadId);
        if (!$lead) return;

        $data = [
            'status' => $this->leadStatus,
            'assigned_to' => $this->leadAssignedTo ?: null,
            'follow_up_at' => $this->leadFollowUpAt ?: null,
            'deal_value' => $this->leadDealValue ?: null,
        ];

        // Append note if entered
        if (trim($this->newNoteText) !== '') {
            $notes = $lead->sales_notes ?? [];
            $notes[] = [
                'date' => now()->toIso8601String(),
                'user' => auth()->user()?->name ?? 'Sales Rep',
                'text' => trim($this->newNoteText)
            ];
            $data['sales_notes'] = $notes;
            $this->newNoteText = '';
        }

        $lead->update($data);
        $this->refreshStats();

        // Refresh selected lead info
        $this->selectLead($this->selectedLeadId);

        session()->flash('message', 'Lead updated successfully.');
    }

    public function markRead(int $id): void
    {
        MoraLeadNotification::find($id)?->markRead();
        $this->refreshStats();
        if ($this->selectedLeadId === $id) {
            $this->selectLead($id);
        }
    }

    public function markAllRead(): void
    {
        MoraLeadNotification::whereNull('read_at')->update(['read_at' => now()]);
        $this->refreshStats();
        if ($this->selectedLeadId) {
            $this->selectLead($this->selectedLeadId);
        }
    }

    public function openCreateModal(): void
    {
        $this->resetManualFields();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    private function resetManualFields(): void
    {
        $this->newLeadName = '';
        $this->newLeadCompany = '';
        $this->newLeadPhone = '';
        $this->newLeadEmail = '';
        $this->newLeadScore = 'cold';
        $this->newLeadService = 'other';
        $this->newLeadSummary = '';
        $this->newLeadDealValue = '';
    }

    public function createManualLead(): void
    {
        $this->validate([
            'newLeadName' => 'required|string|max:100',
            'newLeadPhone' => 'required|string|max:20',
            'newLeadEmail' => 'nullable|email|max:100',
            'newLeadCompany' => 'nullable|string|max:100',
            'newLeadService' => 'required|string|max:30',
            'newLeadScore' => 'required|string|in:hot,warm,cold',
            'newLeadDealValue' => 'nullable|numeric|min:0',
        ]);

        $lead = MoraLeadNotification::create([
            'name' => $this->newLeadName,
            'phone' => $this->newLeadPhone,
            'email' => $this->newLeadEmail ?: null,
            'company' => $this->newLeadCompany ?: null,
            'source' => 'manual',
            'score' => $this->newLeadScore,
            'service_interest' => $this->newLeadService,
            'summary' => $this->newLeadSummary ?: null,
            'deal_value' => $this->newLeadDealValue ?: null,
            'status' => 'new',
            'read_at' => now(), // Manual entries are read by default
            'assigned_to' => auth()->id(),
            'sales_notes' => [
                [
                    'date' => now()->toIso8601String(),
                    'user' => auth()->user()?->name ?? 'System',
                    'text' => 'Lead created manually.'
                ]
            ]
        ]);

        $this->showCreateModal = false;
        $this->refreshStats();
        $this->selectLead($lead->id);

        session()->flash('message', 'Manual lead created successfully.');
    }

    public function exportCsv()
    {
        $query = MoraLeadNotification::query()
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('company', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->when($this->filter !== 'all', function ($q) {
                if (in_array($this->filter, ['unread'])) {
                    $q->whereNull('read_at');
                } elseif (in_array($this->filter, ['hot', 'warm', 'cold'])) {
                    $q->where('score', $this->filter);
                } else {
                    $q->where('status', $this->filter);
                }
            })
            ->latest();

        $leads = $query->get();
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=mora_leads_export_" . date('Ymd_His') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];
        
        $callback = function() use ($leads) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Nama', 'Perusahaan', 'Telepon', 'Email', 'Score', 'Sumber', 'Layanan', 'Deal Value (IDR)', 'Status', 'Follow Up', 'Tanggal Masuk']);
            
            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->id,
                    $lead->name,
                    $lead->company,
                    $lead->phone,
                    $lead->email,
                    strtoupper($lead->score),
                    $lead->source === 'mora_chat' ? 'MORA Chat' : ($lead->source === 'manual' ? 'Manual' : ($lead->source === 'cs_form_whatsapp' ? 'CS WhatsApp' : ($lead->source === 'cs_form_telegram' ? 'CS Telegram' : 'CS Form'))),
                    $lead->serviceLabel() ?? '-',
                    $lead->deal_value ? (float)$lead->deal_value : 0,
                    $lead->stageLabel(),
                    $lead->follow_up_at ? $lead->follow_up_at->format('Y-m-d H:i') : '-',
                    $lead->created_at->format('Y-m-d H:i')
                ]);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $query = MoraLeadNotification::query()
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('company', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->when($this->filter !== 'all', function ($q) {
                if (in_array($this->filter, ['unread'])) {
                    $q->whereNull('read_at');
                } elseif (in_array($this->filter, ['hot', 'warm', 'cold'])) {
                    $q->where('score', $this->filter);
                } else {
                    $q->where('status', $this->filter);
                }
            })
            ->latest();

        $leads = $query->paginate(15);
        $salesReps = User::all();
        $selectedLead = $this->selectedLeadId ? MoraLeadNotification::with('assignee')->find($this->selectedLeadId) : null;

        return view('livewire.admin.mora-lead-manager', [
            'leads' => $leads,
            'salesReps' => $salesReps,
            'selectedLead' => $selectedLead,
        ])->layout('layouts.admin', ['title' => 'MORA Leads — Sales CRM Dashboard']);
    }
}
