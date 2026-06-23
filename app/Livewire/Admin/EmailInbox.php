<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Shipment;
use App\Models\Customer;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Webklex\IMAP\Facades\Client;
use Carbon\Carbon;

class EmailInbox extends Component
{
    use WithPagination;

    public $activeAccount = 'sales';
    public $mailboxes = ['sales', 'import', 'export', 'finance', 'gmail', 'pajak', 'shipping'];
    public $emails = [];
    // Jumlah email belum dibaca per mailbox. Dihitung sekali (1 query grouped)
    // di loadEmails(), menggantikan pemanggilan getUnreadCount() per-mailbox di blade.
    public $unreadCounts = [];
    public $selectedEmail = null;
    public $showConvertModal = false;

    public $customer_id;
    public $service_type = 'import';
    public $shipment_type = 'sea';
    public $selectedAttachments = [];

    // Mode: 'new' = buat shipment baru, 'existing' = masukkan ke shipment existing
    public $convertMode = 'new';
    public $existingShipmentId = null;
    public $existingShipmentSearch = '';

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
        $emails = DB::table('emails')
            ->where('mailbox', $this->activeAccount)
            ->orderByDesc('email_date')
            ->limit(100)
            ->get();

        $ids = $emails->pluck('id');
        $attachmentCounts = $ids->isEmpty()
            ? collect()
            : DB::table('email_attachments')
                ->whereIn('email_id', $ids)
                ->selectRaw('email_id, COUNT(*) as c')
                ->groupBy('email_id')
                ->pluck('c', 'email_id');

        $this->emails = $emails->map(fn ($email) => [
            'db_id' => $email->id,
            'uid' => $email->uid,
            'subject' => $email->subject ?: '(No Subject)',
            'from' => $email->from_email,
            'name' => $email->from_name ?: $email->from_email,
            'date' => $email->email_date ? Carbon::parse($email->email_date)->format('d M H:i') : '',
            'is_read' => (bool) $email->is_read,
            'attachments' => (int) ($attachmentCounts[$email->id] ?? 0),
        ])->toArray();

