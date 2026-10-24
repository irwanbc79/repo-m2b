<?php

namespace App\Livewire\Customer;

use App\Models\Shipment;
use App\Services\DocumentService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class DocumentUpload extends Component
{
    use WithFileUploads;

    public Shipment $shipment;
    public $files = [];
    public $documentType = 'other';
    public $description = '';
    public $uploading = false;

    protected DocumentService $documentService;

    protected function rules(): array
    {
        return [
            'files' => 'required',
            'files.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            'documentType' => 'required|in:invoice,packing_list,certificate,other',
            'description' => 'nullable|string|max:500',
        ];
    }

    protected $messages = [
        'files.required' => 'Please select at least one file',
        'files.*.required' => 'Please select at least one file',
        'files.*.max' => 'File size must not exceed 10MB',
        'files.*.mimes' => 'Only PDF, images, Word, and Excel files are allowed',
    ];

    public function boot(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function mount($shipmentId)
    {
        $user = Auth::user();
        $customerId = $user?->getCustomerId();
        abort_unless($user && $user->canEditCustomerPortal() && $customerId, 403, 'Akun View-Only tidak diizinkan mengunggah dokumen.');

        $this->shipment = Shipment::where('customer_id', $customerId)->findOrFail($shipmentId);
    }

    public function updatedFiles()
    {
        if (is_array($this->files)) {
            $this->validate([
                'files.*' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            ]);
        } else {
            $this->validate([
                'files' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            ]);
        }
    }

    public function uploadDocuments()
    {
        $user = Auth::user();
        $customerId = $user?->getCustomerId();
        abort_unless(
            $user && $user->canEditCustomerPortal() && $customerId && (int) $this->shipment?->customer_id === (int) $customerId,
            403,
            'Akun View-Only tidak diizinkan mengunggah dokumen.'
        );

        $this->validate();
        $this->uploading = true;

        try {
            $fileList = is_array($this->files) ? $this->files : [$this->files];
            $count = 0;

            foreach ($fileList as $file) {
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $this->documentService->uploadDocument(
                        $file,
                        $this->shipment,
                        $this->documentType,
                        $this->description
                    );
                    $count++;
                }
            }

            session()->flash('message', "{$count} document(s) uploaded successfully!");
            
            $this->reset(['files', 'documentType', 'description']);
            $this->uploading = false;
            
            $this->dispatch('documentsUploaded');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Documents uploaded successfully'
            ]);

        } catch (\Exception $e) {
            $this->uploading = false;
            session()->flash('error', 'Upload failed: ' . $e->getMessage());
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to upload documents'
            ]);
        }
    }

    public function removeFile($index)
    {
        array_splice($this->files, $index, 1);
    }

    public function render()
    {
        return view('livewire.customer.document-upload');
    }
}
