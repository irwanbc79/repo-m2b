<div class="p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">My Profile</h1>
        <div class="text-sm text-gray-500">
            <span class="uppercase">{{ now()->translatedFormat('l, d M') }}</span><br>
            <span class="text-2xl font-mono font-bold text-gray-700">{{ now()->format('H . i') }}</span>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg flex items-center gap-2">
            <span>✅</span> {{ session('message') }}
        </div>
    @endif

    @if (session()->has('request_message'))
        <div class="mb-4 p-4 bg-blue-100 border border-blue-300 text-blue-800 rounded-lg flex items-center gap-2">
            <span>📧</span> {{ session('request_message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content (Left) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Data Perusahaan --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        🏢 Data Perusahaan
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nama Perusahaan</label>
                            <p class="text-gray-800 font-semibold">{{ $company_name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">NPWP Perusahaan</label>
                            <p class="text-gray-800 font-mono">{{ $npwp ?? '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Alamat Lengkap (Kantor)</label>
                            <p class="text-gray-800">{{ $address ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Kota / Wilayah</label>
                            <p class="text-gray-800">{{ $city ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">No. Telepon / HP PIC</label>
                            <input type="text" wire:model="phone" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Alamat Gudang (Warehouse)</label>
                            <textarea wire:model="warehouse_address" rows="2" placeholder="Jika berbeda dengan alamat kantor" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Akun Login (PIC) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        👤 Akun Login (PIC)
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Nama Lengkap PIC</label>
                            <input type="text" wire:model="name" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Email Login</label>
                            <p class="text-gray-800 bg-gray-50 px-4 py-2 rounded-lg">{{ $email }}</p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button wire:click="updateProfile" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-lg transition flex items-center gap-2">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>

            {{-- Request User Section (NEW) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        👥 Kelola User Tambahan
                    </h2>
                </div>
                <div class="p-6">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                        <div>
                            <p class="text-gray-600">Tambahkan PIC baru untuk mengakses portal perusahaan Anda.</p>
                            <button wire:click="openTermsModal" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium underline mt-1">
                                📋 Lihat Syarat & Ketentuan
                            </button>
                        </div>
                        <button 
                            wire:click="openRequestUserModal"
                            class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold py-3 px-6 rounded-lg transition transform hover:-translate-y-0.5 shadow-md flex items-center gap-2 whitespace-nowrap"
                        >
                            <span class="text-xl">➕</span>
                            Request User Baru
                        </button>
                    </div>

                    {{-- Request History --}}
                    @if(count($userRequests) > 0)
                    <div class="border-t pt-6">
                        <h3 class="text-sm font-bold text-gray-600 uppercase tracking-wide mb-4">📜 Riwayat Request</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-gray-50">
                                        <th class="text-left py-3 px-4 font-bold text-gray-600 uppercase text-xs">Tanggal</th>
                                        <th class="text-left py-3 px-4 font-bold text-gray-600 uppercase text-xs">Nama PIC</th>
                                        <th class="text-left py-3 px-4 font-bold text-gray-600 uppercase text-xs">Email</th>
                                        <th class="text-left py-3 px-4 font-bold text-gray-600 uppercase text-xs">Level</th>
                                        <th class="text-left py-3 px-4 font-bold text-gray-600 uppercase text-xs">Status</th>
                                        <th class="text-center py-3 px-4 font-bold text-gray-600 uppercase text-xs">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($userRequests as $req)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-3 px-4 text-gray-600">{{ $req->created_at->format('d M Y') }}</td>
                                        <td class="py-3 px-4 font-semibold text-gray-800">{{ $req->pic_name }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $req->pic_email }}</td>
                                        <td class="py-3 px-4">{!! $req->access_level_label !!}</td>
                                        <td class="py-3 px-4">{!! $req->status_badge !!}</td>
                                        <td class="py-3 px-4 text-center">
                                            @if($req->status == 'pending')
                                            <button 
                                                wire:click="cancelRequest({{ $req->id }})"
                                                wire:confirm="Yakin ingin membatalkan request ini?"
                                                class="text-red-500 hover:text-red-700 text-xs font-bold"
                                            >
                                                🚫 Batalkan
                                            </button>
                                            @else
                                            <span class="text-gray-400 text-xs">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @else
                    <div class="border-t pt-6 text-center py-8">
                        <div class="text-4xl mb-3">📭</div>
                        <p class="text-gray-500">Belum ada riwayat request user</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar (Right) --}}
        <div class="space-y-6">
            {{-- Ganti Password --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        🔐 Ganti Password
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    @if (session()->has('password_message'))
                        <div class="p-3 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">
                            ✅ {{ session('password_message') }}
                        </div>
                    @endif
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Password Saat Ini</label>
                        <input type="password" wire:model="current_password" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        @error('current_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Password Baru</label>
                        <input type="password" wire:model="new_password" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        @error('new_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Ulangi Password Baru</label>
                        <input type="password" wire:model="new_password_confirmation" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <button wire:click="updatePassword" class="w-full border-2 border-orange-500 text-orange-500 hover:bg-orange-500 hover:text-white font-bold py-2.5 px-4 rounded-lg transition">
                        Update Password
                    </button>
                </div>
            </div>

            {{-- Customer Code --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Customer Code</label>
                </div>
                <div class="p-6">
                    <p class="text-3xl font-mono font-bold text-gray-800 tracking-wider">{{ $customer_code ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================= --}}
    {{-- REQUEST USER MODAL --}}
    {{-- ============================================= --}}
    @if($showRequestUserModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" wire:click.self="closeRequestUserModal">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            {{-- Modal Header --}}
            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4 rounded-t-2xl sticky top-0 z-10">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        👥 Request User Baru
                    </h3>
                    <button wire:click="closeRequestUserModal" class="text-white hover:text-gray-200 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Modal Body --}}
            <form wire:submit.prevent="submitUserRequest" class="p-6">
                {{-- Info Box --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex gap-3">
                        <span class="text-2xl">💡</span>
                        <div class="text-sm text-blue-800">
                            <p class="font-bold mb-1">Informasi Penting</p>
                            <p>Penambahan user baru dikenakan biaya sesuai ketentuan M2B. Tim sales akan menghubungi Anda untuk proses selanjutnya.</p>
                        </div>
                    </div>
                </div>

                {{-- Form Fields --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap PIC <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            wire:model="pic_name"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            placeholder="Masukkan nama lengkap"
                        >
                        @error('pic_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input 
                            type="email" 
                            wire:model="pic_email"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            placeholder="email@perusahaan.com"
                        >
                        @error('pic_email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">No. Telepon</label>
                            <input 
                                type="text" 
                                wire:model="pic_phone"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="08xx-xxxx-xxxx"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Jabatan</label>
                            <input 
                                type="text" 
                                wire:model="pic_position"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Manager, Staff, dll"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Level Akses <span class="text-red-500">*</span></label>
                        <select 
                            wire:model="access_level"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        >
                            <option value="view_only">👁️ View Only - Hanya melihat data</option>
                            <option value="full_access">🔓 Full Access - Dapat membuat & edit data</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Catatan Tambahan</label>
                        <textarea 
                            wire:model="request_notes"
                            rows="3"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            placeholder="Informasi tambahan (opsional)"
                        ></textarea>
                    </div>

                    {{-- Terms Checkbox --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input 
                                type="checkbox" 
                                wire:model="termsAccepted"
                                class="mt-1 w-5 h-5 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
                            >
                            <span class="text-sm text-gray-700">
                                Saya telah membaca dan menyetujui 
                                <button type="button" wire:click="openTermsModal" class="text-emerald-600 font-bold underline hover:text-emerald-800">Syarat & Ketentuan</button> 
                                penambahan user baru termasuk biaya yang dikenakan.
                            </span>
                        </label>
                        @error('termsAccepted') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex gap-3 mt-6">
                    <button 
                        type="button"
                        wire:click="closeRequestUserModal"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 px-4 rounded-lg transition"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit"
                        class="flex-1 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold py-3 px-4 rounded-lg transition flex items-center justify-center gap-2"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                    >
                        <span wire:loading.remove wire:target="submitUserRequest">📤 Kirim Request</span>
                        <span wire:loading wire:target="submitUserRequest">⏳ Mengirim...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ============================================= --}}
    {{-- TERMS & CONDITIONS MODAL --}}
    {{-- ============================================= --}}
    @if($showTermsModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" wire:click.self="closeTermsModal">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            {{-- Modal Header --}}
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4 rounded-t-2xl sticky top-0 z-10">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        📋 Syarat & Ketentuan Penambahan User
                    </h3>
                    <button wire:click="closeTermsModal" class="text-white hover:text-gray-200 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="p-6">
                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6">
                    <p class="font-bold text-amber-800 flex items-center gap-2">⚠️ Perhatian</p>
                    <p class="text-amber-700 text-sm">Penambahan user baru pada Portal M2B dikenakan biaya layanan sesuai ketentuan yang berlaku.</p>
                </div>

                <h4 class="text-lg font-bold text-gray-800 flex items-center gap-2 mt-6">💰 Mengapa Dikenakan Biaya?</h4>
                
                <div class="bg-gray-50 rounded-lg p-4 mt-3">
                    <h5 class="font-bold text-blue-800 mb-2">🖥️ Dari Sisi Teknis IT:</h5>
                    <ul class="text-sm text-gray-700 space-y-2">
                        <li>• <strong>Infrastruktur Server</strong> - Setiap user menambah beban query database, session storage, dan bandwidth server</li>
                        <li>• <strong>Keamanan & Audit</strong> - Setiap user memerlukan tracking aktivitas, log audit, dan monitoring keamanan 24/7</li>
                        <li>• <strong>Maintenance Sistem</strong> - Password reset, troubleshooting login, dan support teknis per user</li>
                        <li>• <strong>Backup & Recovery</strong> - Data user perlu di-backup harian dan disaster recovery plan</li>
                    </ul>
                </div>

                <div class="bg-gray-50 rounded-lg p-4 mt-4">
                    <h5 class="font-bold text-emerald-800 mb-2">🏢 Dari Sisi Bisnis:</h5>
                    <ul class="text-sm text-gray-700 space-y-2">
                        <li>• <strong>Dedicated IT Support</strong> - Tim IT M2B siap membantu setiap user yang mengalami kendala teknis</li>
                        <li>• <strong>SLA (Service Level Agreement)</strong> - Jaminan uptime 99.9% dan response time support maksimal 24 jam</li>
                        <li>• <strong>Training & Onboarding</strong> - Panduan penggunaan portal dan onboarding untuk user baru</li>
                        <li>• <strong>Customization</strong> - Hak akses dapat disesuaikan per user sesuai kebutuhan perusahaan</li>
                    </ul>
                </div>

                <h4 class="text-lg font-bold text-gray-800 flex items-center gap-2 mt-6">📊 Level Akses User</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-2xl">👁️</span>
                            <span class="font-bold text-gray-800">View Only</span>
                        </div>
                        <ul class="text-xs text-gray-600 space-y-1">
                            <li>✅ Melihat data shipment</li>
                            <li>✅ Melihat status invoice</li>
                            <li>✅ Download dokumen</li>
                            <li>❌ Tidak dapat membuat data baru</li>
                            <li>❌ Tidak dapat edit/hapus data</li>
                        </ul>
                    </div>
                    <div class="border border-emerald-200 bg-emerald-50 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-2xl">🔓</span>
                            <span class="font-bold text-emerald-800">Full Access</span>
                        </div>
                        <ul class="text-xs text-gray-600 space-y-1">
                            <li>✅ Semua fitur View Only</li>
                            <li>✅ Membuat shipment baru</li>
                            <li>✅ Upload dokumen</li>
                            <li>✅ Edit data shipment</li>
                            <li>✅ Konfirmasi pembayaran</li>
                        </ul>
                    </div>
                </div>

                <h4 class="text-lg font-bold text-gray-800 flex items-center gap-2 mt-6">📝 Prosedur Penambahan User</h4>
                <ol class="text-sm text-gray-700 space-y-2 mt-3 list-decimal list-inside">
                    <li>Isi form request dengan data PIC yang akan ditambahkan</li>
                    <li>Tim Sales M2B akan menghubungi Anda untuk konfirmasi</li>
                    <li>Invoice akan diterbitkan sesuai ketentuan yang berlaku</li>
                    <li>Setelah pembayaran dikonfirmasi, user baru akan diaktivasi dalam 1x24 jam kerja</li>
                    <li>Kredensial login akan dikirim ke email PIC yang didaftarkan</li>
                </ol>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                    <p class="text-sm text-blue-800">
                        <strong>📞 Butuh bantuan?</strong><br>
                        Hubungi tim kami di <a href="mailto:sales@m2b.co.id" class="font-bold underline">sales@m2b.co.id</a>
                    </p>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-4 bg-gray-50 rounded-b-2xl sticky bottom-0">
                <button 
                    wire:click="closeTermsModal"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition"
                >
                    Saya Mengerti
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
