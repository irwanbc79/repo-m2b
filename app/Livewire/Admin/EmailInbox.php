<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\Shipment;
use App\Models\Customer;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class EmailInbox extends Component
{
    use WithPagination;

    public $activeAccount = 'sales';
    public $mailboxes = ['sales', 'import', 'export', 'finance', 'gmail'];
    public $emails = [];
    public $selectedEmail = null;
    public $showConvertModal = false;

    public $customer_id;
    public $service_type = 'import';
    public $shipment_type = 'sea';
    public $selectedAttachments = [];

    public function mount()
    {
        $this->activeAccount = request()->query('mailbox', 'sales');
        if (!in_array($this->activeAccount, $this->mailboxes)) {
            $this->activeAccount = 'sales';
        }
        $this->loadEmails();

        // Handle direct selection dari URL jika ada
        $emailId = request()->query('email');
        if ($emailId) {
            $this->selectEmail((int)$emailId);
        }
    }

    public function loadEmails()
    {
        $this->emails = DB::table('emails')
            ->where('mailbox', $this->activeAccount)
            ->orderByDesc('email_date')
            ->limit(100)
            ->get()
            ->map(fn($email) => [
                'db_id' => $email->id,
                'uid' => $email->uid,
                'subject' => $email->subject ?: '(No Subject)',
                'from' => $email->from_email,
                'name' => $email->from_name ?: $email->from_email,
                'date' => $email->email_date ? Carbon::parse($email->email_date)->format('d M H:i') : '',
                'is_read' => (bool)$email->is_read,
                'attachments' => DB::table('email_attachments')->where('email_id', $email->id)->count()
            ])->toArray();
    }

    /**
     * Pilih email menggunakan wire:click (TANPA RELOAD)
     */
    public function selectEmail($dbId)
    {
        $email = DB::table('emails')->where('id', $dbId)->first();
        if (!$email) return;

        DB::table('emails')->where('id', $dbId)->update(['is_read' => true]);

        $attachments = DB::table('email_attachments')
            ->where('email_id', $email->id)
            ->get()
            ->toArray();

        $this->selectedEmail = [
            'db_id' => $email->id,
            'subject' => $email->subject ?: '(No Subject)',
            'from' => $email->from_email,
            'name' => $email->from_name ?: $email->from_email,
            'date' => Carbon::parse($email->email_date)->format('d M Y H:i'),
            'body' => $email->body ?: '(Konten kosong)',
            'attachments' => $attachments,
        ];
        
        $this->loadEmails();
        
        // Auto-select all attachments by default
        $this->selectedAttachments = array_keys($attachments);
    }

    public function syncNow()
    {
        try {
            Artisan::call('email:sync', ['mailbox' => $this->activeAccount, '--force' => true]);
            $this->loadEmails();
            session()->flash('message', 'Sinkronisasi selesai.');
        } catch (\Exception $e) {
            session()->flash('error', 'Sync gagal: ' . $e->getMessage());
        }
    }

    public function switchAccount($account)
    {
        $this->activeAccount = $account;
        $this->selectedEmail = null;
        $this->loadEmails();
    }

    public function getUnreadCount($account)
    {
        return DB::table('emails')->where('mailbox', $account)->where('is_read', false)->count();
    }



    public function selectAllAttachments()
    {
        if ($this->selectedEmail && isset($this->selectedEmail['attachments'])) {
            $this->selectedAttachments = array_keys($this->selectedEmail['attachments']);
        }
    }

    public function deselectAllAttachments()
    {
        $this->selectedAttachments = [];
    }

    public function convertToShipment()
    {
        $this->validate([
            'customer_id' => 'required',
            'service_type' => 'required',
            'shipment_type' => 'required',
        ]);

        DB::transaction(function () {
            $prefix = strtoupper(substr($this->service_type, 0, 3));
            $awb = $prefix . '-' . date('ymd') . '-' . rand(100, 999);

            $shipment = Shipment::create([
                'customer_id' => $this->customer_id,
                'awb_number' => $awb,
                'origin' => 'Email Conversion',
                'destination' => 'Indonesia',
                'service_type' => $this->service_type,
                'shipment_type' => $this->shipment_type,
                'status' => 'pending',
                'notes' => "Converted from email: " . $this->selectedEmail['subject']
            ]);

            $uploader = Auth::id() ?? 1;

            foreach ($this->selectedEmail['attachments'] as $idx => $att) {
                if (!in_array($idx, $this->selectedAttachments)) continue;

                Document::create([
                    'shipment_id' => $shipment->id,
                    'description' => 'Email Attachment: ' . $att->filename,
                    'file_path' => $att->file_path,
                    'filename' => $att->filename,
                    'is_internal' => false,
                    'uploaded_by' => $uploader,
                    'file_size' => $att->size,
                    'mime_type' => $att->mime_type,
                    'uploaded_at' => now(),
                ]);
            }
        });

        session()->flash('message', 'Email berhasil dikonversi ke Shipment');
        return redirect()->route('admin.shipments.index');
    }

    public $showReplyModal = false;
    public $replyTo = "";
    public $replySubject = "";
    public $replyBody = "";

    public function openReplyModal()
    {
        if (!$this->selectedEmail) return;
        
        $this->replyTo = $this->selectedEmail['from'];
        $this->replySubject = 'Re: ' . $this->selectedEmail['subject'];
        $this->replyBody = '';
        $this->showReplyModal = true;
    }

    public function sendReply()
    {
        $this->validate([
            'replyTo' => 'required|email',
            'replySubject' => 'required|string',
            'replyBody' => 'required|string|min:5',
        ]);

        try {
            \Mail::raw($this->replyBody, function($message) {
                $message->to($this->replyTo)
                    ->subject($this->replySubject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            session()->flash('message', 'Reply berhasil dikirim ke ' . $this->replyTo);
            $this->showReplyModal = false;
            $this->reset(['replyTo', 'replySubject', 'replyBody']);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal mengirim reply: ' . $e->getMessage());
        }
    }

    public function closeReplyModal()
    {
        $this->showReplyModal = false;
        $this->reset(['replyTo', 'replySubject', 'replyBody']);
    }


    public function render()
    {
        return view('livewire.admin.email-inbox', [
            'customers' => Customer::orderBy('company_name')->get()
        ])->layout('layouts.admin');
    }
}