        $this->loadUnreadCounts();
    }

    /**
     * Hitung jumlah unread untuk SEMUA mailbox dalam satu query (anti N+1).
     */
    protected function loadUnreadCounts()
    {
        $this->unreadCounts = DB::table('emails')
            ->where('is_read', false)
            ->whereIn('mailbox', $this->mailboxes)
            ->selectRaw('mailbox, COUNT(*) as c')
            ->groupBy('mailbox')
            ->pluck('c', 'mailbox')
            ->toArray();
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
            ->map(fn($a) => (array) $a)
            ->toArray();

        $this->selectedEmail = [
            'db_id' => $email->id,
            'subject' => $email->subject ?: '(No Subject)',
            'from' => $email->from_email,
            'name' => $email->from_name ?: $email->from_email,
            'date' => Carbon::parse($email->email_date)->format('d M Y H:i'),
            // 'body' => $email->body ?: '(Konten kosong)', // REMOVED FOR IFRAME OPTIMIZATION
            'attachments' => $attachments,
        ];
        
        $this->loadEmails();
        
        // Auto-select all attachments by default
        $this->selectedAttachments = array_keys($attachments);

        // Cek apakah ada attachment yang filenya sudah hilang dari disk
        $this->checkMissingAttachments();
    }

    public $hasMissingAttachments = false;

    protected function checkMissingAttachments()
    {
        if (!$this->selectedEmail) return;
        foreach ($this->selectedEmail['attachments'] as $att) {
            $path = $att['file_path'] ?? null;
            if ($path && !Storage::disk('public')->exists($path) && !Storage::disk('local')->exists($path)) {
                $this->hasMissingAttachments = true;
                return;
            }
        }
        $this->hasMissingAttachments = false;
    }

    public function redownloadAttachments()
    {
        if (!$this->selectedEmail) return;

        $email = DB::table('emails')->where('id', $this->selectedEmail['db_id'])->first();
        if (!$email) return;

        try {
            $client = Client::account($email->mailbox);
            if (!$client->isConnected()) $client->connect();

            $folders = $client->getFolders();
            $targetFolder = null;
            foreach ($folders as $folder) {
                $name = strtolower($folder->name);
                if ($name === 'inbox' || str_contains($name, 'fokus') || str_contains($name, 'focused')) {
                    $targetFolder = $folder;
                    break;
                }
            }
            if (!$targetFolder) {
                session()->flash('error', 'Folder INBOX tidak ditemukan di server email.');
                return;
            }

            // Cari message by UID — coba beberapa cara karena API berbeda per versi
            $message = null;
            try {
                $result = $targetFolder->messages()->getMessageByUid($email->uid);
                // getMessageByUid bisa return single Message atau MessageCollection
                if ($result && is_object($result)) {
                    $message = method_exists($result, 'first') ? $result->first() : $result;
                }
            } catch (\Throwable $e) {
                // Fallback: cari lewat query UID manual
            }

            if (!$message) {
                // Fallback: fetch dengan filter UID
                try {
                    $col = $targetFolder->messages()
                        ->whereUid($email->uid)
                        ->get();
                    $message = $col->first();
                } catch (\Throwable $e2) {
                    // ignore
                }
            }

            if (!$message) {
                session()->flash('error', 'Email tidak ditemukan di server IMAP (mungkin sudah dihapus dari kotak masuk).');
                return;
            }
            $mailbox  = $email->mailbox;
            $uid      = $email->uid;
            $restored = 0;

            foreach ($message->getAttachments() as $att) {
                $filename = $att->getName();
                $slug     = Str::slug(pathinfo($filename, PATHINFO_FILENAME));
                $ext      = pathinfo($filename, PATHINFO_EXTENSION) ?: 'bin';
                $newPath  = "email_attachments/{$mailbox}/{$uid}/{$slug}_" . uniqid() . ".{$ext}";

                Storage::disk('public')->put($newPath, $att->getContent());

                // Update path di tabel email_attachments (cari by filename yang mirip)
                $record = DB::table('email_attachments')
                    ->where('email_id', $email->id)
                    ->where('filename', $filename)
                    ->first();

                if ($record) {
                    DB::table('email_attachments')->where('id', $record->id)->update(['file_path' => $newPath]);
                } else {
                    DB::table('email_attachments')->insert([
                        'email_id'  => $email->id,
                        'filename'  => $filename,
                        'file_path' => $newPath,
                        'mime_type' => $att->getMimeType(),
                        'size'      => $att->getSize(),
                        'created_at' => now(),
                    ]);
                }
                $restored++;
            }

            // Reload attachments setelah restore
            $this->selectEmail($email->id);
            session()->flash('message', "{$restored} file berhasil diunduh ulang dari server email.");

        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal download ulang: ' . $e->getMessage());
        }
    }

    public function syncNow()
    {
        try {
            @set_time_limit(120);
            Artisan::call('email:sync', [
                'mailbox' => $this->activeAccount,
                '--force' => true,
                '--days' => 2
            ]);
            $this->loadEmails();
            session()->flash('message', 'Sinkronisasi selesai.');
        } catch (\Throwable $e) {
            \Log::error('Sync error via button: ' . $e->getMessage(), [
                'exception' => $e
            ]);
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
        // Baca dari cache yang sudah dihitung di loadUnreadCounts() (tanpa query baru).
        return (int) ($this->unreadCounts[$account] ?? 0);
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

    public function getExistingShipmentsProperty()
    {
        if (strlen($this->existingShipmentSearch) < 2) return collect();
        $q = $this->existingShipmentSearch;
        return Shipment::with('customer')
            ->where(function($query) use ($q) {
                $query->where('awb_number', 'like', "%{$q}%")
                      ->orWhereHas('customer', fn($c) => $c->where('company_name', 'like', "%{$q}%"));
            })
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    public function convertToShipment()
    {
        if ($this->convertMode === 'existing') {
            $this->validate(['existingShipmentId' => 'required']);
            $shipment = Shipment::findOrFail($this->existingShipmentId);
        } else {
            $this->validate([
                'customer_id'   => 'required',
                'service_type'  => 'required',
                'shipment_type' => 'required',
            ]);
        }

        $skippedFiles = [];

        DB::transaction(function () use (&$shipment, &$skippedFiles) {
            if ($this->convertMode === 'new') {
                $prefix   = strtoupper(substr($this->service_type, 0, 3));
                $awb      = $prefix . '-' . date('ymd') . '-' . rand(100, 999);
                $shipment = Shipment::create([
                    'customer_id'   => $this->customer_id,
                    'awb_number'    => $awb,
                    'origin'        => 'Email Conversion',
                    'destination'   => 'Indonesia',
                    'service_type'  => $this->service_type,
                    'shipment_type' => $this->shipment_type,
                    'status'        => 'pending',
                    'notes'         => "Converted from email: " . $this->selectedEmail['subject'],
                ]);
            }

            $uploader = Auth::id() ?? 1;
            $extMap   = [
                'pdf'  => 'application/pdf',
                'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'png'  => 'image/png',  'gif'  => 'image/gif', 'webp' => 'image/webp',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xls'  => 'application/vnd.ms-excel',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'doc'  => 'application/msword',
            ];

            foreach ($this->selectedEmail['attachments'] as $idx => $att) {
                if (!in_array($idx, $this->selectedAttachments)) continue;

                $attArr     = is_array($att) ? $att : (array) $att;
                $sourcePath = $attArr['file_path'] ?? null;
                $filename   = $attArr['filename'] ?? 'attachment';
                $fileSize   = $attArr['size'] ?? null;
                $ext        = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'bin';
                $mimeType   = $attArr['mime_type'] ?? $extMap[$ext] ?? 'application/octet-stream';

                $safeName = pathinfo($filename, PATHINFO_FILENAME);
                $destPath = 'documents/public/' . $safeName . '_' . uniqid() . '.' . $ext;

                if ($sourcePath && Storage::disk('public')->exists($sourcePath)) {
                    Storage::disk('public')->copy($sourcePath, $destPath);
                } elseif ($sourcePath && Storage::disk('local')->exists($sourcePath)) {
                    Storage::disk('public')->put($destPath, Storage::disk('local')->get($sourcePath));
                } else {
                    // File sudah terhapus dari temporary storage — skip, jangan buat dokumen rusak
                    $skippedFiles[] = $filename;
                    continue;
                }

                Document::create([
                    'shipment_id' => $shipment->id,
                    'description' => 'Email Attachment: ' . $filename,
                    'file_path'   => $destPath,
                    'filename'    => $filename,
                    'is_internal' => false,
                    'uploaded_by' => $uploader,
                    'file_size'   => $fileSize,
                    'mime_type'   => $mimeType,
                    'uploaded_at' => now(),
                ]);
            }
        });

        if (!empty($skippedFiles)) {
            $skippedList = implode(', ', $skippedFiles);
            session()->flash('warning', count($skippedFiles) . ' file tidak dapat disalin karena sudah terhapus dari temporary storage (>30 hari): ' . $skippedList);
        }

        $msg = $this->convertMode === 'existing'
            ? 'Dokumen berhasil ditambahkan ke Shipment #' . ($shipment->awb_number ?? '')
            : 'Email berhasil dikonversi ke Shipment baru';
        session()->flash('message', $msg);
        return redirect()->route('admin.shipments.show', $shipment->id);
    }

    public $showReplyModal = false;
    public $replyTo = "";
    public $replySubject = "";
    public $replyBody = "";
    public $selectedTemplate = "";
    public $templateLang = "ID";

    public function getTemplatesProperty()
    {
        $templates = config("email_templates.templates", []);
        $filtered = [];
        
        foreach ($templates as $key => $template) {
            if (($template["lang"] ?? "") === $this->templateLang) {
                $category = $template["category"] ?? "Umum";
                if (!isset($filtered[$category])) {
                    $filtered[$category] = [];
                }
                $filtered[$category][$key] = $template;
            }
        }
        
        return $filtered;
    }

    public function applyTemplate()
    {
        if (empty($this->selectedTemplate)) return;
        
        $template = config("email_templates.templates.{$this->selectedTemplate}");
        if (!$template) return;
        
        // Replace placeholders
        $body = $template["body"];
        $body = str_replace("{staff_name}", auth()->user()->name, $body);
        $body = str_replace("{original_subject}", $this->selectedEmail["subject"] ?? "", $body);
        
        $this->replyBody = $body;
        $this->selectedTemplate = "";
    }

    public function switchTemplateLang()
    {
        $this->templateLang = $this->templateLang === "ID" ? "EN" : "ID";
    }

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

        // Mapping mailbox ke email address
        $mailboxEmails = [
            'sales' => 'sales@m2b.co.id',
            'import' => 'import@m2b.co.id',
            'export' => 'export@m2b.co.id',
            'finance' => 'finance@m2b.co.id',
            'gmail' => 'logisolmed@gmail.com',
            'pajak' => 'moramultiberkahpt@gmail.com',
            'shipping' => 'shipping@m2b.co.id',
        ];
        
        $fromEmail = $mailboxEmails[$this->activeAccount] ?? config('mail.from.address');
        $fromName = 'M2B - ' . ucfirst($this->activeAccount);

        try {
            \Log::info('Sending reply email', [
                'to' => $this->replyTo,
                'from' => $fromEmail,
                'subject' => $this->replySubject,
                'mailbox' => $this->activeAccount,
                'user' => auth()->user()->name ?? 'unknown'
            ]);

            \Mail::raw($this->replyBody, function($message) use ($fromEmail, $fromName) {
                $message->to($this->replyTo)
                    ->subject($this->replySubject)
                    ->from(config("mail.from.address"), $fromName)->replyTo($fromEmail, $fromName);
            });

            \Log::info('Email sent successfully, saving to database');

            // Simpan ke database sent_emails
            \App\Models\SentEmail::create([
                'mailbox' => $this->activeAccount,
                'to_email' => $this->replyTo,
                'subject' => $this->replySubject,
                'body' => $this->replyBody,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
            ]);

            \Log::info('Email saved to sent_emails');
            
            session()->flash('message', 'Reply berhasil dikirim ke ' . $this->replyTo);
            $this->showReplyModal = false;
            $this->reset(['replyTo', 'replySubject', 'replyBody']);
            
        } catch (\Exception $e) {
            \Log::error('Failed to send reply email', [
                'error' => $e->getMessage(),
                'to' => $this->replyTo,
                'mailbox' => $this->activeAccount
            ]);
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