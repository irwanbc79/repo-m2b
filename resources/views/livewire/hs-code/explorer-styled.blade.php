<div>
    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
        .search-container { max-width: 1400px; margin: 0 auto; padding: 1.5rem; }
        .header-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; }
        .search-box { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .search-input { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 16px; }
        .search-input:focus { outline: none; border-color: #667eea; }
        .btn-clear { background: #ef4444; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .result-card { background: white; padding: 1rem 1.5rem; border-radius: 8px; border-left: 4px solid #667eea; margin-bottom: 0.5rem; transition: all 0.2s; }
        .result-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .result-card.selected { border-left-color: #10b981; background: #ecfdf5; }
        .hs-code { font-family: monospace; font-size: 16px; font-weight: bold; color: #667eea; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; }
        .badge-level { background: #e0e7ff; color: #4c51bf; }
        .badge-chapter { background: #fef3c7; color: #92400e; margin-left: 4px; }
        .btn-detail { background: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; border: none; margin-left: 8px; }
        .btn-detail:hover { background: #059669; }
        .hierarchy-panel { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 2px solid #10b981; }
        .hierarchy-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 2px solid #e5e7eb; }
        .hierarchy-title { font-size: 18px; font-weight: bold; color: #059669; }
        .btn-close { background: #f3f4f6; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; }
        .hierarchy-section { background: #fef3c7; padding: 12px 16px; border-radius: 8px; margin-bottom: 12px; }
        .hierarchy-chapter { background: #dbeafe; padding: 12px 16px; border-radius: 8px; margin-bottom: 12px; margin-left: 20px; }
        .tree-item { padding: 10px 16px; border-left: 3px solid #d1d5db; margin-left: 20px; margin-bottom: 8px; background: #f9fafb; border-radius: 0 8px 8px 0; }
        .tree-item.level-4 { border-left-color: #f59e0b; background: #fffbeb; }
        .tree-item.level-6 { border-left-color: #3b82f6; background: #eff6ff; margin-left: 40px; }
        .tree-item.level-8 { border-left-color: #10b981; background: #ecfdf5; margin-left: 60px; }
        .tree-item.selected { border-left-color: #ef4444; background: #fef2f2; }
        .tree-code { font-family: monospace; font-weight: bold; color: #374151; }
        .tree-desc-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 6px; font-size: 13px; }
        .siblings-section { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #d1d5db; }
        .sibling-item { display: inline-block; background: #f3f4f6; padding: 4px 10px; border-radius: 4px; margin: 4px; font-size: 13px; cursor: pointer; }
        .sibling-item:hover { background: #e5e7eb; }
        .table-header { display: grid; grid-template-columns: 130px 1fr 1fr 80px 80px 140px; gap: 1rem; font-size: 11px; font-weight: 600; color: #6b7280; padding: 10px 16px; background: #f1f5f9; border-radius: 8px; margin-bottom: 8px; text-transform: uppercase; }
        .table-row { display: grid; grid-template-columns: 130px 1fr 1fr 80px 80px 140px; gap: 1rem; align-items: center; }
    </style>
    <div class="search-container">
        <div class="header-section">
            <h1 style="margin: 0 0 0.5rem 0; font-size: 28px;">🔍 HS Code Explorer - BTKI 2022</h1>
            <p style="margin: 0; opacity: 0.9;">Database {{ number_format(\DB::table('hs_codes')->count()) }} kode HS - Klik DETAIL untuk lihat hierarki klasifikasi</p>
        </div>
        <div class="search-box">
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px;">
                    <input type="text" wire:model.live.debounce.300ms="search" class="search-input" placeholder="Cari kode HS atau deskripsi barang...">
                </div>
                @if($search)<button wire:click="$set('search', '')" class="btn-clear">✕ Clear</button>@endif
            </div>
            @if($search)<div style="margin-top: 12px; padding: 10px; background: #eff6ff; border-radius: 6px; color: #1e40af;">📌 Pencarian: "{{ $search }}" ({{ $results->total() }} hasil)</div>@endif
        </div>
        @if($selectedCode && is_array($hierarchy) && count($hierarchy) > 0)
        <div class="hierarchy-panel">
            <div class="hierarchy-header">
                <div class="hierarchy-title">📊 Hierarki Klasifikasi: {{ $selectedCode }}</div>
                <button wire:click="closeHierarchy" class="btn-close">✕ Tutup</button>
            </div>
            @if(isset($hierarchy['section']))<div class="hierarchy-section"><div style="font-weight:600;color:#92400e;">📦 Bagian {{ $hierarchy['section']['number'] }}</div><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:6px;font-size:14px;"><span style="color:#78350f;">{{ $hierarchy['section']['title_id'] }}</span><span style="color:#a16207;font-style:italic;">{{ $hierarchy['section']['title_en'] }}</span></div></div>@endif
            @if(isset($hierarchy['chapter']))<div class="hierarchy-chapter"><div style="font-weight:600;color:#1e40af;">📁 Bab {{ $hierarchy['chapter']['number'] }}</div><div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:6px;font-size:14px;"><span style="color:#1e3a8a;">{{ $hierarchy['chapter']['title_id'] }}</span><span style="color:#3b82f6;font-style:italic;">{{ $hierarchy['chapter']['title_en'] }}</span></div></div>@endif
            @if(isset($hierarchy['levels']) && count($hierarchy['levels']) > 0)
            <div style="margin-left:20px;">
                @foreach($hierarchy['levels'] as $level)
                <div class="tree-item level-{{ $level['level'] }} {{ (isset($level['is_selected']) && $level['is_selected']) ? 'selected' : '' }}">
                    <div class="tree-code">{{ $level['code'] }} <span class="badge badge-level">{{ $level['level'] }} Digit</span>@if(isset($level['is_selected']) && $level['is_selected'])<span class="badge" style="background:#fee2e2;color:#dc2626;margin-left:4px;">← Dipilih</span>@endif</div>
                    <div class="tree-desc-row"><span style="color:#374151;">{{ $level['description_id'] ?? '-' }}</span><span style="color:#6b7280;font-style:italic;">{{ $level['description_en'] ?? '-' }}</span></div>
                </div>
                @endforeach
            </div>
            @endif
            @if(isset($hierarchy['siblings']) && count($hierarchy['siblings']) > 0)<div class="siblings-section"><div style="font-size:14px;color:#6b7280;margin-bottom:8px;">🔗 Kode sejenis (level sama):</div>@foreach($hierarchy['siblings'] as $sib)<span class="sibling-item" wire:click="showHierarchy('{{ $sib->hs_code }}')">{{ $sib->hs_code }}</span>@endforeach</div>@endif
        </div>
        @endif
        <div wire:loading style="text-align:center;padding:2rem;"><div style="width:40px;height:40px;border:4px solid #e5e7eb;border-top-color:#667eea;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto;"></div><p style="color:#6b7280;margin-top:1rem;">Memuat...</p></div>
        <div wire:loading.remove>
            @if($results->count() > 0)
            <div style="margin-bottom:1rem;"><h2 style="font-size:18px;color:#374151;margin:0;">📊 Hasil Pencarian ({{ $results->total() }} kode)</h2></div>
            <div class="table-header"><span>KODE</span><span>URAIAN (INDONESIA)</span><span>DESCRIPTION (ENGLISH)</span><span>BEA MASUK</span><span>BEA KELUAR</span><span>INFO</span></div>
            @foreach($results as $code)
            <div class="result-card {{ $selectedCode == $code->hs_code ? 'selected' : '' }}">
                <div class="table-row">
                    <div class="hs-code">{{ $code->hs_code }}</div>
                    <div style="color:#374151;font-size:14px;">{{ $code->description_id ?: '-' }}</div>
                    <div style="color:#6b7280;font-size:14px;font-style:italic;">{{ $code->description_en ?: '-' }}</div>
                    <div style="text-align:center;font-size:13px;">@if($code->hs_level == 8 && $code->import_duty)<span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:4px;font-weight:600;">{{ $code->import_duty }}{{ is_numeric($code->import_duty) ? '%' : '' }}</span>@else<span style="color:#9ca3af;">-</span>@endif</div>
                    <div style="text-align:center;font-size:13px;">@if($code->hs_level == 8 && $code->export_duty && $code->export_duty != '-')<span style="background:#ffedd5;color:#9a3412;padding:2px 8px;border-radius:4px;font-weight:600;">{{ $code->export_duty }}{{ is_numeric($code->export_duty) ? '%' : '' }}</span>@else<span style="color:#9ca3af;">-</span>@endif</div>
                    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:4px;">
                        <span class="badge badge-level">{{ $code->hs_level }} Digit</span>
                        @if($code->chapter_number)<span class="badge badge-chapter">Bab {{ $code->chapter_number }}</span>@endif
                        <button wire:click="showHierarchy('{{ $code->hs_code }}')" class="btn-detail" title="Lihat Detail Hierarki">👁</button>
                    </div>
                </div>
            </div>
            @endforeach
            <div style="margin-top:1.5rem;">{{ $results->links() }}</div>
            @else
            <div style="text-align:center;padding:3rem;"><div style="font-size:64px;">🔍</div><h3 style="color:#6b7280;">Tidak ada hasil ditemukan</h3><p style="color:#9ca3af;">Coba kata kunci lain</p></div>
            @endif
        </div>
    </div>
</div>
