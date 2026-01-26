<div class="p-6">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            👥 User Request Management
        </h1>
        <p class="text-gray-500 text-sm mt-1">Kelola permintaan penambahan user dari customer</p>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">
            {!! session('message') !!}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold">Total Request</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                </div>
                <span class="text-3xl">📋</span>
            </div>
        </div>
        <div class="bg-yellow-50 rounded-xl shadow-sm border border-yellow-200 p-4 cursor-pointer hover:bg-yellow-100 transition" wire:click="$set('statusFilter', 'pending')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-yellow-600 uppercase font-bold">Pending</p>
                    <p class="text-2xl font-bold text-yellow-700">{{ $stats['pending'] }}</p>
                </div>
                <span class="text-3xl">⏳</span>
            </div>
        </div>
        <div class="bg-green-50 rounded-xl shadow-sm border border-green-200 p-4 cursor-pointer hover:bg-green-100 transition" wire:click="$set('statusFilter', 'approved')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-green-600 uppercase font-bold">Approved</p>
                    <p class="text-2xl font-bold text-green-700">{{ $stats['approved'] }}</p>
                </div>
                <span class="text-3xl">✅</span>
            </div>
        </div>
        <div class="bg-red-50 rounded-xl shadow-sm border border-red-200 p-4 cursor-pointer hover:bg-red-100 transition" wire:click="$set('statusFilter', 'rejected')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-red-600 uppercase font-bold">Rejected</p>
                    <p class="text-2xl font-bold text-red-700">{{ $stats['rejected'] }}</p>
                </div>
                <span class="text-3xl">❌</span>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm border p-4 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search"
                    placeholder="🔍 Cari nama, email, atau perusahaan..."
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                >
            </div>
            <div class="w-full md:w-48">
                <select wire:model.live="statusFilter" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    <option value="pending">⏳ Pending</option>
                    <option value="approved">✅ Approved</option>
                    <option value="rejected">❌ Rejected</option>
                    <option value="cancelled">🚫 Cancelled</option>
                </select>
            </div>
            @if($search || $statusFilter)
            <button wire:click="$set('search', ''); $set('statusFilter', '')" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                ✕ Reset
            </button>
            @endif
        </div>
    </div>

    {{-- Permission Notice --}}
    @if(!$canApprove)
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <p class="font-bold text-amber-800">Akses Terbatas</p>
                <p class="text-sm text-amber-700">Hanya <strong>Direktur</strong> dan <strong>Super Admin</strong> yang dapat menyetujui atau menolak request.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left py-3 px-4 font-bold text-gray-600 uppercase text-xs">ID</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-600 uppercase text-xs">Tanggal</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-600 uppercase text-xs">Customer</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-600 uppercase text-xs">PIC Baru</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-600 uppercase text-xs">Level</th>
                        <th class="text-left py-3 px-4 font-bold text-gray-600 uppercase text-xs">Status</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-600 uppercase text-xs">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    <tr class="border-b hover:bg-gray-50 {{ $req->status == 'pending' ? 'bg-yellow-50' : '' }}">
                        <td class="py-3 px-4 font-mono text-gray-500">#{{ $req->id }}</td>
                        <td class="py-3 px-4 text-gray-600">
                            {{ $req->created_at->format('d M Y') }}<br>
                            <span class="text-xs text-gray-400">{{ $req->created_at->format('H:i') }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <p class="font-semibold text-gray-800">{{ $req->customer->company_name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">oleh: {{ $req->requestedBy->name ?? 'N/A' }}</p>
                        </td>
                        <td class="py-3 px-4">
                            <p class="font-semibold text-gray-800">{{ $req->pic_name }}</p>
                            <p class="text-xs text-gray-500">{{ $req->pic_email }}</p>
                        </td>
                        <td class="py-3 px-4">
                            @if($req->access_level == 'full_access')
                                <span class="px-2 py-1 text-xs font-bold rounded-full bg-blue-100 text-blue-800">🔓 Full</span>
                            @else
                                <span class="px-2 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-700">👁️ View</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">{!! $req->status_badge !!}</td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button 
                                    wire:click="viewDetail({{ $req->id }})"
                                    class="text-blue-600 hover:text-blue-800 font-bold text-xs px-2 py-1 rounded hover:bg-blue-50"
                                >
                                    👁️ Detail
                                </button>
                                @if(auth()->user()->role === 'super_admin')
                                <button 
                                    wire:click="deleteRequest({{ $req->id }})"
                                    wire:confirm="PERINGATAN: Ini akan menghapus request dan user terkait (jika sudah dibuat). Lanjutkan?"
                                    class="text-red-600 hover:text-red-800 font-bold text-xs px-2 py-1 rounded hover:bg-red-50"
                                >
                                    🗑️ Delete
                                </button>
                                @endif
                                @if($req->status == 'pending' && $canApprove)
                                <button 
                                    wire:click="approveRequest({{ $req->id }})"
                                    wire:confirm="Yakin ingin APPROVE request ini? User baru akan langsung dibuat dan email kredensial akan dikirim."
                                    class="text-green-600 hover:text-green-800 font-bold text-xs px-2 py-1 rounded hover:bg-green-50"
                                >
                                    ✅ Approve
                                </button>
                                <button 
                                    wire:click="openRejectModal({{ $req->id }})"
                                    class="text-red-600 hover:text-red-800 font-bold text-xs px-2 py-1 rounded hover:bg-red-50"
                                >
                                    ❌ Reject
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-500">
                            <div class="text-4xl mb-3">📭</div>
                            <p>Tidak ada request ditemukan</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        @if($requests->hasPages())
        <div class="px-4 py-3 border-t bg-gray-50">
            {{ $requests->links() }}
        </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedRequest)
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" wire:click.self="closeDetailModal">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">📋 Detail Request #{{ $selectedRequest->id }}</h3>
                    <button wire:click="closeDetailModal" class="text-white hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6">
                {{-- Status Badge --}}
                <div class="text-center mb-6">
                    {!! $selectedRequest->status_badge !!}
                </div>

                {{-- Customer Info --}}
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <h4 class="font-bold text-gray-700 mb-3">🏢 Data Customer</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Perusahaan</p>
                            <p class="font-semibold">{{ $selectedRequest->customer->company_name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Customer Code</p>
                            <p class="font-mono">{{ $selectedRequest->customer->customer_code ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Diminta Oleh</p>
                            <p class="font-semibold">{{ $selectedRequest->requestedBy->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Email Requester</p>
                            <p>{{ $selectedRequest->requestedBy->email ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                {{-- PIC Baru Info --}}
                <div class="bg-emerald-50 rounded-lg p-4 mb-4">
                    <h4 class="font-bold text-emerald-800 mb-3">👤 Data PIC Baru</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Nama Lengkap</p>
                            <p class="font-semibold">{{ $selectedRequest->pic_name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Email</p>
                            <p>{{ $selectedRequest->pic_email }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">No. Telepon</p>
                            <p>{{ $selectedRequest->pic_phone ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Jabatan</p>
                            <p>{{ $selectedRequest->pic_position ?: '-' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-gray-500">Level Akses</p>
                            <p class="font-semibold">
                                @if($selectedRequest->access_level == 'full_access')
                                    🔓 Full Access - Dapat membuat & edit data
                                @else
                                    👁️ View Only - Hanya melihat data
                                @endif
                            </p>
                        </div>
                        @if($selectedRequest->notes)
                        <div class="col-span-2">
                            <p class="text-gray-500">Catatan</p>
                            <p class="bg-white p-2 rounded border">{{ $selectedRequest->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Processing Info (if processed) --}}
                @if($selectedRequest->processed_at)
                <div class="bg-blue-50 rounded-lg p-4 mb-4">
                    <h4 class="font-bold text-blue-800 mb-3">📝 Info Pemrosesan</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Diproses Oleh</p>
                            <p class="font-semibold">{{ $selectedRequest->processedBy->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Tanggal Proses</p>
                            <p>{{ $selectedRequest->processed_at->format('d M Y H:i') }}</p>
                        </div>
                        @if($selectedRequest->admin_notes)
                        <div class="col-span-2">
                            <p class="text-gray-500">Catatan Admin</p>
                            <p class="bg-white p-2 rounded border">{{ $selectedRequest->admin_notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Action Buttons --}}
                @if($selectedRequest->status == 'pending' && $canApprove)
                <div class="flex gap-3 mt-6">
                    <button 
                        wire:click="approveRequest({{ $selectedRequest->id }})"
                        wire:confirm="Yakin ingin APPROVE? User baru akan dibuat dan email kredensial akan dikirim."
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition flex items-center justify-center gap-2"
                    >
                        ✅ Approve & Buat User
                    </button>
                    <button 
                        wire:click="openRejectModal({{ $selectedRequest->id }})"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition flex items-center justify-center gap-2"
                    >
                        ❌ Reject
                    </button>
                </div>
                @else
                <div class="mt-6">
                    <button 
                        wire:click="closeDetailModal"
                        class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 px-4 rounded-lg transition"
                    >
                        Tutup
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Reject Modal --}}
    @if($showRejectModal && $selectedRequest)
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" wire:click.self="closeRejectModal">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-red-600 to-red-800 px-6 py-4 rounded-t-2xl">
                <h3 class="text-xl font-bold text-white">❌ Tolak Request</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">
                    Anda akan menolak request dari <strong>{{ $selectedRequest->pic_name }}</strong> 
                    untuk <strong>{{ $selectedRequest->customer->company_name ?? 'N/A' }}</strong>.
                </p>
                
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Alasan Penolakan <span class="text-red-500">*</span></label>
                    <textarea 
                        wire:model="rejectReason"
                        rows="4"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500"
                        placeholder="Jelaskan alasan penolakan (min. 10 karakter)"
                    ></textarea>
                    @error('rejectReason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-3">
                    <button 
                        wire:click="closeRejectModal"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 px-4 rounded-lg transition"
                    >
                        Batal
                    </button>
                    <button 
                        wire:click="rejectRequest"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition"
                    >
                        Konfirmasi Tolak
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
