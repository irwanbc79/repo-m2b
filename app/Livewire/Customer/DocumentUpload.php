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

    protected $rules = [
        'files' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        'documentType' => 'required|in:invoice,packing_list,certificate,other',
        'description' => 'nullable|string|max:500'];

    protected $messages = [
        'files.*.required' => 'Please select at least one file',
        'files.*.max' => 'File size must not exceed 10MB',
        'files.*.mimes' => 'Only PDF, images, Word, and Excel files are allowed'];

    public function boot(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function mount($shipmentId)
    {
        $this->shipment = Auth::user()->customer->shipments()->findOrFail($shipmentId);
    }

    public function updatedFiles()
    {
        $this->validate([
            'files.*' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx']);
    }

    public function uploadDocuments()
    {
        $this->validate();
        $this->uploading = true;

        try {
            $uploadedDocuments = $this->documentService->uploadDocument(
                $this->files,
                $this->shipment,
                $this->documentType,
                $this->description
            );

            session()->flash('message', 1 . ' document(s) uploaded successfully!');
            
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
