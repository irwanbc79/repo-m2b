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
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

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

    // Modal Rename Document Properties
    public $showRenameModal = false;
    public $editingDocId = null;
    public $editingDocDescription = '';
    public $editingDocFilename = '';
    public $editingDocExtension = '';

    // Checklist Dokumen (F1)
    public $showDocReqModal = false;
    public $req_doc_type = '';
    public $req_note = '';
    public $req_due = '';

    // AI Lartas (F4)
    public $showLartasPanel = false;

    public function mount($id)
    {
        // Pastikan relasi customer dan user terload
        $this->shipment = Shipment::with('customer.user')->findOrFail($id);

        // Bangun checklist dokumen sekali (aditif; aman bila tabel belum ada).
        try {
            if (\App\Models\DocumentRequirement::where('shipment_id', $this->shipment->id)->doesntExist()) {
                app(\App\Services\DocumentChecklistService::class)->buildForShipment($this->shipment);
            }
        } catch (\Throwable $e) {
            \Log::warning('buildForShipment gagal: ' . $e->getMessage());
        }
    }

    // ===== Checklist Dokumen =====
    public function getChecklistProperty()
    {
        return \App\Models\DocumentRequirement::where('shipment_id', $this->shipment->id)
            ->orderByDesc('is_mandatory')->orderBy('doc_type')->get();
    }

    public function getReadinessProperty()
    {
        try {
            return app(\App\Services\DocumentChecklistService::class)->readinessScore($this->shipment);
        } catch (\Throwable $e) {
            return ['total' => 0, 'fulfilled' => 0, 'pending' => 0, 'percent' => 100];
        }
    }

    public function getDocTypeOptionsProperty()
    {
        return \App\Models\DocumentType::active()->shipmentLevel()
            ->forService($this->shipment->service_type ?? 'import')
            ->orderBy('category')->orderBy('sort_order')
            ->pluck('doc_type')->unique()->values();
    }

    public function openDocReqModal()
    {
        $this->reset(['req_doc_type', 'req_note', 'req_due']);
        $this->showDocReqModal = true;
    }

    public function mintaDokumen()
    {
        $this->validate([
            'req_doc_type' => 'required|string',
            'req_note'     => 'nullable|string|max:500',
            'req_due'      => 'nullable|date',
        ]);

        app(\App\Services\DocumentChecklistService::class)->requestFromCustomer(
            $this->shipment, $this->req_doc_type, $this->req_note ?: null, $this->req_due ?: null, Auth::id()
        );
        ActivityLog::record('Shipment', 'MINTA DOKUMEN', $this->shipment->awb_number, "Minta '{$this->req_doc_type}' ke customer");
        $this->showDocReqModal = false;
        session()->flash('message', "Permintaan dokumen '{$this->req_doc_type}' dikirim ke customer.");
    }

    public function tetapkanWajib($reqId)
    {
        $req = \App\Models\DocumentRequirement::where('shipment_id', $this->shipment->id)->find($reqId);
        if ($req) {
            app(\App\Services\DocumentChecklistService::class)->confirmMandatory($req, Auth::id());
            session()->flash('message', "'{$req->doc_type}' ditetapkan sebagai wajib.");
        }
    }

    public function waiveRequirement($reqId)
    {
        $req = \App\Models\DocumentRequirement::where('shipment_id', $this->shipment->id)->find($reqId);
        if ($req && $req->status !== 'fulfilled') {
            $req->update(['status' => 'waived', 'is_mandatory' => false]);
        }
    }

    public function hapusRequirement($reqId)
    {
        \App\Models\DocumentRequirement::where('shipment_id', $this->shipment->id)
            ->where('id', $reqId)->where('status', '!=', 'fulfilled')->delete();
    }

    /** Tandai "Sudah Ada" secara manual (verifikasi staf). */
    public function tandaiLengkap($reqId)
    {
        $req = \App\Models\DocumentRequirement::where('shipment_id', $this->shipment->id)->find($reqId);
        if ($req) {
            app(\App\Services\DocumentChecklistService::class)->markFulfilledManual($req, Auth::id());
            session()->flash('message', "'{$req->doc_type}' ditandai sudah ada (verifikasi manual).");
        }
    }

    /** Batalkan status lengkap → kembali belum. */
    public function batalkanLengkap($reqId)
    {
        $req = \App\Models\DocumentRequirement::where('shipment_id', $this->shipment->id)->find($reqId);
        if ($req && $req->status === 'fulfilled') {
            app(\App\Services\DocumentChecklistService::class)->revertFulfillment($req);
        }
    }

    /** Cocokkan ulang seluruh dokumen shipment ke checklist. */
    public function cocokkanUlang()
    {
        $this->shipment->load('documents');
        $n = app(\App\Services\DocumentChecklistService::class)->rescanShipment($this->shipment);
        session()->flash('message', "Cocokkan ulang selesai — {$n} dokumen tercocokkan ke checklist.");
    }

    // ===== AI Lartas (F4) =====
    public function getLartasConfiguredProperty(): bool
    {
        return app(\App\Services\LartasAiService::class)->isConfigured();
    }

    /** Hasil analisa tersimpan (bila ada). */
    public function getLartasProperty()
    {
        try {
            return \App\Models\LartasAnalysis::where('shipment_id', $this->shipment->id)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Jalankan analisa AI lartas (staf yang memicu; hasil disimpan). */
    public function analisaLartas()
    {
        try {
            $analysis = app(\App\Services\LartasAiService::class)->analyze($this->shipment, Auth::id());
            $this->showLartasPanel = true;
            ActivityLog::record('Shipment', 'ANALISA LARTAS (AI)', $this->shipment->awb_number, "HS {$this->shipment->hs_code}");
            $n = count($analysis->recommendations ?? []);
            session()->flash('message', "Analisa lartas AI selesai — {$n} rekomendasi. Ingat: rekomendasi awal, keputusan tetap di tim.");
        } catch (\Throwable $e) {
            session()->flash('lartas_error', $e->getMessage());
        }
    }

    /** Rekomendasi AI → minta ke customer (jadi WAJIB + notifikasi kuat). */
    public function mintaLartasKeCustomer($docType)
    {
        $note = 'Perkiraan izin/lartas dari analisa AI — mohon konfirmasi & lengkapi bila berlaku.';
        app(\App\Services\DocumentChecklistService::class)->requestFromCustomer(
            $this->shipment, $docType, $note, null, Auth::id()
        );
        ActivityLog::record('Shipment', 'MINTA DOKUMEN', $this->shipment->awb_number, "Minta '{$docType}' (rekomendasi AI) ke customer");
        session()->flash('message', "Permintaan '{$docType}' dikirim ke customer.");
    }

    /** Rekomendasi AI → tambah ke checklist (belum tentu wajib; keputusan staf). */
    public function tambahLartasKeChecklist($docType)
    {
        app(\App\Services\DocumentChecklistService::class)->addRequirement(
            $this->shipment, $docType, ['source' => 'ai-lartas', 'responsibility' => 'customer']
        );
        session()->flash('message', "'{$docType}' ditambahkan ke checklist.");
    }

    public function edit()
    {
        $this->form = $this->shipment->only([
            'customer_id', 'awb_number', 'origin', 'destination', 
            'service_type', 'shipment_type', 'container_mode', 'container_info',
            'pieces', 'package_type', 'weight', 'volume', 'hs_code', 'status', 'lane_status', 'estimated_arrival', 'notes'
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

        $document = Document::create([
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

        // Auto-fulfill checklist dokumen (ADITIF; dibungkus try/catch agar tak
        // pernah mengganggu flow upload lama). Role admin krn ini sisi admin.
        try {
            app(\App\Services\DocumentChecklistService::class)->autoFulfillOnUpload($document, 'admin');
        } catch (\Throwable $e) {
            \Log::warning('autoFulfillOnUpload gagal utk document #' . $document->id . ': ' . $e->getMessage());
        }

        // --- AUTO STATUS dari dokumen (pakai mapping RESMI getDocumentTriggers,
        //     tidak lagi hardcode; hanya MAJU, tak pernah mundur/melompat) ---
        $triggers = Shipment::getDocumentTriggers($this->shipment->service_type);
        if (isset($triggers[$this->doc_type])
            && !in_array($this->shipment->status, ['completed', 'cancel', 'cancelled'], true)) {

            $target  = $triggers[$this->doc_type];
            $updates = [];

            // Hanya set status bila milestone dokumen ini >= status sekarang
            // (mis. Billing Pungutan → billing_issued, BUKAN customs_released).
            if ($this->shipment->statusOrder($target) >= $this->shipment->statusOrder()) {
                $updates['status'] = $target;
            }

            // Deteksi jalur (khusus import): Billing Pungutan = hijau, SPJM = merah.
            if (strtolower($this->shipment->service_type) === 'import') {
                if ($this->doc_type === 'Billing Pungutan')      $updates['lane_status'] = 'green';
                elseif ($this->doc_type === 'SPJM')              $updates['lane_status'] = 'red';
            }

            if (!empty($updates)) {
                $this->shipment->update($updates);
                ActivityLog::record('Shipment', 'AUTO STATUS', $this->shipment->awb_number,
                    "Auto dari upload {$this->doc_type} → " . ($updates['status'] ?? $this->shipment->status));
            }
        }

        if ($this->shipment->status == 'pending') {
            $this->shipment->update(['status' => 'in_progress']);
        }

        // --- TRIGGER NOTIFIKASI EMAIL UNTUK DOKUMEN PUBLIK ---
        // Cooldown 30 menit: hanya kirim 1 email per shipment per sesi upload
        if (!$isInternal) {
            $cacheKey = 'doc_notif_' . $this->shipment->id;
            if (!Cache::has($cacheKey)) {
                Cache::put($cacheKey, true, now()->addMinutes(30));
                $this->sendUpdateNotification($this->doc_type, "DOKUMEN BARU DIUNGGAH");
            }
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

                // Log ke sent_emails
                \App\Models\SentEmail::create([
                    'mailbox' => 'no_reply',
                    'to_email' => $customerEmail,
                    'subject' => "Pembaruan Status Pengiriman: {$data['newStatus']} - {$data['awb']}",
                    'body' => "Status dokumen diupdate ke {$data['newStatus']}",
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name,
                ]);
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

    public function openRenameModal($id)
    {
        $doc = Document::findOrFail($id);
        $this->editingDocId = $doc->id;
        $this->editingDocDescription = $doc->description ?? '';

        $pathInfo = pathinfo($doc->filename);
        $this->editingDocFilename = $pathInfo['filename'] ?? '';
        $this->editingDocExtension = isset($pathInfo['extension']) && !empty($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

        $this->showRenameModal = true;
    }

    public function closeRenameModal()
    {
        $this->showRenameModal = false;
        $this->editingDocId = null;
        $this->editingDocDescription = '';
        $this->editingDocFilename = '';
        $this->editingDocExtension = '';
    }

    public function selectDocPreset($preset)
    {
        if (empty($preset)) return;

        $this->editingDocDescription = $preset;

        // Auto format filename suggestion if current filename is default/email attachment
        $currentLower = strtolower($this->editingDocFilename);
        if (empty($this->editingDocFilename) || str_contains($currentLower, 'email_attachment') || str_contains($currentLower, 'attachment') || str_contains($currentLower, 'internal')) {
            $cleanRef = str_replace(['/', '\\', ' '], '-', $this->shipment->awb_number ?? '');
            $slug = strtoupper(str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $preset)));
            $this->editingDocFilename = $slug . '_' . $cleanRef;
        }
    }

    public function updateDocumentName()
    {
        $this->validate([
            'editingDocDescription' => 'required|string|max:255',
            'editingDocFilename' => 'required|string|max:255',
        ]);

        if (!$this->editingDocId) {
            return;
        }

        $doc = Document::findOrFail($this->editingDocId);
        $oldDesc = $doc->description;
        $ext = ltrim($this->editingDocExtension, '.');
        $rawFilename = trim($this->editingDocFilename);

        if (!empty($ext)) {
            $cleanFilenameBase = preg_replace('/\.' . preg_quote($ext, '/') . '$/i', '', $rawFilename);
            $newFilename = $cleanFilenameBase . $this->editingDocExtension;
        } else {
            $newFilename = $rawFilename;
        }

        $doc->update([
            'description' => trim($this->editingDocDescription),
            'filename' => $newFilename,
        ]);

        ActivityLog::record(
            'Shipment',
            'RENAME DOCUMENT',
            $this->shipment->awb_number,
            "Mengubah nama dokumen ID {$doc->id} dari '{$oldDesc}' menjadi '{$this->editingDocDescription}' (file: {$newFilename})"
        );

        if ($this->previewDoc && $this->previewDoc->id == $doc->id) {
            $this->previewDoc = $doc->fresh();
        }

        $this->closeRenameModal();
        $this->shipment->refresh();
        session()->flash('message', 'Nama dokumen berhasil diperbarui.');
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

    public function viewInternalDoc($docId)
    {
        $this->previewDoc = Document::find($docId);
        $this->allPublicDocs = collect();
        $this->currentDocIndex = 0;
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
