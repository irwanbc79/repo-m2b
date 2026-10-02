<?php

namespace App\Livewire\Admin;

use App\Models\Shipment;
use App\Models\ShipmentComplianceAudit;
use App\Services\ComplianceAuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ShipmentCompliancePanel extends Component
{
    public $shipmentId;

    // State Resolusi Temuan Manual
    public $showResolveModal = false;
    public $selectedFindingIndex = null;
    public $resolutionNote = '';

    // State WhatsApp & Email
    public $showWaModal = false;
    public $waText = '';
    public $waNumber = '';

    public $showEmailModal = false;
    public $emailRecipient = '';
    public $emailSubject = '';
    public $emailBody = '';

    public function mount($shipmentId)
    {
        $this->shipmentId = $shipmentId;
    }

    public function getShipmentProperty()
    {
        return Shipment::with(['customer', 'documents'])->findOrFail($this->shipmentId);
    }

    public function getAuditProperty()
    {
        return ShipmentComplianceAudit::where('shipment_id', $this->shipmentId)
            ->latest()
            ->first();
    }

    public function getIsConfiguredProperty(): bool
    {
        return app(ComplianceAuditService::class)->isConfigured();
    }

    /**
     * Jalankan pra-audit dokumen pengapalan.
     */
    public function runAudit()
    {
        try {
            $service = app(ComplianceAuditService::class);
            $service->auditShipment($this->shipment, Auth::id());

            session()->flash('compliance_message', 'Pra-audit dokumen pengapalan berhasil dijalankan.');
        } catch (\Throwable $e) {
            Log::error('Compliance Audit failed: ' . $e->getMessage());
            session()->flash('compliance_error', $e->getMessage());
        }
    }

    /**
     * Buka modal untuk menyelesaikan temuan secara manual oleh staf.
     */
    public function openResolveModal($index)
    {
        $audit = $this->audit;
        if (! $audit || ! isset($audit->findings[$index])) {
            return;
        }

        $this->selectedFindingIndex = $index;
        $this->resolutionNote = $audit->findings[$index]['resolution_note'] ?? '';
        $this->showResolveModal = true;
    }

    /**
     * Simpan keterangan penyelesaian temuan manual (tanpa panggil ulang AI).
     */
    public function saveResolution()
    {
        $this->validate([
            'resolutionNote' => 'required|min:5|max:1000',
        ], [
            'resolutionNote.required' => 'Catatan penyelesaian/klarifikasi wajib diisi.',
            'resolutionNote.min' => 'Catatan minimal 5 karakter.',
        ]);

        $audit = $this->audit;
        if ($audit && $this->selectedFindingIndex !== null) {
            $audit->resolveFinding($this->selectedFindingIndex, $this->resolutionNote, Auth::user());
            $this->showResolveModal = false;
            $this->selectedFindingIndex = null;
            $this->resolutionNote = '';

            session()->flash('compliance_message', 'Temuan berhasil ditandai selesai dengan catatan klarifikasi Tim M2B.');
        }
    }

    /**
     * Siapkan teks WhatsApp resmi untuk dikirim ke klien.
     */
    public function prepareWhatsApp()
    {
        $audit = $this->audit;
        if (! $audit) {
            return;
        }

        $customer = $this->shipment->customer;
        $this->waNumber = $customer?->phone ?: ($customer?->mobile ?: '');
        $this->waText = $audit->buildWhatsAppText();
        $this->showWaModal = true;
    }

    /**
     * Siapkan draf email resmi.
     */
    public function prepareEmail()
    {
        $audit = $this->audit;
        if (! $audit) {
            return;
        }

        $customer = $this->shipment->customer;
        $bl = $this->shipment->awb_number ?: ($this->shipment->bl_number ?: '-');

        $this->emailRecipient = $customer?->email ?: '';
        $this->emailSubject = "Update Pra-Verifikasi Dokumen Impor — B/L {$bl} [M2B Logistics]";
        $this->emailBody = str_replace(['*', '_'], '', $audit->buildWhatsAppText());
        $this->showEmailModal = true;
    }

    public function render()
    {
        return view('livewire.admin.shipment-compliance-panel', [
            'shipment' => $this->shipment,
            'audit' => $this->audit,
            'isConfigured' => $this->isConfigured,
        ]);
    }
}
