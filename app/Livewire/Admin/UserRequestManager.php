<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\UserRequest;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class UserRequestManager extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $showDetailModal = false;
    public $showRejectModal = false;
    public $selectedRequest = null;
    public $rejectReason = '';

    protected $queryString = ['search', 'statusFilter'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function viewDetail($requestId)
    {
        $this->selectedRequest = UserRequest::with(['customer', 'requestedBy', 'processedBy'])->find($requestId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedRequest = null;
    }

    public function openRejectModal($requestId)
    {
        $this->selectedRequest = UserRequest::find($requestId);
        $this->rejectReason = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal()
    {
        $this->showRejectModal = false;
        $this->rejectReason = '';
    }

    public function approveRequest($requestId)
    {
        // Check permission - only director and super_admin can approve
        $currentUser = Auth::user();
        if (!$currentUser->isDirectorLevel() && $currentUser->role !== 'super_admin') {
            session()->flash('error', 'Anda tidak memiliki izin untuk approve request ini. Hanya Direktur dan Super Admin yang dapat melakukan approval.');
            return;
        }

        $request = UserRequest::with('customer')->find($requestId);
        
        if (!$request || $request->status !== 'pending') {
            session()->flash('error', 'Request tidak valid atau sudah diproses.');
            return;
        }

        DB::beginTransaction();
        try {
            // 1. Generate random password
            $randomPassword = Str::random(10);
            
            // 2. Determine role based on access_level
            $role = $request->access_level === 'full_access' ? 'customer' : 'customer_viewer';
            
            // 3. Create new user
            $newUser = User::create([
                'name' => $request->pic_name,
                'email' => $request->pic_email,
                'password' => Hash::make($randomPassword),
                'role' => $role,
                'email_verified_at' => now(), // Auto verify karena sudah di-approve
            ]);
            
            // 4. Link user to customer via pivot table
            DB::table('customer_user')->insert([
                'customer_id' => $request->customer_id,
                'user_id' => $newUser->id,
                'is_primary' => false, // Secondary user
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // 5. Update request status
            $request->update([
                'status' => 'approved',
                'processed_by' => $currentUser->id,
                'processed_at' => now(),
                'admin_notes' => 'User berhasil dibuat dengan role: ' . $role,
            ]);
            
            // 6. Send email to new user with credentials
            $this->sendCredentialsEmail($newUser, $randomPassword, $request);
            
            // 7. Send notification to requester
            $this->sendApprovalNotificationToRequester($request, $newUser);
            
            DB::commit();
            
            session()->flash('message', "✅ User {$request->pic_name} berhasil dibuat dan email kredensial telah dikirim ke {$request->pic_email}");
            
            $this->closeDetailModal();
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error approving user request: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function rejectRequest()
    {
        // Check permission
        $currentUser = Auth::user();
        if (!$currentUser->isDirectorLevel() && $currentUser->role !== 'super_admin') {
            session()->flash('error', 'Anda tidak memiliki izin untuk reject request ini.');
            return;
        }

        if (!$this->selectedRequest) {
            return;
        }

        $this->validate([
            'rejectReason' => 'required|min:10|max:500',
        ], [
            'rejectReason.required' => 'Alasan penolakan wajib diisi',
            'rejectReason.min' => 'Alasan minimal 10 karakter',
        ]);

        $this->selectedRequest->update([
            'status' => 'rejected',
            'processed_by' => $currentUser->id,
            'processed_at' => now(),
            'admin_notes' => $this->rejectReason,
        ]);

        // Send rejection email to requester
        $this->sendRejectionNotification($this->selectedRequest);

        session()->flash('message', "Request dari {$this->selectedRequest->pic_name} telah ditolak.");
        
        $this->closeRejectModal();
        $this->closeDetailModal();
    }

    protected function sendCredentialsEmail($user, $password, $request)
    {
        $customer = $request->customer;
        $loginUrl = url('/login');
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; padding: 25px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 25px; background: #f9fafb; }
                .credentials-box { background: #1e3a5f; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .credentials-box p { margin: 10px 0; }
                .btn { display: inline-block; background: #059669; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #6b7280; background: #f3f4f6; border-radius: 0 0 10px 10px; }
                .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='margin:0;'>🎉 Selamat! Akun Portal M2B Anda Telah Aktif</h2>
                </div>
                <div class='content'>
                    <p>Yth. <strong>{$user->name}</strong>,</p>
                    <p>Akun Portal M2B Anda telah berhasil dibuat untuk perusahaan <strong>{$customer->company_name}</strong>.</p>
                    
                    <div class='credentials-box'>
                        <h3 style='margin-top:0; color: #10b981;'>🔐 Kredensial Login Anda</h3>
                        <p><strong>Email:</strong> {$user->email}</p>
                        <p><strong>Password:</strong> {$password}</p>
                        <p><strong>URL Login:</strong> <a href='{$loginUrl}' style='color: #10b981;'>{$loginUrl}</a></p>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ Penting:</strong>
                        <ul style='margin: 10px 0; padding-left: 20px;'>
                            <li>Segera ubah password Anda setelah login pertama</li>
                            <li>Jangan bagikan kredensial ini kepada siapapun</li>
                            <li>Password bersifat case-sensitive</li>
                        </ul>
                    </div>
                    
                    <p style='text-align: center; margin-top: 25px;'>
                        <a href='{$loginUrl}' class='btn'>Login Sekarang</a>
                    </p>
                    
                    <p style='margin-top: 25px;'>Jika ada pertanyaan, silakan hubungi:<br>
                    📧 Email: <a href='mailto:support@m2b.co.id'>support@m2b.co.id</a></p>
                </div>
                <div class='footer'>
                    <p><strong>M2B Logistic Solution</strong><br>
                    © " . date('Y') . " M2B. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        try {
            Mail::html($body, function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('[M2B] Akun Portal Anda Telah Aktif - Kredensial Login')
                    ->from('noreply@m2b.co.id', 'Portal M2B');
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send credentials email: ' . $e->getMessage());
        }
    }

    protected function sendApprovalNotificationToRequester($request, $newUser)
    {
        $requester = $request->requestedBy;
        if (!$requester) return;

        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; padding: 25px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 25px; background: #f9fafb; }
                .success-box { background: #d1fae5; border: 1px solid #10b981; padding: 15px; border-radius: 8px; margin: 15px 0; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #6b7280; background: #f3f4f6; border-radius: 0 0 10px 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='margin:0;'>✅ Request User Disetujui</h2>
                </div>
                <div class='content'>
                    <p>Yth. <strong>{$requester->name}</strong>,</p>
                    <p>Request penambahan user baru Anda telah <strong>DISETUJUI</strong>.</p>
                    
                    <div class='success-box'>
                        <h4 style='margin-top:0; color: #059669;'>Detail User Baru:</h4>
                        <p><strong>Nama:</strong> {$newUser->name}</p>
                        <p><strong>Email:</strong> {$newUser->email}</p>
                        <p><strong>Level Akses:</strong> " . ($request->access_level == 'full_access' ? '🔓 Full Access' : '👁️ View Only') . "</p>
                    </div>
                    
                    <p>Kredensial login telah dikirimkan langsung ke email <strong>{$newUser->email}</strong>.</p>
                    
                    <p>Terima kasih telah menggunakan Portal M2B.</p>
                </div>
                <div class='footer'>
                    <p><strong>M2B Logistic Solution</strong><br>
                    Request ID: #{$request->id}</p>
                </div>
            </div>
        </body>
        </html>
        ";

        try {
            Mail::html($body, function ($message) use ($requester) {
                $message->to($requester->email)
                    ->subject('[M2B] Request User Anda Telah Disetujui')
                    ->from('noreply@m2b.co.id', 'Portal M2B');
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send approval notification: ' . $e->getMessage());
        }
    }

    protected function sendRejectionNotification($request)
    {
        $requester = $request->requestedBy;
        if (!$requester) return;

        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: white; padding: 25px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 25px; background: #f9fafb; }
                .reject-box { background: #fef2f2; border: 1px solid #fecaca; padding: 15px; border-radius: 8px; margin: 15px 0; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #6b7280; background: #f3f4f6; border-radius: 0 0 10px 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='margin:0;'>❌ Request User Ditolak</h2>
                </div>
                <div class='content'>
                    <p>Yth. <strong>{$requester->name}</strong>,</p>
                    <p>Mohon maaf, request penambahan user baru Anda untuk <strong>{$request->pic_name}</strong> tidak dapat kami proses.</p>
                    
                    <div class='reject-box'>
                        <h4 style='margin-top:0; color: #dc2626;'>Alasan Penolakan:</h4>
                        <p>{$request->admin_notes}</p>
                    </div>
                    
                    <p>Jika Anda memiliki pertanyaan, silakan hubungi tim sales kami di <a href='mailto:sales@m2b.co.id'>sales@m2b.co.id</a>.</p>
                </div>
                <div class='footer'>
                    <p><strong>M2B Logistic Solution</strong><br>
                    Request ID: #{$request->id}</p>
                </div>
            </div>
        </body>
        </html>
        ";

        try {
            Mail::html($body, function ($message) use ($requester) {
                $message->to($requester->email)
                    ->subject('[M2B] Request User Anda Ditolak')
                    ->from('noreply@m2b.co.id', 'Portal M2B');
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send rejection notification: ' . $e->getMessage());
        }
    }


    public function deleteRequest($requestId)
    {
        // Check permission - only super_admin can delete
        $currentUser = Auth::user();
        if ($currentUser->role !== 'super_admin') {
            session()->flash('error', 'Hanya Super Admin yang dapat menghapus request.');
            return;
        }

        $request = UserRequest::find($requestId);
        if (!$request) {
            session()->flash('error', 'Request tidak ditemukan.');
            return;
        }

        DB::beginTransaction();
        try {
            // Jika sudah approved, hapus juga user yang dibuat
            if ($request->status === 'approved') {
                $createdUser = User::where('email', $request->pic_email)->first();
                if ($createdUser) {
                    // Hapus dari pivot table
                    DB::table('customer_user')->where('user_id', $createdUser->id)->delete();
                    // Hapus user
                    $createdUser->delete();
                }
            }

            // Hapus request
            $request->delete();

            DB::commit();
            session()->flash('message', '🗑️ Request dan data terkait berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }
    public function render()
    {
        $query = UserRequest::with(['customer', 'requestedBy', 'processedBy'])
            ->orderBy('created_at', 'desc');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('pic_name', 'like', "%{$this->search}%")
                  ->orWhere('pic_email', 'like', "%{$this->search}%")
                  ->orWhereHas('customer', function ($cq) {
                      $cq->where('company_name', 'like', "%{$this->search}%");
                  });
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $requests = $query->paginate(15);

        // Count by status
        $stats = [
            'total' => UserRequest::count(),
            'pending' => UserRequest::where('status', 'pending')->count(),
            'approved' => UserRequest::where('status', 'approved')->count(),
            'rejected' => UserRequest::where('status', 'rejected')->count(),
        ];

        // Check if current user can approve
        $currentUser = Auth::user();
        $canApprove = $currentUser->isDirectorLevel() || $currentUser->role === 'super_admin';

        return view('livewire.admin.user-request-manager', [
            'requests' => $requests,
            'stats' => $stats,
            'canApprove' => $canApprove,
        ])->layout('layouts.admin');
    }
}
