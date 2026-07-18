<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Shipment;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ShipmentDetail extends Component
{
    use WithFileUploads;

    public $shipment;
    
    // Variabel Form Upload
    public $file_upload;
    public $doc_type = '';
    public $custom_note = '';

    // Modal Preview Properties (BARU - adopsi dari admin)
    public $showDocPreview = false;
    public $previewDoc = null;
    public $allPublicDocs;
    public $currentDocIndex = 0;

    // Pesan / diskusi shipment
    public $newMessage = '';

    public function mount($id)
    {
        // LOGIC KEAMANAN PINTAR:
        // 1. Ambil data shipment beserta dokumennya
        $query = Shipment::with(['customer','statuses','documents'])->where('id', $id);

        // 2. Jika yang akses adalah CUSTOMER, paksa filter hanya miliknya
        if (Auth::user()->role === 'customer') {
            $query->where('customer_id', Auth::user()->customer->id);
        }

        // 3. Jika ADMIN, loloskan saja (Bisa lihat semua shipment)

        $this->shipment = $query->firstOrFail();

        // Tandai pesan dari admin sebagai sudah dibaca saat customer membuka detail.
        if (Auth::user()->role === 'customer') {
            $this->shipment->messages()->unreadForCustomer()->update(['read_at' => now()]);
        }

        // Bangun checklist dokumen sekali (aditif; aman bila tabel belum ada).
        try {
            if (\App\Models\DocumentRequirement::where('shipment_id', $this->shipment->id)->doesntExist()) {
                app(\App\Services\DocumentChecklistService::class)->buildForShipment($this->shipment);
            }
        } catch (\Throwable $e) {
            \Log::warning('buildForShipment (customer) gagal: ' . $e->getMessage());
        }
    }

    // ===== Kelengkapan Dokumen (F2) =====
    public function getReadinessProperty()
    {
        try {
            return app(\App\Services\DocumentChecklistService::class)->readinessScore($this->shipment);
        } catch (\Throwable $e) {
            return ['total' => 0, 'fulfilled' => 0, 'pending' => 0, 'percent' => 100];
        }
    }

    /** Dokumen yang perlu DILENGKAPI CUSTOMER (tanggung jawab customer, belum lengkap). */
    public function getMyRequirementsProperty()
    {
        return \App\Models\DocumentRequirement::where('shipment_id', $this->shipment->id)
            ->where('responsibility', 'customer')
            ->where('status', '!=', 'waived')
            ->orderByRaw("CASE WHEN status='fulfilled' THEN 1 ELSE 0 END")
            ->orderByDesc('is_mandatory')
            ->orderBy('doc_type')
            ->get();
    }

    /** Pilih dokumen ini untuk di-upload (pra-isi form upload). */
    public function pilihUntukUpload($reqId)
    {
        $req = \App\Models\DocumentRequirement::where('shipment_id', $this->shipment->id)->find($reqId);
        if ($req) {
            $this->doc_type = $req->doc_type;
            $this->dispatch('scroll-to-upload');
        }
    }

    /** Hasil analisa lartas (read-only, edukasi) bila staf sudah menjalankannya. */
    public function getLartasProperty()
    {
        try {
            return \App\Models\LartasAnalysis::where('shipment_id', $this->shipment->id)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Referensi INSW otoritatif (bila staf sudah merekam) — didahulukan atas AI. */
    public function getLartasReferenceProperty()
    {
        try {
            $flow = strtolower($this->shipment->service_type ?: 'import');
            $flow = in_array($flow, ['import', 'export'], true) ? $flow : 'import';
            return \App\Models\LartasReference::lookup($this->shipment->hs_code, $flow);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Opsi jenis dokumen di form upload = yang diminta + umum. */
    public function getUploadOptionsProperty()
    {
        $needed = $this->myRequirements->where('status', '!=', 'fulfilled')->pluck('doc_type');
        $common = collect(['Invoice', 'Packing List', 'Bill of Lading', 'Bukti Bayar BM/PDRI']);
        return $needed->merge($common)->unique()->values();
    }

    /**
     * Customer mengirim pertanyaan/pesan tentang shipment ini.
     */
    public function sendMessage()
    {
        $this->validate(
            ['newMessage' => 'required|string|max:2000'],
            ['newMessage.required' => 'Pesan tidak boleh kosong.']
        );

        $this->shipment->messages()->create([
            'customer_id' => $this->shipment->customer_id,
            'sender_type' => 'customer',
            'sender_id'   => Auth::id(),
            'body'        => trim($this->newMessage),
        ]);

        $this->newMessage = '';
        $this->shipment->load('messages.sender');
        session()->flash('msg_sent', 'Pesan terkirim. Tim M2B akan membalas secepatnya.');
    }

    // === METHOD PREVIEW DOCUMENT (BARU - adopsi dari admin) ===
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

    public function closeDocPreview()
    {
        $this->showDocPreview = false;
        $this->previewDoc = null;
    }
    // === END METHOD PREVIEW ===

    public function uploadDoc()
    {
        $this->validate([
            'file_upload' => 'required|file|mimes:pdf,jpg,jpeg,png,xls,xlsx,doc,docx|max:5120',
            'doc_type' => 'required|string']);

        $ext = $this->file_upload->getClientOriginalExtension();
        $cleanRef = str_replace(['/', '\\'], '-', $this->shipment->awb_number);
        $filename = strtoupper(str_replace(' ', '_', $this->doc_type)) . '_' . $cleanRef . '_' . time() . '.' . $ext;
        
        $path = $this->file_upload->storeAs('documents/customer_uploads', $filename, 'public');

        $document = Document::create([
            'shipment_id' => $this->shipment->id,
            'document_type' => 'customer_upload',
            'filename' => $filename,
            'file_path' => $path,
            'description' => $this->doc_type . ($this->custom_note ? ' - ' . $this->custom_note : ''),
            'is_internal' => false,
            'uploaded_by' => Auth::id(),
            'file_size' => $this->file_upload->getSize(),
            'mime_type' => $this->file_upload->getMimeType(),
            'uploaded_at' => now()]);

        // Auto-fulfill checklist (role customer) — ADITIF, aman via try/catch.
        try {
            app(\App\Services\DocumentChecklistService::class)->autoFulfillOnUpload($document, 'customer');
        } catch (\Throwable $e) {
            \Log::warning('autoFulfillOnUpload (customer) gagal: ' . $e->getMessage());
        }

        $this->reset(['file_upload', 'doc_type', 'custom_note']);
        $this->shipment->refresh(); 
        
        session()->flash('message', 'Dokumen berhasil diunggah.');
    }

    public function render()
    {
        // DETEKSI LAYOUT OTOMATIS
        // Jika Admin yang buka -> Pakai Layout Admin
        // Jika Customer yang buka -> Pakai Layout Customer
        $layout = Auth::user()->role === 'admin' || Auth::user()->role === 'manager' 
            ? 'layouts.admin' 
            : 'layouts.customer';

        return view('livewire.customer.shipment-detail')->layout($layout);
    }
}
