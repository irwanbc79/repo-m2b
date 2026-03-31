<div>
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-100">💰 Periode Penggajian</h1>
            <p class="text-sm text-gray-400 mt-1">Kelola periode dan approval penggajian bulanan</p>
        </div>
        @if($this->canCreate())
        <button wire:click="openCreate"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
            + Buat Periode
        </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm text-gray-300">
            <thead>
                <tr class="bg-gray-700 text-gray-400 uppercase text-xs tracking-wider">
                    <th class="px-4 py-3 text-left">Periode</th>
                    <th class="px-4 py-3 text-center">Jml Slip</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-left">Disetujui Oleh</th>
                    <th class="px-4 py-3 text-left">Tgl Approve</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($periods as $period)
                <tr class="hover:bg-gray-750">
                    <td class="px-4 py-3 font-medium text-gray-200">
                        {{ $period->period_label }}
                    </td>
                    <td class="px-4 py-3 text-center">{{ $period->payroll_slips_count }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($period->status === 'draft')
                            <span class="px-2 py-0.5 bg-gray-600 text-gray-300 text-xs rounded-full">Draft</span>
                        @elseif($period->status === 'approved')
                            <span class="px-2 py-0.5 bg-green-900 text-green-300 text-xs rounded-full">Approved</span>
                        @else
                            <span class="px-2 py-0.5 bg-blue-900 text-blue-300 text-xs rounded-full">Paid</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $period->approvedBy?->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-400">
                        {{ $period->approved_at?->format('d M Y H:i') ?? '-' }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-1 flex-wrap">
                            {{-- View Slips --}}
                            <a href="{{ route('admin.hrd.payroll-slips', $period->id) }}"
                                class="px-2 py-1 bg-gray-600 hover:bg-gray-500 text-white text-xs rounded-lg transition-colors">
                                Lihat Slip
                            </a>

                            {{-- Approve: admin & director, only if draft --}}
                            @if($this->canApprove() && $period->status === 'draft')
                            <button wire:click="approve({{ $period->id }})"
                                wire:confirm="Approve periode {{ $period->period_label }}?"
                                class="px-2 py-1 bg-green-700 hover:bg-green-800 text-white text-xs rounded-lg transition-colors">
                                Approve
                            </button>
                            @endif

                            {{-- Mark Paid: admin & director, only if approved --}}
                            @if($this->canApprove() && $period->status === 'approved')
                            <button wire:click="markPaid({{ $period->id }})"
                                wire:confirm="Tandai sebagai Paid?"
                                class="px-2 py-1 bg-blue-700 hover:bg-blue-800 text-white text-xs rounded-lg transition-colors">
                                Tandai Paid
                            </button>
                            @endif

                            {{-- Export Excel --}}
                            @if(auth()->user()->hasRole(['admin', 'super_admin', 'finance', 'director']))
                            <a href="{{ route('admin.hrd.export-excel', $period->id) }}" target="_blank"
                                class="px-2 py-1 bg-emerald-700 hover:bg-emerald-800 text-white text-xs rounded-lg transition-colors">
                                Excel
                            </a>
                            @endif

                            {{-- Delete: admin only --}}
                            @if($this->canDelete())
                            <button wire:click="confirmDelete({{ $period->id }})"
                                class="px-2 py-1 bg-red-700 hover:bg-red-800 text-white text-xs rounded-lg transition-colors">
                                Hapus
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada periode penggajian.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-700">
            {{ $periods->links() }}
        </div>
    </div>

    {{-- Create Period Modal --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6">
            <h2 class="text-lg font-bold text-gray-100 mb-4">Buat Periode Penggajian</h2>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Bulan <span class="text-red-400">*</span></label>
                    <select wire:model="bulan"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none">
                        <option value="">-- Pilih Bulan --</option>
                        @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $bln)
                        <option value="{{ $i + 1 }}">{{ $bln }}</option>
                        @endforeach
                    </select>
                    @error('bulan') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Tahun <span class="text-red-400">*</span></label>
                    <input wire:model="tahun" type="number" min="2020" max="2099"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none">
                    @error('tahun') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg">Buat</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Delete Confirm --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
            <p class="text-gray-200 mb-2 font-medium">Hapus periode ini?</p>
            <p class="text-gray-400 text-sm mb-5">Semua slip gaji dalam periode ini akan ikut terhapus.</p>
            <div class="flex justify-center gap-3">
                <button wire:click="closeModal" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg">Batal</button>
                <button wire:click="delete" class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white text-sm rounded-lg">Ya, Hapus</button>
            </div>
        </div>
    </div>
    @endif
</div>
