<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Shipment;
use App\Models\Document;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail; 
use Carbon\Carbon; // Import Carbon

class ShipmentDetail extends Component
{
    use WithFileUploads;

    public $shipment;
    
    // State Edit
    public $isModalOpen = false;
    public $isEditing = false;
    public $form = [];
    public $mark_as_completed = false; 

    // State Upload
    public $file_upload;
    public $doc_type = '';
    
    // Modal Preview Properties
    public $showDocPreview = false;
    public $previewDoc = null;
    public $allPublicDocs;
    public $currentDocIndex = 0;
    public $custom_note = '';
    public $custom_description = '';
    public $showInternalModal = false;

    public function mount($id)
    {
        // Pastikan relasi customer dan user terload
        $this->shipment = Shipment::with('customer.user')->findOrFail($id);
    }

    public function edit()
    {
        $this->form = $this->shipment->only([
            'customer_id', 'awb_number', 'origin', 'destination', 
            'service_type', 'shipment_type', 'container_mode', 'container_info',
            'pieces', 'package_type', 'weight', 'volume', 'status', 'lane_status', 'estimated_arrival', 'notes'
        ]);
        
        if($this->form['estimated_arrival']) {
            $this->form['estimated_arrival'] = date('Y-m-d', strtotime($this->form['estimated_arrival']));
        }

        // Set checkbox berdasarkan status saat ini
        $this->mark_as_completed = ($this->form['status'] === 'completed');

        $this->isEditing = true;
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate([
            'form.origin' => 'required',
            'form.destination' => 'required',
            'form.lane_status' => 'nullable|string',
            'form.notes' => 'nullable|string',
            'form.volume' => 'nullable|numeric|min:0',
        ]);

        // --- LOGIKA OTOMATIS STATUS ---
        if ($this->mark_as_completed) {
            $this->form['status'] = 'completed';
        } else {
            if (!empty($this->form['lane_status']) || $this->shipment->documents->count() > 0) {
                $this->form['status'] = 'in_progress';
            } else {
                $this->form['status'] = 'pending';
            }
        }

        $oldStatus = $this->shipment->status;
        $this->shipment->update($this->form);

        if ($oldStatus !== $this->form['status']) {
            ActivityLog::record('Shipment', 'UPDATE STATUS', $this->shipment->awb_number, "Status otomatis berubah ke '{$this->form['status']}'");
            
            // --- PANGGIL NOTIFIKASI JIKA STATUS SHIPMENT BERUBAH ---
            $this->sendUpdateNotification($this->form['status'], "STATUS SHIPMENT BERUBAH");
            
        } else {
            ActivityLog::record('Shipment', 'UPDATE INFO', $this->shipment->awb_number, "Mengubah detail shipment.");
        }

        $this->closeModal();
        session()->flash('message', 'Data shipment berhasil diperbarui.');
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->form = [];
    }

    // --- UPLOAD HANDLER ---
    public function uploadPublic() { $this->processUpload(false); }
    public function openInternalModal() { 
        $this->reset(['file_upload', 'doc_type', 'custom_note', 'custom_description']); 
        $this->doc_type = 'Foto Dokumentasi';
        $this->showInternalModal = true; 
    }
    public function closeInternalModal() { $this->showInternalModal = false; }
    public function uploadInternal() { $this->processUpload(true); $this->closeInternalModal(); }

