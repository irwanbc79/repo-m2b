<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mt-6">
    {{-- Header Panel --}}
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-xl font-bold">
                🛡️
            </div>
            <div>
                <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                    Pra-Audit Kepatuhan Berkas
                    <span class="text-[11px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                        Pre-Clearance Diligence
                    </span>
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Uji silang konsistensi B/L, Invoice, Packing List &amp; validasi formil SKA/Form E oleh <strong>Tim M2B</strong>.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($audit)
                @php
                    $effStatus = $audit->effective_status;
                @endphp
                @if($effStatus === 'COMPLIANT')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Siap Aju (Compliant)
                    </span>
                @elseif($effStatus === 'ATTENTION')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> Perlu Konfirmasi (Attention)
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span> Cacat Formil / Kritis
                    </span>
                @endif
            @endif

            @if($isConfigured)
                <button
                    wire:click="runAudit"
                    wire:loading.attr="disabled"
                    wire:target="runAudit"
                    class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="runAudit">
                        {{ $audit ? '↻ Audit Ulang Berkas' : '✨ Jalankan Pra-Audit Dokumen' }}
                    </span>
                    <span wire:loading wire:target="runAudit">
                        Sedang Menganalisis…
                    </span>
                </button>
            @else
                <span class="text-xs text-amber-700 bg-amber-50 px-2.5 py-1.5 rounded-lg border border-amber-200 font-medium">
                    ⚠️ API Key AI belum terkonfigurasi di server
                </span>
            @endif
        </div>
    </div>

    {{-- Notifikasi Session --}}
    @if(session()->has('compliance_message'))
        <div class="mt-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-3.5 py-2.5 rounded-lg flex items-center gap-2">
            <span>✓</span> {{ session('compliance_message') }}
        </div>
    @endif

    @if(session()->has('compliance_error'))
        <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs px-3.5 py-2.5 rounded-lg flex items-center gap-2">
            <span>⚠️</span> {{ session('compliance_error') }}
        </div>
    @endif

    {{-- Konten Utama Hasil Audit --}}
    @if($audit)
        <div class="mt-5 space-y-4">
            {{-- Executive Summary Card --}}
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="space-y-1 max-w-2xl">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Ringkasan Eksekutif</p>
                        <p class="text-sm font-medium text-slate-800 leading-relaxed">{{ $audit->summary }}</p>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Terakhir diuji pada: <strong>{{ $audit->created_at->format('d M Y, H:i') }} WIB</strong> oleh {{ $audit->auditor?->name ?: 'Sistem' }}
                            &bull; Basis Mesin: <span class="capitalize">{{ $audit->provider }} ({{ $audit->model }})</span>
                        </p>
                    </div>

                    {{-- Skor Kepatuhan --}}
                    <div class="text-right bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-xs shrink-0">
                        <p class="text-[11px] font-bold text-slate-500 uppercase">Skor Kesiapan</p>
                        <p class="text-2xl font-black {{ $audit->compliance_score >= 90 ? 'text-emerald-600' : ($audit->compliance_score >= 75 ? 'text-amber-600' : 'text-rose-600') }}">
                            {{ $audit->compliance_score }}<span class="text-xs font-bold text-slate-400">/100</span>
                        </p>
                    </div>
                </div>

                {{-- Snapshot Berkas yang Dianalisis --}}
                @if(!empty($audit->document_snapshots))
                    <div class="mt-3 pt-3 border-t border-slate-200 flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold text-slate-500">Berkas Teruji ({{ count($audit->document_snapshots) }}):</span>
                        @foreach($audit->document_snapshots as $docSnap)
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium bg-white text-slate-700 px-2 py-0.5 rounded-md border border-slate-200">
                                📄 {{ $docSnap['label'] }}: <span class="text-slate-500 truncate max-w-[140px]">{{ $docSnap['filename'] }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Daftar Temuan & Rekonsiliasi Dokumen --}}
            @php
                $findings = $audit->findings ?: [];
            @endphp

            @if(empty($findings))
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center">
                    <p class="text-emerald-800 font-bold text-sm">🎉 Tidak Ditemukan Ketidaksesuaian Formil</p>
                    <p class="text-emerald-700 text-xs mt-1">Seluruh data B/L, Invoice, dan Packing List dinyatakan selaras. Berkas siap diajukan ke proses kepabeanan.</p>
                </div>
            @else
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center justify-between">
                        <span>Daftar Catatan &amp; Uji Silang Formil ({{ count($findings) }})</span>
                        <span class="text-[11px] font-normal text-slate-500">Staf dapat mengonfirmasi temuan tanpa proses ulang AI</span>
                    </h4>

                    @foreach($findings as $idx => $f)
                        @php
                            $isResolved = !empty($f['resolved_at']) || ($f['status'] ?? '') === 'RESOLVED';
                            $severity = strtoupper($f['severity'] ?? 'LOW');
                        @endphp
                        <div class="rounded-xl border p-4 transition {{ $isResolved ? 'bg-emerald-50/40 border-emerald-200' : ($severity === 'HIGH' || $severity === 'CRITICAL' ? 'bg-rose-50/30 border-rose-200' : 'bg-white border-slate-200') }}">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        {{-- Badge Dokumen --}}
                                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-800">
                                            {{ $f['document'] ?? 'Dokumen' }}
                                        </span>

                                        {{-- Badge Tingkat Kepentingan --}}
                                        @if($severity === 'HIGH' || $severity === 'CRITICAL')
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-rose-100 text-rose-800">
                                                Tingkat Kritis
                                            </span>
                                        @elseif($severity === 'MEDIUM')
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-100 text-amber-800">
                                                Perhatian
                                            </span>
                                        @else
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">
                                                Catatan
                                            </span>
                                        @endif

                                        <h5 class="text-sm font-bold text-slate-900">{{ $f['title'] ?? 'Catatan Kepatuhan' }}</h5>
                                    </div>

                                    <p class="text-xs text-slate-700 mt-1 leading-relaxed">{{ $f['description'] ?? '' }}</p>

                                    @if(!empty($f['recommendation']))
                                        <p class="text-xs text-blue-700 bg-blue-50/70 rounded-md p-2 mt-2">
                                            💡 <strong>Rekomendasi Tindakan:</strong> {{ $f['recommendation'] }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Status Resolusi & Tombol Aksi Staf --}}
                                <div>
                                    @if($isResolved)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-md bg-emerald-100 text-emerald-800">
                                            ✓ Telah Dikonfirmasi Tim M2B
                                        </span>
                                    @else
                                        <button
                                            wire:click="openResolveModal({{ $idx }})"
                                            class="text-xs font-bold px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 transition"
                                        >
                                            ✏️ Tandai Selesai / Beri Keterangan
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Catatan Klarifikasi Staf yang Sudah Disimpan --}}
                            @if($isResolved && !empty($f['resolution_note']))
                                <div class="mt-2.5 pt-2.5 border-t border-emerald-200 text-xs text-emerald-900 bg-white/80 rounded-lg p-2.5">
                                    <div class="font-bold flex items-center justify-between text-[11px] text-emerald-700 mb-1">
                                        <span>Catatan Klarifikasi Tim M2B:</span>
                                        <span>{{ $f['resolved_by_name'] ?? 'Staf' }} &bull; {{ $f['resolved_at'] ? \Carbon\Carbon::parse($f['resolved_at'])->format('d/m/Y H:i') : '' }}</span>
                                    </div>
                                    <p class="italic text-slate-800">"{{ $f['resolution_note'] }}"</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Tombol Aksi Cepat Komunikasi Klien --}}
            <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-200">
                <div class="flex items-center gap-2">
                    <button
                        wire:click="prepareWhatsApp"
                        class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-xs"
                    >
                        <span>💬</span> Kirim Catatan via WhatsApp
                    </button>

                    <button
                        wire:click="prepareEmail"
                        class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-2 rounded-lg bg-slate-700 text-white hover:bg-slate-800 transition shadow-xs"
                    >
                        <span>✉️</span> Siapkan Draf Email
                    </button>
                </div>

                <div class="text-[11px] text-slate-500 italic">
                    Diperiksa &amp; diverifikasi oleh <strong>Tim M2B</strong>
                </div>
            </div>

            {{-- Kotak Disclaimer Hukum Baku --}}
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-[11px] text-gray-500 leading-relaxed">
                <strong>Disclaimer:</strong> {{ $audit->disclaimer }}
            </div>
        </div>
    @else
        {{-- Kondisi Belum Diaudit --}}
        <div class="py-8 text-center text-slate-500">
            <p class="text-2xl mb-1">📋</p>
            <p class="text-sm font-bold text-slate-700">Dokumen Belum Dilakukan Pra-Audit</p>
            <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
                Lakukan uji kepatuhan otomatis untuk memvalidasi keselarasan B/L, Invoice, Packing List, serta pencegahan cacat formil Form E/SKA sebelum diajukan ke pabean.
            </p>
            @if($isConfigured)
                <button
                    wire:click="runAudit"
                    wire:loading.attr="disabled"
                    class="mt-4 inline-flex items-center gap-2 text-xs font-bold px-4 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm"
                >
                    <span wire:loading.remove wire:target="runAudit">✨ Jalankan Pra-Audit Dokumen Sekarang</span>
                    <span wire:loading wire:target="runAudit">Memproses Berkas…</span>
                </button>
            @endif
        </div>
    @endif

    {{-- MODAL RESOLUSI TEMUAN MANUAL --}}
    @if($showResolveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 border border-gray-100" style="position: relative; z-index: 10;" @click.outside="$wire.set('showResolveModal', false)">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                        <span>✏️</span> Konfirmasi &amp; Selesaikan Temuan
                    </h4>
                    <button wire:click="$set('showResolveModal', false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="mt-4 space-y-3">
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Jika temuan telah diklarifikasi ke pihak pelayaran, importir, atau supplier, tuliskan catatan penyelesaian di bawah. Status akan otomatis berubah menjadi <strong>Terselesaikan</strong> tanpa memanggil ulang AI.
                    </p>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Catatan Klarifikasi Tim M2B:</label>
                        <textarea
                            wire:model="resolutionNote"
                            rows="4"
                            class="w-full text-xs rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 p-3"
                            placeholder="Contoh: Sudah konfirmasi ke pihak pelayaran, 5 koli adalah item bonus terlampir di lembar 2 PL. Aman untuk diajukan."
                        ></textarea>
                        @error('resolutionNote') <span class="text-[11px] text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                    <button
                        wire:click="$set('showResolveModal', false)"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-lg transition"
                    >
                        Batal
                    </button>
                    <button
                        wire:click="saveResolution"
                        class="px-4 py-2 text-xs font-bold bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-sm"
                    >
                        Simpan Konfirmasi
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL PRATINJAU WHATSAPP --}}
    @if($showWaModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 border border-gray-100" style="position: relative; z-index: 10;" @click.outside="$wire.set('showWaModal', false)">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                        <span>💬</span> Draf Pesan WhatsApp Resmi
                    </h4>
                    <button wire:click="$set('showWaModal', false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nomor WhatsApp Penerima (Klien):</label>
                        <input
                            type="text"
                            wire:model="waNumber"
                            class="w-full text-xs rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 p-2.5 font-mono"
                            placeholder="Contoh: 0812xxxxxxxx / 62812xxxxxxxx"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Pratinjau Naskah Pesan:</label>
                        <textarea
                            wire:model="waText"
                            rows="9"
                            class="w-full text-xs rounded-xl border-gray-200 bg-slate-50 focus:border-emerald-500 focus:ring-emerald-500 p-3 font-mono leading-relaxed"
                        ></textarea>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between gap-2 pt-3 border-t border-gray-100">
                    <button
                        wire:click="$set('showWaModal', false)"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-lg transition"
                    >
                        Tutup
                    </button>

                    @php
                        // Bersihkan nomor HP untuk format internasional
                        $cleanPhone = preg_replace('/[^0-9]/', '', $waNumber);
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '62' . substr($cleanPhone, 1);
                        }
                        $waUrl = 'https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($waText);
                    @endphp

                    <a
                        href="{{ $waUrl }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition shadow-sm"
                    >
                        <span>🚀</span> Buka WhatsApp Web / App
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL PRATINJAU EMAIL --}}
    @if($showEmailModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 border border-gray-100" style="position: relative; z-index: 10;" @click.outside="$wire.set('showEmailModal', false)">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                        <span>✉️</span> Draf Email Resmi
                    </h4>
                    <button wire:click="$set('showEmailModal', false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Email Penerima:</label>
                        <input
                            type="email"
                            wire:model="emailRecipient"
                            class="w-full text-xs rounded-xl border-gray-200 p-2.5 font-mono"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Subjek Email:</label>
                        <input
                            type="text"
                            wire:model="emailSubject"
                            class="w-full text-xs rounded-xl border-gray-200 p-2.5 font-semibold text-gray-800"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Isi Pesan:</label>
                        <textarea
                            wire:model="emailBody"
                            rows="7"
                            class="w-full text-xs rounded-xl border-gray-200 bg-slate-50 p-3 leading-relaxed"
                        ></textarea>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between gap-2 pt-3 border-t border-gray-100">
                    <button
                        wire:click="$set('showEmailModal', false)"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-lg transition"
                    >
                        Tutup
                    </button>

                    @php
                        $mailtoUrl = 'mailto:' . rawurlencode($emailRecipient) . '?subject=' . rawurlencode($emailSubject) . '&body=' . rawurlencode($emailBody);
                    @endphp

                    <a
                        href="{{ $mailtoUrl }}"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-sm"
                    >
                        <span>📬</span> Buka Aplikasi Email
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
