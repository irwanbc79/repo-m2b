<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\PettyCashFund;
use App\Models\PettyCashTransaction;
use App\Models\PettyCashTopup;
use App\Models\Shipment;
use App\Models\User;
use App\Services\PettyCashService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class PettyCashManager extends Component
{
    use WithPagination, WithFileUploads;

    public $activeTab = 'transactions';
    public $fund;
    
    // Modals
    public $showModal = false;
    public $showTopupModal = false;
    public $showSettingModal = false;
    public $showPreviewModal = false;
    public $previewFile = '';
    public $previewType = '';

    // Form transaksi
    public $transaction_date;
    public $amount;
    public $category = '';
    public $description = '';
    public $shipment_id = '';
    public $proof_file;

    // Form top-up
    public $topup_amount;
    public $topup_notes = '';

    // Form setting
    public $setting_plafon;
    public $setting_max_transaction;
    public $setting_min_balance;
    public $setting_holder_id;
    public $setting_approver_id;
    public $setting_reason = '';

    // Access Control Roles
    const ROLES_CAN_APPROVE = ['super_admin', 'admin'];
    const ROLES_CAN_SETTING = ['super_admin', 'admin'];
    const ROLES_CAN_INPUT = ['super_admin', 'admin', 'staff'];

    protected function rules()
    {
        return [
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:1000|max:' . ($this->fund->max_transaction ?? 750000),
            'category' => 'required',
            'description' => 'required|min:3',
            'proof_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    // ==================== ACCESS CONTROL ====================
    
    public function canApprove(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        // Check by role column
        if (in_array($user->role, self::ROLES_CAN_APPROVE)) return true;
        
        // Check by roles JSON/text field (multi-role)
        if ($user->roles) {
            $roles = is_array($user->roles) ? $user->roles : json_decode($user->roles, true);
            if ($roles && (
                in_array('super_admin', $roles) || 
                in_array('director', $roles) || 
                in_array('manager', $roles) || 
                in_array('supervisor', $roles)
            )) return true;
        }
        
        return false;
    }

    public function canSetting(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        // Check by role column
        if (in_array($user->role, self::ROLES_CAN_SETTING)) return true;
        
        // Check by roles JSON/text field (multi-role)
        if ($user->roles) {
            $roles = is_array($user->roles) ? $user->roles : json_decode($user->roles, true);
            if ($roles && (
                in_array('super_admin', $roles) || 
                in_array('director', $roles)
            )) return true;
        }
        
        return false;
    }

    public function canInput(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        // Semua staff bisa input
        return in_array($user->role, self::ROLES_CAN_INPUT);
    }

    // ==================== LIFECYCLE ====================

    public function mount()
    {
        $this->fund = PettyCashFund::active()->first();
        $this->transaction_date = now()->format('Y-m-d');
        $this->loadSettings();
    }

    public function loadSettings()
    {
        if ($this->fund) {
            $this->setting_plafon = $this->fund->plafon;
            $this->setting_max_transaction = $this->fund->max_transaction;
            $this->setting_min_balance = $this->fund->min_balance_alert;
            $this->setting_holder_id = $this->fund->holder_user_id;
            $this->setting_approver_id = $this->fund->approver_user_id;
        }
    }

    // ==================== TRANSACTIONS ====================

    public function saveTransaction()
    {
        if (!$this->canInput()) {
            session()->flash('error', 'Anda tidak memiliki akses untuk input pengeluaran');
            return;
        }

        $this->validate();

        try {
            // Upload dan resize image untuk hemat space
            $file = $this->proof_file;
            $extension = strtolower($file->getClientOriginalExtension());

            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                // Resize image max 1200px, quality 80%
                $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                $image = $manager->read($file->getRealPath());
                if ($image->width() > 1200) {
                    $image->scale(width: 1200);
                }
                $filename = uniqid() . '.' . $extension;
                $path = 'petty-cash/proofs/' . $filename;
                Storage::disk('public')->put($path, $image->toJpeg(80));
            } else {
                $path = $file->store('petty-cash/proofs', 'public');
            }
            
            app(PettyCashService::class)->createTransaction($this->fund, [
                'transaction_date' => $this->transaction_date,
                'amount' => $this->amount,
                'category' => $this->category,
                'description' => $this->description,
                'shipment_id' => $this->shipment_id ?: null,
                'proof_file' => $path,
            ]);

            $this->fund->refresh();
            $this->reset(['amount', 'category', 'description', 'shipment_id', 'proof_file']);
            $this->showModal = false;
            session()->flash('success', 'Transaksi berhasil disimpan & jurnal otomatis dibuat');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // ==================== EDIT & BATAL TRANSAKSI ====================

    public $editId = null;
    public $editTanggal = '';
    public $editJumlah = '';
    public $editKategori = '';
    public $editKeterangan = '';
    public $editJob = '';
    public $editAlasan = '';
    public $showEditModal = false;

    public $batalId = null;
    public $batalAlasan = '';
    public $showBatalModal = false;

    public $riwayatId = null;
    public $showRiwayatModal = false;

    /**
     * Boleh mengubah/membatalkan bila: pencatatnya sendiri, pemegang kas,
     * atau punya wewenang approve. Transaksi yang sudah dibatalkan terkunci.
     */
    public function canEditTransaction(PettyCashTransaction $t): bool
    {
        if ($t->status === 'cancelled') {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $this->canApprove()
            || (int) $t->created_by === (int) $user->id
            || (int) $this->fund?->holder_user_id === (int) $user->id;
    }

    public function openEdit($id)
    {
        $t = PettyCashTransaction::findOrFail($id);

        if (! $this->canEditTransaction($t)) {
            session()->flash('error', 'Anda tidak berhak mengubah transaksi ini.');

            return;
        }

        $this->editId         = $t->id;
        $this->editTanggal    = optional($t->transaction_date)->format('Y-m-d') ?? (string) $t->transaction_date;
        $this->editJumlah     = (float) $t->amount;
        $this->editKategori   = $t->category;
        $this->editKeterangan = $t->description;
        $this->editJob        = $t->shipment_id;
        $this->editAlasan     = '';
        $this->showEditModal  = true;
    }

    public function saveEdit()
    {
        $t = PettyCashTransaction::findOrFail($this->editId);

        if (! $this->canEditTransaction($t)) {
            session()->flash('error', 'Anda tidak berhak mengubah transaksi ini.');

            return;
        }

        $this->validate([
            'editTanggal'    => 'required|date',
            'editJumlah'     => 'required|numeric|min:1',
            'editKategori'   => 'required',
            'editKeterangan' => 'required|string|max:255',
            // Alasan wajib supaya jejaknya bermakna, bukan sekadar "diubah".
            'editAlasan'     => 'required|string|min:3|max:255',
        ], [], [
            'editTanggal' => 'tanggal', 'editJumlah' => 'jumlah',
            'editKategori' => 'kategori', 'editKeterangan' => 'keterangan',
            'editAlasan' => 'alasan perubahan',
        ]);

        try {
            app(PettyCashService::class)->updateTransaction($t, [
                'transaction_date' => $this->editTanggal,
                'amount'           => $this->editJumlah,
                'category'         => $this->editKategori,
                'description'      => $this->editKeterangan,
                'shipment_id'      => $this->editJob ?: null,
            ], $this->editAlasan);

            $this->fund->refresh();
            $this->showEditModal = false;
            $this->reset(['editId', 'editTanggal', 'editJumlah', 'editKategori', 'editKeterangan', 'editJob', 'editAlasan']);
            session()->flash('success', 'Transaksi diperbarui. Jurnal lama dibalik & jurnal baru dibuat otomatis.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openBatal($id)
    {
        $t = PettyCashTransaction::findOrFail($id);

        if (! $this->canEditTransaction($t)) {
            session()->flash('error', 'Anda tidak berhak membatalkan transaksi ini.');

            return;
        }

        $this->batalId        = $t->id;
        $this->batalAlasan    = '';
        $this->showBatalModal = true;
    }

    public function confirmBatal()
    {
        $t = PettyCashTransaction::findOrFail($this->batalId);

        if (! $this->canEditTransaction($t)) {
            session()->flash('error', 'Anda tidak berhak membatalkan transaksi ini.');

            return;
        }

        $this->validate(
            ['batalAlasan' => 'required|string|min:3|max:255'],
            [],
            ['batalAlasan' => 'alasan pembatalan']
        );

        try {
            app(PettyCashService::class)->cancelTransaction($t, $this->batalAlasan);

            $this->fund->refresh();
            $this->showBatalModal = false;
            $this->reset(['batalId', 'batalAlasan']);
            session()->flash('success', 'Transaksi dibatalkan. Saldo dikembalikan & efeknya di buku besar ditiadakan.');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openRiwayat($id)
    {
        $this->riwayatId        = $id;
        $this->showRiwayatModal = true;
    }

    // ==================== TOP-UP ====================

    public function requestTopup()
    {
        $this->validate(['topup_amount' => 'required|numeric|min:50000']);

        try {
            $topup = app(PettyCashService::class)->requestTopup($this->fund, $this->topup_amount, $this->topup_notes);
            
            // Send email notification to approver
            $this->sendTopupRequestEmail($topup);
            
            $this->reset(['topup_amount', 'topup_notes']);
            $this->showTopupModal = false;
            session()->flash('success', 'Request top up berhasil dikirim ke approver');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function approveTopup($id)
    {
        if (!$this->canApprove()) {
            session()->flash('error', 'Anda tidak memiliki akses untuk approve top-up');
            return;
        }

        try {
            $topup = PettyCashTopup::findOrFail($id);
            app(PettyCashService::class)->approveTopup($topup);
            
            // Send email to holder
            $this->sendTopupApprovedEmail($topup);
            
            session()->flash('success', 'Top up diapprove, silakan transfer dana');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function transferTopup($id)
    {
        if (!$this->canApprove()) {
            session()->flash('error', 'Anda tidak memiliki akses untuk konfirmasi transfer');
            return;
        }

        try {
            $topup = PettyCashTopup::findOrFail($id);
            app(PettyCashService::class)->processTopupTransfer($topup);
            $this->fund->refresh();
            
            // Send confirmation email
            $this->sendTopupTransferredEmail($topup);
            
            session()->flash('success', 'Transfer berhasil, saldo & jurnal diperbarui');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function rejectTopup($id, $reason = 'Ditolak oleh approver')
    {
        if (!$this->canApprove()) {
            session()->flash('error', 'Anda tidak memiliki akses untuk menolak top-up');
            return;
        }

        try {
            $topup = PettyCashTopup::findOrFail($id);
            app(PettyCashService::class)->rejectTopup($topup, $reason);
            
            // Send rejection email
            $this->sendTopupRejectedEmail($topup);
            
            session()->flash('success', 'Top up ditolak');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // ==================== EMAIL NOTIFICATIONS ====================

    protected function sendTopupRequestEmail(PettyCashTopup $topup)
    {
        try {
            $approver = $this->fund->approver;
            if (!$approver || !$approver->email) return;

            $data = [
                'topup' => $topup,
                'fund' => $this->fund,
                'requester' => Auth::user(),
            ];

            Mail::send('emails.petty-cash.topup-request', $data, function ($message) use ($approver, $topup) {
                $message->to($approver->email, $approver->name)
                        ->subject("[M2B] Request Top Up Kas Kecil - {$topup->topup_number}");
            });
        } catch (\Exception $e) {
            \Log::warning('Failed to send topup request email: ' . $e->getMessage());
        }
    }

    protected function sendTopupApprovedEmail(PettyCashTopup $topup)
    {
        try {
            $holder = $this->fund->holder;
            if (!$holder || !$holder->email) return;

            $data = [
                'topup' => $topup,
                'fund' => $this->fund,
                'approver' => Auth::user(),
            ];

            Mail::send('emails.petty-cash.topup-approved', $data, function ($message) use ($holder, $topup) {
                $message->to($holder->email, $holder->name)
                        ->subject("[M2B] Top Up Kas Kecil Diapprove - {$topup->topup_number}");
            });
        } catch (\Exception $e) {
            \Log::warning('Failed to send topup approved email: ' . $e->getMessage());
        }
    }

    protected function sendTopupTransferredEmail(PettyCashTopup $topup)
    {
        try {
            $holder = $this->fund->holder;
            if (!$holder || !$holder->email) return;

            $topup->refresh();
            $data = [
                'topup' => $topup,
                'fund' => $this->fund->fresh(),
            ];

            Mail::send('emails.petty-cash.topup-transferred', $data, function ($message) use ($holder, $topup) {
                $message->to($holder->email, $holder->name)
                        ->subject("[M2B] Dana Kas Kecil Sudah Ditransfer - {$topup->topup_number}");
            });
        } catch (\Exception $e) {
            \Log::warning('Failed to send topup transferred email: ' . $e->getMessage());
        }
    }

    protected function sendTopupRejectedEmail(PettyCashTopup $topup)
    {
        try {
            $requester = $topup->requester;
            if (!$requester || !$requester->email) return;

            $data = [
                'topup' => $topup,
                'fund' => $this->fund,
                'rejector' => Auth::user(),
            ];

            Mail::send('emails.petty-cash.topup-rejected', $data, function ($message) use ($requester, $topup) {
                $message->to($requester->email, $requester->name)
                        ->subject("[M2B] Top Up Kas Kecil Ditolak - {$topup->topup_number}");
            });
        } catch (\Exception $e) {
            \Log::warning('Failed to send topup rejected email: ' . $e->getMessage());
        }
    }

    // ==================== SETTINGS ====================

    public function saveSettings()
    {
        if (!$this->canSetting()) {
            session()->flash('error', 'Anda tidak memiliki akses untuk mengubah pengaturan');
            return;
        }

        $this->validate([
            'setting_plafon' => 'required|numeric|min:100000',
            'setting_max_transaction' => 'required|numeric|min:10000',
            'setting_min_balance' => 'required|numeric|min:0',
            'setting_holder_id' => 'required|exists:users,id',
        ]);

        try {
            app(PettyCashService::class)->updateFundSettings($this->fund, [
                'plafon' => $this->setting_plafon,
                'max_transaction' => $this->setting_max_transaction,
                'min_balance_alert' => $this->setting_min_balance,
                'holder_user_id' => $this->setting_holder_id,
                'approver_user_id' => $this->setting_approver_id,
            ], $this->setting_reason);

            $this->fund->refresh();
            $this->showSettingModal = false;
            $this->setting_reason = '';
            session()->flash('success', 'Pengaturan kas kecil berhasil diperbarui');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // ==================== UTILITIES ====================

    public function previewProof($transactionId)
    {
        $t = \App\Models\PettyCashTransaction::find($transactionId);
        if (!$t || !$t->proof_file) {
            session()->flash("error", "Bukti tidak ditemukan.");
            return;
        }
        $this->previewFile = Storage::disk("public")->url($t->proof_file);
        $ext = strtolower(pathinfo($t->proof_file, PATHINFO_EXTENSION));
        $this->previewType = in_array($ext, ["jpg", "jpeg", "png", "webp"]) ? "image" : "pdf";
        $this->showPreviewModal = true;
    }

    // ==================== RENDER ====================

    public function render()
    {
        // withCount logs: dipakai menampilkan penanda "pernah diubah" di daftar
        // tanpa query tambahan per baris.
        $transactions = $this->fund
            ? $this->fund->transactions()->with(['shipment.customer', 'creator'])->withCount('logs')->latest()->paginate(15, ['*'], 'txPage')
            : collect();

        $riwayat = $this->riwayatId
            ? \App\Models\PettyCashTransactionLog::where('petty_cash_transaction_id', $this->riwayatId)->latest('id')->get()
            : collect();

        $topups = $this->fund
            ? $this->fund->topups()->with(['requester', 'approver'])->latest()->paginate(10, ['*'], 'topPage')
            : collect();

        $settingLogs = $this->fund
            ? $this->fund->settingLogs()->with('changedBy')->latest()->limit(10)->get()
            : collect();

        $pendingTopups = $this->fund ? $this->fund->topups()->pending()->count() : 0;
        
        $shipments = Shipment::with('customer:id,company_name')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'awb_number', 'customer_id']);
            
        $users = User::whereIn('role', ['super_admin', 'admin', 'staff'])->orderBy('name')->get(['id', 'name', 'role']);
        $summary = $this->fund ? app(PettyCashService::class)->getSummary($this->fund, 'month') : [];

        return view('livewire.admin.petty-cash-manager', [
            'transactions' => $transactions,
            'topups' => $topups,
            'settingLogs' => $settingLogs,
            'pendingTopups' => $pendingTopups,
            'shipments' => $shipments,
            'users' => $users,
            'summary' => $summary,
            'canApprove' => $this->canApprove(),
            'canSetting' => $this->canSetting(),
            'canInput' => $this->canInput(),
            'riwayat' => $riwayat,
        ])->layout('layouts.admin');
    }
}
