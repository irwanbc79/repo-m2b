<div class="space-y-6">

    {{-- Header --}}
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">⭐ Moderasi Testimoni</h2>
                <p class="text-sm text-gray-500 mt-1">Tinjau dan setujui testimoni pelanggan</p>
            </div>
            <a href="{{ route('testimonial.index') }}" target="_blank"
               class="px-4 py-2 bg-blue-900 text-white text-sm rounded-lg hover:bg-blue-800 transition">
                🌐 Lihat Halaman Publik
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('message') }}
        </div>
    @endif

    {{-- Status Tabs --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            @foreach([
                'pending_filled' => ['label' => 'Menunggu Review', 'color' => 'yellow'],
                'pending_unfilled' => ['label' => 'Belum Mengisi', 'color' => 'gray'],
                'approved' => ['label' => 'Disetujui', 'color' => 'green'],
                'rejected' => ['label' => 'Ditolak', 'color' => 'red']
            ] as $status => $cfg)
            <button wire:click="$set('filterStatus', '{{ $status }}')"
                    class="px-6 py-4 text-sm font-medium border-b-2 transition whitespace-nowrap {{ $filterStatus === $status ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $cfg['label'] }}
                <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-{{ $cfg['color'] }}-100 text-{{ $cfg['color'] }}-700">
                    {{ $counts[$status] }}
                </span>
            </button>
            @endforeach
        </div>

        {{-- Table --}}
        <div class="divide-y divide-gray-100">
            @forelse($testimonials as $t)
            <div class="p-6">
                @if($editingId === $t->id)
                    {{-- Edit Form --}}
                    <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 space-y-4">
                        <h4 class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                            <span>✍️ Tulis/Edit Testimoni</span>
                            <span class="text-xs font-normal text-gray-500">({{ $t->customer?->company_name ?? 'Pelanggan M2B' }})</span>
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama PIC *</label>
                                <input type="text" wire:model="editDisplayName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                @error('editDisplayName') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Perusahaan</label>
                                <input type="text" wire:model="editCompanyName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                @error('editCompanyName') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Jabatan</label>
                                <input type="text" wire:model="editPosition" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                @error('editPosition') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Rating Bintang *</label>
                                <select wire:model="editRating" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                    <option value="5">⭐⭐⭐⭐⭐ (Sangat Puas)</option>
                                    <option value="4">⭐⭐⭐⭐ (Puas)</option>
                                    <option value="3">⭐⭐⭐ (Cukup)</option>
                                    <option value="2">⭐⭐ (Kurang Puas)</option>
                                    <option value="1">⭐ (Tidak Puas)</option>
                                </select>
                                @error('editRating') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Isi Testimoni * (Minimal 10 karakter)</label>
                            <textarea wire:model="editContent" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Tulis testimoni pelanggan yang diperoleh dari WhatsApp, telepon, atau lainnya..."></textarea>
                            @error('editContent') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button wire:click="saveEdit" class="px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white text-xs font-semibold rounded-lg transition">
                                💾 Simpan Testimoni
                            </button>
                            <button wire:click="cancelEdit" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg transition">
                                Batal
                            </button>
                        </div>
                    </div>
                @else
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            {{-- Rating --}}
                            <div class="flex gap-0.5 mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="{{ $i <= $t->rating ? 'text-yellow-400' : 'text-gray-200' }}">★</span>
                                @endfor
                            </div>
                            {{-- Konten --}}
                            <p class="text-gray-800 text-sm leading-relaxed mb-3 italic">"{{ $t->content ?: '(Belum diisi)' }}"</p>
                            {{-- Author --}}
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <span class="font-semibold text-gray-700">{{ $t->display_name ?: '(Nama belum diisi)' }}</span>
                                @if($t->position) <span>·</span> <span>{{ $t->position }}</span> @endif
                                @if($t->company_name) <span>·</span> <span>{{ $t->company_name }}</span> @endif
                            </div>
                            <div class="text-xs text-gray-400 mt-1">
                                {{ $t->created_at->format('d M Y H:i') }}
                                @if($t->customer?->user?->email)
                                    · {{ $t->customer->user->email }}
                                @endif
                            </div>

                            {{-- Admin Note untuk reject --}}
                            @if($activeId === $t->id)
                            <div class="mt-3">
                                <textarea wire:model="adminNote" rows="2" placeholder="Alasan penolakan (opsional)..."
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500"></textarea>
                            </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-col gap-2 flex-shrink-0 w-36">
                            @if($t->status === 'pending')
                                @if(empty($t->content))
                                    <button wire:click="startEdit({{ $t->id }})"
                                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition font-medium text-center">
                                        ✍️ Tulis Testimoni
                                    </button>
                                    @if($activeId !== $t->id)
                                        <button wire:click="$set('activeId', {{ $t->id }})"
                                                class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 text-sm rounded-lg transition font-medium border border-red-200 text-center">
                                            ❌ Tolak
                                        </button>
                                    @else
                                        <button wire:click="reject({{ $t->id }})"
                                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition font-medium text-center">
                                            Konfirmasi Tolak
                                        </button>
                                        <button wire:click="$set('activeId', null)"
                                                class="px-4 py-2 text-gray-500 text-sm rounded-lg hover:bg-gray-100 transition text-center">
                                            Batal
                                        </button>
                                    @endif
                                @else
                                    <button wire:click="approve({{ $t->id }})"
                                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition font-medium text-center">
                                        ✅ Setujui
                                    </button>
                                    <button wire:click="startEdit({{ $t->id }})"
                                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg transition font-medium border border-gray-200 text-center">
                                        ✏️ Edit
                                    </button>
                                    @if($activeId !== $t->id)
                                        <button wire:click="$set('activeId', {{ $t->id }})"
                                                class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 text-sm rounded-lg transition font-medium border border-red-200 text-center">
                                            ❌ Tolak
                                        </button>
                                    @else
                                        <button wire:click="reject({{ $t->id }})"
                                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition font-medium text-center">
                                            Konfirmasi Tolak
                                        </button>
                                        <button wire:click="$set('activeId', null)"
                                                class="px-4 py-2 text-gray-500 text-sm rounded-lg hover:bg-gray-100 transition text-center">
                                            Batal
                                        </button>
                                    @endif
                                @endif
                            @elseif($t->status === 'approved')
                                <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full text-center">✅ Approved</span>
                                <span class="text-xs text-gray-400 text-center">{{ $t->approved_at?->format('d M Y') }}</span>
                            @else
                                <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full text-center">❌ Rejected</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            @empty
            <div class="p-12 text-center text-gray-400">
                <div class="text-4xl mb-3">⭐</div>
                <p>Tidak ada testimoni dengan status ini.</p>
            </div>
            @endforelse
        </div>

        @if($testimonials->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{ $testimonials->links() }}
        </div>
        @endif
    </div>
</div>
