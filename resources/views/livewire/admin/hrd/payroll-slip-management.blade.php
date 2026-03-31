<div>
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- Breadcrumb & Header --}}
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-4">
        <a href="{{ route('admin.hrd.payroll-periods') }}" class="hover:text-gray-200">Periode Penggajian</a>
        <span>/</span>
        <span class="text-gray-200">{{ $period->period_label }}</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-100">📋 Slip Gaji — {{ $period->period_label }}</h1>
            <div class="flex items-center gap-3 mt-1">
                @if($period->status === 'draft')
                    <span class="px-2 py-0.5 bg-gray-600 text-gray-300 text-xs rounded-full">Draft</span>
                @elseif($period->status === 'approved')
                    <span class="px-2 py-0.5 bg-green-900 text-green-300 text-xs rounded-full">Approved</span>
                @else
                    <span class="px-2 py-0.5 bg-blue-900 text-blue-300 text-xs rounded-full">Paid</span>
                @endif
                <span class="text-sm text-gray-400">Total: Rp {{ number_format($totalGajiAll, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="flex gap-2 flex-wrap justify-end">
            @if($this->canCreate())
            <button wire:click="$set('showBulkCreateConfirm', true)"
                class="px-3 py-2 bg-purple-700 hover:bg-purple-800 text-white text-sm rounded-lg transition-colors">
                ⚡ Bulk Create
            </button>
            <button wire:click="openCreate"
                class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                + Tambah Slip
            </button>
            @endif
            @if(auth()->user()->hasRole(['admin', 'super_admin', 'finance', 'director']))
            <a href="{{ route('admin.hrd.export-excel', $period->id) }}" target="_blank"
                class="px-3 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-sm rounded-lg transition-colors">
                📊 Export Excel
            </a>
            @endif
        </div>
    </div>

    {{-- Search --}}
    <div class="bg-gray-800 rounded-xl p-4 mb-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama / NIK karyawan..."
            class="px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm w-72 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    {{-- Table --}}
    <div class="bg-gray-800 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-300 whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-700 text-gray-400 uppercase text-xs tracking-wider">
                        <th class="px-3 py-3 text-left">No</th>
                        <th class="px-3 py-3 text-left">NIK</th>
                        <th class="px-3 py-3 text-left">Nama</th>
                        <th class="px-3 py-3 text-left">Jabatan</th>
                        <th class="px-3 py-3 text-right">Gaji Pokok</th>
                        <th class="px-3 py-3 text-right">T. Transport</th>
                        <th class="px-3 py-3 text-right">T. Jabatan</th>
                        <th class="px-3 py-3 text-right">T. Lainnya</th>
                        <th class="px-3 py-3 text-right">Lembur</th>
                        <th class="px-3 py-3 text-right">Pot. BPJS Kes</th>
                        <th class="px-3 py-3 text-right">Pot. BPJS TK</th>
                        <th class="px-3 py-3 text-right">Pot. Lainnya</th>
                        <th class="px-3 py-3 text-right font-bold text-gray-200">Total Gaji</th>
                        <th class="px-3 py-3 text-center">Potongan</th>
                        <th class="px-3 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($slips as $index => $slip)
                    <tr class="hover:bg-gray-750">
                        <td class="px-3 py-3 text-gray-500">{{ $slips->firstItem() + $index }}</td>
                        <td class="px-3 py-3 font-mono text-xs">{{ $slip->employee->nik }}</td>
                        <td class="px-3 py-3 font-medium text-gray-200">{{ $slip->employee->nama }}</td>
                        <td class="px-3 py-3">{{ $slip->employee->jabatan->nama_jabatan ?? '-' }}</td>
                        <td class="px-3 py-3 text-right">{{ number_format($slip->gaji_pokok, 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right">{{ number_format($slip->tunjangan_transport, 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right">{{ number_format($slip->tunjangan_jabatan, 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right">{{ number_format($slip->tunjangan_lainnya, 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right">{{ number_format($slip->lembur_nominal, 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right text-red-400">{{ number_format($slip->potongan_bpjs_kes, 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right text-red-400">{{ number_format($slip->potongan_bpjs_tk, 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right text-red-400">{{ number_format($slip->potongan_lainnya, 0, ',', '.') }}</td>
                        <td class="px-3 py-3 text-right font-bold text-green-300">Rp {{ number_format($slip->total_gaji, 0, ',', '.') }}</td>
                        {{-- Potongan Items --}}
                        <td class="px-3 py-3 text-center">
                            <button wire:click="openDeductionModal({{ $slip->id }})"
                                class="px-2 py-1 rounded text-xs transition-colors
                                    {{ $slip->deductionItems->count() > 0
                                        ? 'bg-orange-800 hover:bg-orange-700 text-orange-200'
                                        : 'bg-gray-700 hover:bg-gray-600 text-gray-400' }}">
                                {{ $slip->deductionItems->count() > 0
                                    ? $slip->deductionItems->count() . ' item'
                                    : '+ Tambah' }}
                            </button>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                {{-- PDF Slip --}}
                                @if(auth()->user()->hasRole(['admin', 'super_admin', 'finance', 'director']))
                                <a href="{{ route('admin.hrd.export-pdf-slip', $slip->id) }}" target="_blank"
                                    class="px-2 py-1 bg-red-800 hover:bg-red-900 text-white text-xs rounded transition-colors">PDF</a>
                                @endif
                                @if($this->canEdit())
                                <button wire:click="openEdit({{ $slip->id }})"
                                    class="px-2 py-1 bg-yellow-600 hover:bg-yellow-700 text-white text-xs rounded transition-colors">Edit</button>
                                @endif
                                @if($this->canDelete())
                                <button wire:click="confirmDelete({{ $slip->id }})"
                                    class="px-2 py-1 bg-red-700 hover:bg-red-800 text-white text-xs rounded transition-colors">Hapus</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="15" class="px-4 py-8 text-center text-gray-500">
                            Belum ada slip gaji. Gunakan tombol "Bulk Create" untuk membuat otomatis.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-700">
            {{ $slips->links() }}
        </div>
    </div>

    {{-- Create / Edit Modal --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 overflow-y-auto py-6">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl mx-4 p-6">
            <h2 class="text-lg font-bold text-gray-100 mb-4">
                {{ $isEditing ? 'Edit Slip Gaji' : 'Tambah Slip Gaji' }}
            </h2>
            <form wire:submit="save" class="space-y-4">
                {{-- Employee --}}
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Karyawan <span class="text-red-400">*</span></label>
                    <div class="flex gap-2">
                        <select wire:model.live="employee_id"
                            @if($isEditing) disabled @endif
                            class="flex-1 px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none">
                            <option value="">-- Pilih Karyawan --</option>
                            @foreach($availableEmployees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->nik }} - {{ $emp->nama }} ({{ $emp->jabatan->nama_jabatan ?? '-' }})</option>
                            @endforeach
                        </select>
                        @if(!$isEditing)
                        <button type="button" wire:click="fillFromJabatan"
                            class="px-3 py-2 bg-gray-600 hover:bg-gray-500 text-white text-xs rounded-lg transition-colors whitespace-nowrap">
                            Isi Default
                        </button>
                        @endif
                    </div>
                    @error('employee_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Pendapatan --}}
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider pt-2">Pendapatan</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach([['gaji_pokok','Gaji Pokok'],['tunjangan_transport','T. Transport'],['tunjangan_jabatan','T. Jabatan'],['tunjangan_lainnya','T. Lainnya'],['lembur_jam','Lembur (Jam)'],['lembur_nominal','Lembur (Rp)']] as [$field, $label])
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">{{ $label }}</label>
                        <input wire:model.live="{{$field}}" type="number" min="0" step="1000"
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                        @error($field) <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    @endforeach
                </div>

                {{-- Potongan --}}
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider pt-2">Potongan</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach([['potongan_bpjs_kes','BPJS Kesehatan'],['potongan_bpjs_tk','BPJS TK'],['potongan_lainnya','Potongan Lainnya']] as [$field, $label])
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">{{ $label }}</label>
                        <input wire:model.live="{{$field}}" type="number" min="0" step="1000"
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                        @error($field) <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    @endforeach
                </div>

                {{-- Total --}}
                <div class="bg-gray-700 rounded-lg p-3 flex items-center justify-between">
                    <span class="text-sm text-gray-400 font-medium">Total Gaji</span>
                    <span class="text-lg font-bold text-green-300">Rp {{ number_format($computed_total, 0, ',', '.') }}</span>
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Catatan</label>
                    <textarea wire:model="catatan" rows="2"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none resize-none"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-lg">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Delete Confirm --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
            <p class="text-gray-200 mb-5">Yakin ingin menghapus slip gaji ini?</p>
            <div class="flex justify-center gap-3">
                <button wire:click="closeModal" class="px-4 py-2 bg-gray-600 text-white text-sm rounded-lg">Batal</button>
                <button wire:click="delete" class="px-4 py-2 bg-red-700 text-white text-sm rounded-lg">Ya, Hapus</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Deduction Items Modal --}}
    @if($showDeductionModal && $deductionSlip)
    <div class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 overflow-y-auto py-6">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-xl mx-4 p-6 my-auto">

            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-100">✂️ Kelola Potongan</h2>
                    <p class="text-sm text-gray-400">{{ $deductionSlip->employee->nama }}</p>
                </div>
                <button wire:click="closeModal" class="text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
            </div>

            @if(session('deduction_success'))
            <div class="mb-3 p-2 bg-green-900/50 border border-green-700 text-green-300 rounded text-sm">
                {{ session('deduction_success') }}
            </div>
            @endif

            {{-- Existing items --}}
            <div class="mb-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs uppercase border-b border-gray-700">
                            <th class="pb-2 text-left">Nama Potongan</th>
                            <th class="pb-2 text-right">Nominal</th>
                            <th class="pb-2 text-left pl-3">Catatan</th>
                            @if($period->status === 'draft')
                            <th class="pb-2"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        {{-- Fixed: BPJS --}}
                        @if($deductionSlip->potongan_bpjs_kes > 0)
                        <tr>
                            <td class="py-2 text-gray-300">BPJS Kesehatan</td>
                            <td class="py-2 text-right text-red-400">Rp {{ number_format($deductionSlip->potongan_bpjs_kes, 0, ',', '.') }}</td>
                            <td class="py-2 pl-3 text-gray-500 text-xs">Fixed</td>
                            @if($period->status === 'draft')<td></td>@endif
                        </tr>
                        @endif
                        @if($deductionSlip->potongan_bpjs_tk > 0)
                        <tr>
                            <td class="py-2 text-gray-300">BPJS Ketenagakerjaan</td>
                            <td class="py-2 text-right text-red-400">Rp {{ number_format($deductionSlip->potongan_bpjs_tk, 0, ',', '.') }}</td>
                            <td class="py-2 pl-3 text-gray-500 text-xs">Fixed</td>
                            @if($period->status === 'draft')<td></td>@endif
                        </tr>
                        @endif
                        @if($deductionSlip->potongan_lainnya > 0)
                        <tr>
                            <td class="py-2 text-gray-300">Potongan Lainnya</td>
                            <td class="py-2 text-right text-red-400">Rp {{ number_format($deductionSlip->potongan_lainnya, 0, ',', '.') }}</td>
                            <td class="py-2 pl-3 text-gray-500 text-xs">Fixed</td>
                            @if($period->status === 'draft')<td></td>@endif
                        </tr>
                        @endif

                        {{-- Dynamic items --}}
                        @forelse($deductionItems as $item)
                        <tr>
                            <td class="py-2 text-gray-200 font-medium">{{ $item['nama_potongan'] }}</td>
                            <td class="py-2 text-right text-orange-400">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</td>
                            <td class="py-2 pl-3 text-gray-400 text-xs">{{ $item['catatan'] ?? '-' }}</td>
                            @if($period->status === 'draft')
                            <td class="py-2 text-right">
                                <button wire:click="removeDeductionItem({{ $item['id'] }})"
                                    wire:confirm="Hapus potongan '{{ $item['nama_potongan'] }}'?"
                                    class="text-red-500 hover:text-red-400 text-xs px-1">✕</button>
                            </td>
                            @endif
                        </tr>
                        @empty
                        @if($deductionSlip->potongan_bpjs_kes == 0 && $deductionSlip->potongan_bpjs_tk == 0 && $deductionSlip->potongan_lainnya == 0)
                        <tr>
                            <td colspan="4" class="py-4 text-center text-gray-500 text-sm">Belum ada potongan.</td>
                        </tr>
                        @endif
                        @endforelse

                        {{-- Total --}}
                        <tr class="border-t-2 border-gray-600">
                            <td class="pt-3 font-bold text-gray-200">Total Potongan</td>
                            <td class="pt-3 text-right font-bold text-red-300">
                                Rp {{ number_format(
                                    $deductionSlip->potongan_bpjs_kes
                                    + $deductionSlip->potongan_bpjs_tk
                                    + $deductionSlip->potongan_lainnya
                                    + collect($deductionItems)->sum('nominal'),
                                    0, ',', '.'
                                ) }}
                            </td>
                            <td colspan="{{ $period->status === 'draft' ? 2 : 1 }}"></td>
                        </tr>
                        <tr>
                            <td class="pb-1 text-gray-400 text-sm">Take Home Pay</td>
                            <td class="pb-1 text-right font-bold text-green-300">
                                Rp {{ number_format($deductionSlip->total_gaji, 0, ',', '.') }}
                            </td>
                            <td colspan="{{ $period->status === 'draft' ? 2 : 1 }}"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Add new item form (only if draft) --}}
            @if($period->status === 'draft')
            <div class="border-t border-gray-700 pt-4">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Tambah Potongan Baru</p>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Nama Potongan <span class="text-red-400">*</span></label>
                        <input wire:model="new_nama_potongan" type="text" placeholder="cth: Kasbon Nov, PPh 21..."
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        @error('new_nama_potongan') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Nominal (Rp) <span class="text-red-400">*</span></label>
                        <input wire:model="new_nominal" type="number" min="1" step="1000" placeholder="0"
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        @error('new_nominal') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label class="block text-xs text-gray-400 mb-1">Catatan (opsional)</label>
                    <input wire:model="new_catatan" type="text" placeholder="cth: Kasbon tgl 5 Nov..."
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-200 rounded-lg text-sm focus:outline-none">
                </div>
                <button wire:click="addDeductionItem"
                    class="w-full py-2 bg-orange-700 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">
                    + Tambah Potongan
                </button>
            </div>
            @else
            <p class="text-center text-xs text-gray-500 border-t border-gray-700 pt-3">
                Potongan tidak bisa diubah — periode sudah {{ $period->status }}.
            </p>
            @endif

        </div>
    </div>
    @endif

    {{-- Bulk Create Confirm --}}
    @if($showBulkCreateConfirm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
            <p class="text-gray-200 mb-2 font-medium">Bulk Create Slip Gaji</p>
            <p class="text-gray-400 text-sm mb-5">
                Sistem akan membuat slip gaji untuk semua karyawan aktif yang belum memiliki slip di periode ini.
                Nilai gaji akan diisi dari default jabatan.
            </p>
            <div class="flex justify-center gap-3">
                <button wire:click="closeModal" class="px-4 py-2 bg-gray-600 text-white text-sm rounded-lg">Batal</button>
                <button wire:click="bulkCreate" class="px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white text-sm rounded-lg">Buat Sekarang</button>
            </div>
        </div>
    </div>
    @endif
</div>
