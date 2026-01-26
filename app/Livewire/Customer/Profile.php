<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
use App\Models\UserRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class Profile extends Component
{
    // Existing properties
    public $name;
    public $email;

    // Customer properties
    public $company_name;
    public $customer_code;
    public $npwp;
    public $phone;
    public $address;
    public $city;
    public $warehouse_address;

    // Password properties
    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    // === NEW: Request User properties ===
    public $showRequestUserModal = false;
    public $showTermsModal = false;
    public $termsAccepted = false;
    
    // Request form fields
    public $pic_name = '';
    public $pic_email = '';
    public $pic_phone = '';
    public $pic_position = '';
    public $access_level = 'view_only';
    public $request_notes = '';
    
    // Request history
    public $userRequests = [];
    public $customer;

    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;

        $customer = Customer::where('user_id', $user->id)->first();
        $this->customer = $customer;
        
        if ($customer) {
            $this->company_name = $customer->company_name;
            $this->customer_code = $customer->customer_code;
            $this->npwp = $customer->npwp;
            $this->phone = $customer->phone;
            $this->address = $customer->address;
            $this->city = $customer->city;
            $this->warehouse_address = $customer->warehouse_address;
        }

        $this->loadUserRequests();
    }

    // === EXISTING FUNCTIONS ===

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|min:3',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'warehouse_address' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $user->name = $this->name;
        $user->save();

        $customer = Customer::where('user_id', $user->id)->first();
        if ($customer) {
            $customer->phone = $this->phone;
            $customer->address = $this->address;
            $customer->city = $this->city;
            $customer->warehouse_address = $this->warehouse_address;
            $customer->save();
        }

        session()->flash('message', 'Profile berhasil diperbarui!');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Password saat ini salah');
            return;
        }

        $user->password = Hash::make($this->new_password);
        $user->save();

        $this->current_password = '';
        $this->new_password = '';
        $this->new_password_confirmation = '';

        session()->flash('password_message', 'Password berhasil diperbarui!');
    }

    // === NEW: Request User Functions ===

    public function loadUserRequests()
    {
        if ($this->customer) {
            $this->userRequests = UserRequest::where('customer_id', $this->customer->id)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        }
    }

    public function openRequestUserModal()
    {
        $this->resetRequestForm();
        $this->showRequestUserModal = true;
    }

    public function closeRequestUserModal()
    {
        $this->showRequestUserModal = false;
        $this->resetRequestForm();
    }

    public function openTermsModal()
    {
        $this->showTermsModal = true;
    }

    public function closeTermsModal()
    {
        $this->showTermsModal = false;
    }

    public function resetRequestForm()
    {
        $this->pic_name = '';
        $this->pic_email = '';
        $this->pic_phone = '';
        $this->pic_position = '';
        $this->access_level = 'view_only';
        $this->request_notes = '';
        $this->termsAccepted = false;
        $this->resetValidation(['pic_name', 'pic_email', 'pic_phone', 'pic_position', 'access_level', 'request_notes', 'termsAccepted']);
    }

    public function submitUserRequest()
    {
        $this->validate([
            'pic_name' => 'required|string|min:3|max:100',
            'pic_email' => 'required|email|max:100',
            'pic_phone' => 'nullable|string|max:20',
            'pic_position' => 'nullable|string|max:100',
            'access_level' => 'required|in:view_only,full_access',
            'request_notes' => 'nullable|string|max:500',
            'termsAccepted' => 'accepted',
        ], [
            'pic_name.required' => 'Nama PIC wajib diisi',
            'pic_name.min' => 'Nama PIC minimal 3 karakter',
            'pic_email.required' => 'Email PIC wajib diisi',
            'pic_email.email' => 'Format email tidak valid',
            'termsAccepted.accepted' => 'Anda harus menyetujui syarat & ketentuan',
        ]);

        // Check if email already requested (pending)
        $existingRequest = UserRequest::where('customer_id', $this->customer->id)
            ->where('pic_email', $this->pic_email)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            $this->addError('pic_email', 'Email ini sudah memiliki request yang sedang diproses');
            return;
        }

        // Create request
        $userRequest = UserRequest::create([
            'customer_id' => $this->customer->id,
            'requested_by' => Auth::id(),
            'pic_name' => $this->pic_name,
            'pic_email' => $this->pic_email,
            'pic_phone' => $this->pic_phone,
            'pic_position' => $this->pic_position,
            'access_level' => $this->access_level,
            'notes' => $this->request_notes,
            'status' => 'pending',
        ]);

        // Send email to sales@m2b.co.id
        $this->sendEmailToSales($userRequest);

        // Send confirmation email to customer (requester)
        $this->sendConfirmationToCustomer($userRequest);

        $this->closeRequestUserModal();
        $this->loadUserRequests();
        
        session()->flash('request_message', 'Request user baru berhasil dikirim! Cek email Anda untuk konfirmasi. Tim sales kami akan menghubungi Anda.');
    }

    protected function sendEmailToSales($userRequest)
    {
        $to = 'sales@m2b.co.id';
        $subject = "[REQUEST USER BARU] {$this->company_name} - {$userRequest->pic_name}";
        
        $body = $this->getSalesEmailTemplate($userRequest);

        try {
            Mail::html($body, function ($message) use ($to, $subject) {
                $message->to($to)
                    ->subject($subject)
                    ->from(config('mail.from.address', 'noreply@m2b.co.id'), config('mail.from.name', 'Portal M2B'));
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send user request email to sales: ' . $e->getMessage());
        }
    }

    protected function sendConfirmationToCustomer($userRequest)
    {
        $to = Auth::user()->email;
        $subject = "[M2B] Konfirmasi Request User Baru - {$userRequest->pic_name}";
        
        $body = $this->getCustomerConfirmationTemplate($userRequest);

        try {
            Mail::html($body, function ($message) use ($to, $subject) {
                $message->to($to)
                    ->subject($subject)
                    ->from('noreply@m2b.co.id', 'Portal M2B');
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send confirmation email to customer: ' . $e->getMessage());
        }
    }

    protected function getSalesEmailTemplate($userRequest)
    {
        $accessLabel = $userRequest->access_level == 'full_access' ? '🔓 Full Access' : '👁️ View Only';
        $requestDate = now()->format('d M Y H:i');
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); color: white; padding: 25px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 25px; background: #f9fafb; }
                .info-table { width: 100%; border-collapse: collapse; margin: 15px 0; background: white; border-radius: 8px; overflow: hidden; }
                .info-table td { padding: 12px 15px; border-bottom: 1px solid #e5e7eb; }
                .info-table td:first-child { font-weight: bold; width: 40%; background: #f3f4f6; color: #374151; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #6b7280; background: #f3f4f6; border-radius: 0 0 10px 10px; }
                .badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
                .badge-info { background: #dbeafe; color: #1d4ed8; }
                .action-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='margin:0;'>📧 Request Penambahan User Baru</h2>
                    <p style='margin:10px 0 0 0; opacity:0.9;'>Portal M2B - Customer Management</p>
                </div>
                <div class='content'>
                    <p>Halo Tim Sales M2B,</p>
                    <p>Ada permintaan penambahan user baru dari customer berikut:</p>
                    
                    <h3 style='color:#1e3a5f; border-bottom:2px solid #1e3a5f; padding-bottom:8px;'>📋 Data Customer</h3>
                    <table class='info-table'>
                        <tr><td>Perusahaan</td><td><strong>{$this->company_name}</strong></td></tr>
                        <tr><td>Customer Code</td><td>{$this->customer_code}</td></tr>
                        <tr><td>NPWP</td><td>{$this->npwp}</td></tr>
                        <tr><td>Diminta oleh</td><td>{$this->name} ({$this->email})</td></tr>
                    </table>
                    
                    <h3 style='color:#059669; border-bottom:2px solid #059669; padding-bottom:8px;'>👤 Data PIC Baru yang Diminta</h3>
                    <table class='info-table'>
                        <tr><td>Nama Lengkap</td><td><strong>{$userRequest->pic_name}</strong></td></tr>
                        <tr><td>Email</td><td>{$userRequest->pic_email}</td></tr>
                        <tr><td>No. Telepon</td><td>" . ($userRequest->pic_phone ?: '-') . "</td></tr>
                        <tr><td>Jabatan</td><td>" . ($userRequest->pic_position ?: '-') . "</td></tr>
                        <tr><td>Level Akses</td><td><span class='badge badge-info'>{$accessLabel}</span></td></tr>
                        <tr><td>Catatan</td><td>" . ($userRequest->notes ?: '-') . "</td></tr>
                    </table>
                    
                    <div class='action-box'>
                        <strong>⚡ Action Required:</strong><br>
                        Silakan follow-up customer untuk proses invoice dan aktivasi user baru.
                    </div>
                </div>
                <div class='footer'>
                    <p>Email ini dikirim otomatis dari Portal M2B<br>
                    <strong>Request ID: #{$userRequest->id}</strong> | Tanggal: {$requestDate} WIB</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    protected function getCustomerConfirmationTemplate($userRequest)
    {
        $accessLabel = $userRequest->access_level == 'full_access' ? '🔓 Full Access' : '👁️ View Only';
        $requestDate = now()->format('d M Y H:i');
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; padding: 25px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 25px; background: #f9fafb; }
                .info-table { width: 100%; border-collapse: collapse; margin: 15px 0; background: white; border-radius: 8px; overflow: hidden; }
                .info-table td { padding: 12px 15px; border-bottom: 1px solid #e5e7eb; }
                .info-table td:first-child { font-weight: bold; width: 40%; background: #f3f4f6; color: #374151; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #6b7280; background: #f3f4f6; border-radius: 0 0 10px 10px; }
                .badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
                .badge-success { background: #d1fae5; color: #065f46; }
                .badge-info { background: #dbeafe; color: #1d4ed8; }
                .terms-box { background: #eff6ff; border: 1px solid #bfdbfe; padding: 20px; margin: 20px 0; border-radius: 8px; }
                .terms-box h4 { color: #1d4ed8; margin-top: 0; }
                .terms-box ul { margin: 10px 0; padding-left: 20px; }
                .terms-box li { margin: 8px 0; color: #374151; }
                .next-steps { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0; }
                .cost-box { background: #fef2f2; border: 1px solid #fecaca; padding: 15px; margin: 15px 0; border-radius: 8px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='margin:0;'>✅ Konfirmasi Request User Baru</h2>
                    <p style='margin:10px 0 0 0; opacity:0.9;'>Portal M2B - {$this->company_name}</p>
                </div>
                <div class='content'>
                    <p>Yth. <strong>{$this->name}</strong>,</p>
                    <p>Terima kasih telah menggunakan Portal M2B. Kami konfirmasi bahwa request penambahan user baru untuk perusahaan Anda telah kami terima.</p>
                    
                    <h3 style='color:#059669; border-bottom:2px solid #059669; padding-bottom:8px;'>👤 Detail Request</h3>
                    <table class='info-table'>
                        <tr><td>Request ID</td><td><strong>#{$userRequest->id}</strong></td></tr>
                        <tr><td>Tanggal Request</td><td>{$requestDate} WIB</td></tr>
                        <tr><td>Nama PIC Baru</td><td><strong>{$userRequest->pic_name}</strong></td></tr>
                        <tr><td>Email PIC</td><td>{$userRequest->pic_email}</td></tr>
                        <tr><td>Level Akses</td><td><span class='badge badge-info'>{$accessLabel}</span></td></tr>
                        <tr><td>Status</td><td><span class='badge badge-success'>⏳ Menunggu Proses</span></td></tr>
                    </table>

                    <div class='terms-box'>
                        <h4>📋 Syarat & Ketentuan Penambahan User</h4>
                        <p><strong>Penambahan user baru pada Portal M2B dikenakan biaya layanan</strong> dengan pertimbangan sebagai berikut:</p>
                        
                        <p style='margin-top:15px;'><strong>🖥️ Dari Sisi Teknis IT:</strong></p>
                        <ul>
                            <li><strong>Infrastruktur Server</strong> - Setiap user menambah beban database, session storage, dan bandwidth</li>
                            <li><strong>Keamanan & Audit</strong> - Tracking aktivitas, log audit, dan monitoring keamanan 24/7</li>
                            <li><strong>Maintenance Sistem</strong> - Password reset, troubleshooting, dan support teknis</li>
                            <li><strong>Backup & Recovery</strong> - Data user di-backup harian dengan disaster recovery plan</li>
                        </ul>
                        
                        <p style='margin-top:15px;'><strong>🏢 Dari Sisi Bisnis:</strong></p>
                        <ul>
                            <li><strong>Dedicated IT Support</strong> - Tim IT M2B siap membantu kendala teknis</li>
                            <li><strong>SLA</strong> - Jaminan uptime 99.9% dan response time support maksimal 24 jam</li>
                            <li><strong>Training & Onboarding</strong> - Panduan penggunaan portal untuk user baru</li>
                            <li><strong>Customization</strong> - Hak akses dapat disesuaikan sesuai kebutuhan</li>
                        </ul>
                    </div>

                    <div class='cost-box'>
                        <strong>💰 Informasi Biaya:</strong><br>
                        <p style='margin:10px 0 0 0;'>Biaya penambahan user akan diinformasikan oleh tim sales kami. Invoice akan diterbitkan setelah konfirmasi dari Anda.</p>
                    </div>

                    <div class='next-steps'>
                        <strong>📌 Langkah Selanjutnya:</strong>
                        <ol style='margin:10px 0 0 0; padding-left:20px;'>
                            <li>Tim Sales M2B akan menghubungi Anda dalam 1x24 jam kerja</li>
                            <li>Konfirmasi data dan biaya layanan</li>
                            <li>Invoice diterbitkan setelah konfirmasi</li>
                            <li>User baru diaktivasi setelah pembayaran dikonfirmasi</li>
                            <li>Kredensial login dikirim ke email PIC yang didaftarkan</li>
                        </ol>
                    </div>

                    <p style='margin-top:25px;'>Jika ada pertanyaan, silakan hubungi kami:</p>
                    <p>
                        📧 Email: <a href='mailto:sales@m2b.co.id' style='color:#1d4ed8;'>sales@m2b.co.id</a><br>
                        📱 WhatsApp: <a href='https://wa.me/628xxxxxxxxxx' style='color:#1d4ed8;'>+62 8xx-xxxx-xxxx</a>
                    </p>
                </div>
                <div class='footer'>
                    <p><strong>M2B Logistic Solution</strong><br>
                    Email ini dikirim otomatis, mohon tidak membalas email ini.<br>
                    © " . date('Y') . " M2B. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    public function cancelRequest($requestId)
    {
        $request = UserRequest::where('id', $requestId)
            ->where('customer_id', $this->customer->id)
            ->where('status', 'pending')
            ->first();

        if ($request) {
            $request->update(['status' => 'cancelled']);
            $this->loadUserRequests();
            session()->flash('request_message', 'Request berhasil dibatalkan');
        }
    }

    public function render()
    {
        return view('livewire.customer.profile')
            ->layout('layouts.customer');
    }
}