    protected function processUpload($isInternal)
    {
        $this->validate([
            'file_upload' => 'required|file|mimes:pdf,jpg,jpeg,png,xls,xlsx,doc,docx,webp|max:10240',
            'doc_type' => 'required|string',
            'custom_description' => 'required_if:doc_type,Dokumen Pendukung Lainnya|string|max:255',
        ]);

        $ext = $this->file_upload->getClientOriginalExtension();
        $cleanRef = str_replace(['/', '\\'], '-', $this->shipment->awb_number);
        $prefix = $isInternal ? 'INTERNAL' : strtoupper(str_replace(' ', '_', $this->doc_type));
        $filename = $prefix . '_' . $cleanRef . '_' . time() . '.' . $ext;
        
        $path = $this->file_upload->storeAs('documents/' . ($isInternal ? 'internal' : 'public'), $filename, 'public');

        Document::create([
            'shipment_id' => $this->shipment->id,
            'document_type' => $isInternal ? 'internal_evidence' : 'admin_upload',
            'filename' => $filename,
            'file_path' => $path,
            'description' => ($this->doc_type === 'Dokumen Pendukung Lainnya' ? $this->custom_description : $this->doc_type) . ($this->custom_note ? ' - ' . $this->custom_note : ''),
            'is_internal' => $isInternal,
            'uploaded_by' => Auth::id(),
            'file_size' => $this->file_upload->getSize(),
            'mime_type' => $this->file_upload->getMimeType(),
            'uploaded_at' => now(),
        ]);

        // --- AUTOMATION TRIGGER: HANYA DOKUMEN UTAMA ---
        if (strtolower($this->shipment->service_type) == 'import') {
            // EXACT MATCH - Hanya dokumen utama yang trigger status
            if ($this->doc_type === 'Billing Pungutan') {
                $this->shipment->update(['lane_status' => 'green', 'status' => 'customs_released']);
                ActivityLog::record('Shipment', 'AUTO STATUS', $this->shipment->awb_number, "Auto: Green Lane - Billing Pungutan uploaded");
            }
            elseif ($this->doc_type === 'SPJM') {
                $this->shipment->update(['lane_status' => 'red', 'status' => 'customs_released']);
                ActivityLog::record('Shipment', 'AUTO STATUS', $this->shipment->awb_number, "Auto: Red Lane - SPJM uploaded");
            }
            elseif ($this->doc_type === 'SPPB') {
                $this->shipment->update(['status' => 'on_board']);
                ActivityLog::record('Shipment', 'AUTO STATUS', $this->shipment->awb_number, "Auto: On Board - SPPB uploaded");
            }
        }
        elseif (strtolower($this->shipment->service_type) == 'export') {
            if ($this->doc_type === 'NPE') {
                $this->shipment->update(['status' => 'on_board']);
                ActivityLog::record('Shipment', 'AUTO STATUS', $this->shipment->awb_number, "Auto: On Board - NPE uploaded");
            }
        }
        
        if ($this->shipment->status == 'pending') {
            $this->shipment->update(['status' => 'in_progress']);
        }

        // --- TRIGGER NOTIFIKASI EMAIL UNTUK DOKUMEN PUBLIK ---
        if (!$isInternal) {
            // Mengirim notifikasi tentang dokumen baru
            $this->sendUpdateNotification($this->doc_type, "DOKUMEN BARU DIUNGGAH");
        }

        $this->reset(['file_upload', 'doc_type', 'custom_note', 'custom_description']);
        $this->shipment->refresh();
        session()->flash('message', $isInternal ? 'Bukti internal disimpan.' : 'Dokumen publik diunggah & Status diperbarui.');
    }
    
    // --- FUNGSI KIRIM NOTIFIKASI (BARU) ---
    public function sendUpdateNotification($newStatus, $statusType)
    {
        // Mencoba mendapatkan email customer
        $customerEmail = $this->shipment->customer->user->email ?? $this->shipment->customer->email ?? null;
        
        if ($customerEmail && $this->shipment->customer) {
            
            // Data dinamis untuk template email
            $data = [
                'shipment' => $this->shipment, // Pass seluruh objek shipment
                'customerName' => $this->shipment->customer->company_name ?? 'Pelanggan Yth.',
                'awb' => $this->shipment->awb_number,
                'origin' => $this->shipment->origin,
                'destination' => $this->shipment->destination,
                'serviceType' => $this->shipment->service_type,
                'shipmentType' => $this->shipment->shipment_type,
                'newStatus' => $newStatus, // Status atau Nama Dokumen
                'statusType' => $statusType, // Label di atas status box
                'updateTime' => Carbon::now()->format('d M Y, H:i'),
                'trackingLink' => route('customer.shipment.show', $this->shipment->id), // Asumsi route customer ada
            ];

            try {
                // Menggunakan template baru yang sudah disesuaikan
                Mail::send('emails.shipment-document-update', $data, function ($message) use ($customerEmail, $data) {
                    $message->to($customerEmail)
                            ->subject("Pembaruan Status Pengiriman: {$data['newStatus']} - {$data['awb']}");
                });
            } catch (\Exception $e) {
                // Log error jika email gagal, tapi jangan hentikan proses
                // \Log::error("Gagal kirim notif tracking ke $customerEmail: " . $e->getMessage());
            }
        }
    }

    public function deleteDocument($id)
    {
        $doc = Document::findOrFail($id);
        if (Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }
        $doc->delete();
        $this->shipment->refresh();
        session()->flash('message', 'Dokumen dihapus.');
    }


    public function viewDocument($docId)
    {
        $this->previewDoc = Document::find($docId);
        $this->allPublicDocs = $this->shipment->documents()->where('is_internal', false)->get();
        $this->currentDocIndex = $this->allPublicDocs->search(function($doc) use ($docId) {
            return $doc->id == $docId;
        });
        $this->showDocPreview = true;
    }

    public function nextDocument()
    {
        if ($this->currentDocIndex < $this->allPublicDocs->count() - 1) {
            $this->currentDocIndex++;
            $this->previewDoc = $this->allPublicDocs[$this->currentDocIndex];
        }
    }

    public function previousDocument()
    {
        if ($this->currentDocIndex > 0) {
            $this->currentDocIndex--;
            $this->previewDoc = $this->allPublicDocs[$this->currentDocIndex];
        }
    }

    public function render()
    {
        return view('livewire.admin.shipment-detail')->layout('layouts.admin');
    }

    // ===================================
    // FIELD DOCUMENTATION METHODS
    // ===================================
    
    public $showPhotoUpload = false;

    public function togglePhotoUpload()
    {
        $this->showPhotoUpload = !$this->showPhotoUpload;
    }

    public function getFieldPhotosProperty()
    {
        return $this->shipment->fieldPhotos()
            ->latest()
            ->with('user')
            ->get();
    }
}
