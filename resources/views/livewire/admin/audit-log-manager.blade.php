<div class="space-y-6">
    @section('header', 'Audit Logs / Rekam Jejak')

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-black text-gray-800">{{ number_format($stats['total'] ?? 0) }}</p>
            <p class="text-xs text-gray-500">Total Logs</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 shadow-sm border border-blue-100 text-center">
            <p class="text-2xl font-black text-blue-600">{{ $stats['today'] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Hari Ini</p>
        </div>
        <div class="bg-indigo-50 rounded-xl p-4 shadow-sm border border-indigo-100 text-center">
            <p class="text-2xl font-black text-indigo-600">{{ $stats['this_week'] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Minggu Ini</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 shadow-sm border border-purple-100 text-center">
            <p class="text-2xl font-black text-purple-600">{{ $stats['users_active'] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Users Aktif</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 shadow-sm border border-green-100 text-center">
            <p class="text-2xl font-black text-green-600">{{ $stats['creates'] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Create</p>
        </div>
        <div class="bg-amber-50 rounded-xl p-4 shadow-sm border border-amber-100 text-center">
            <p class="text-2xl font-black text-amber-600">{{ $stats['updates'] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Update</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4 shadow-sm border border-red-100 text-center">
            <p class="text-2xl font-black text-red-600">{{ $stats['deletes'] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Delete</p>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Filters & Export --}}
        <div class="p-6 border-b border-gray-100 bg-gray-50">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
                {{-- Search --}}
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari log..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Export Button --}}
                <button wire:click="exportExcel" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <select wire:model.live="filterUser" class="border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua User</option>
                    @foreach($filterOptions['users'] ?? [] as $user)
                        <option value="{{ $user }}">{{ $user }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterModule" class="border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Modul</option>
                    @foreach($filterOptions['modules'] ?? [] as $module)
                        <option value="{{ $module }}">{{ $module }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterAction" class="border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Action</option>
                    @foreach($filterOptions['actions'] ?? [] as $action)
                        <option value="{{ $action }}">{{ $action }}</option>
                    @endforeach
                </select>

                <input wire:model.live="filterDateFrom" type="date" class="border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500" title="Dari Tanggal">
                <input wire:model.live="filterDateTo" type="date" class="border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500" title="Sampai Tanggal">

                @if($filterUser || $filterModule || $filterAction || $filterDateFrom || $filterDateTo)
                <button wire:click="clearFilters" class="text-sm text-red-600 hover:text-red-800 font-medium">
                    ✕ Reset Filter
                </button>
                @endif
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-800 text-white text-xs uppercase">
                    <tr>
                        <th class="px-6 py-3">Waktu</th>
                        <th class="px-6 py-3">User</th>
                        <th class="px-6 py-3">Modul</th>
                        <th class="px-6 py-3">Ref No</th>
                        <th class="px-6 py-3">Aktivitas</th>
                        <th class="px-6 py-3">IP Address</th>
                        <th class="px-6 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-gray-800 font-medium">{{ $log->created_at->format('d M Y') }}</p>
                            <p class="text-xs text-gray-500">{{ $log->created_at->format('H:i:s') }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @php
                                    $colors = ['A' => 'bg-red-500', 'B' => 'bg-blue-500', 'C' => 'bg-green-500', 'D' => 'bg-yellow-500', 'E' => 'bg-purple-500', 'F' => 'bg-pink-500', 'G' => 'bg-indigo-500', 'H' => 'bg-teal-500', 'I' => 'bg-orange-500', 'J' => 'bg-cyan-500'];
                                    $initial = strtoupper(substr($log->user_name ?? 'U', 0, 1));
                                    $bgColor = $colors[$initial] ?? 'bg-gray-500';
                                @endphp
                                <div class="w-8 h-8 {{ $bgColor }} rounded-full flex items-center justify-center text-white font-bold text-sm">
                                    {{ $initial }}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800">{{ $log->user_name }}</p>
                                    <p class="text-xs text-gray-400 uppercase">{{ $log->role }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-700 uppercase">{{ $log->module }}</span>
                        </td>
                        <td class="px-6 py-4 font-mono text-blue-600 font-medium">
                            {{ $log->target_ref ?: '-' }}
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $actionColors = [
                                    'CREATE' => 'bg-green-100 text-green-700 border-green-200',
                                    'UPDATE' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'UPDATE STATUS' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    'UPDATE INFO' => 'bg-cyan-100 text-cyan-700 border-cyan-200',
                                    'DELETE' => 'bg-red-100 text-red-700 border-red-200',
                                    'LOGIN' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    'LOGOUT' => 'bg-gray-100 text-gray-700 border-gray-200',
                                ];
                                $color = $actionColors[$log->action] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $color }}">
                                @if($log->action == 'CREATE')
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                @elseif(str_contains($log->action, 'UPDATE'))
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                @elseif($log->action == 'DELETE')
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                @endif
                                {{ $log->action }}
                            </span>
                            <p class="text-xs text-gray-500 mt-1 max-w-xs truncate" title="{{ $log->description }}">{{ $log->description }}</p>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-400 font-mono">
                            {{ $log->ip_address }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button wire:click="viewDetail({{ $log->id }})" class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg text-xs font-medium transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="text-gray-500 font-medium">Belum ada aktivitas terekam</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <p class="text-sm text-gray-600">
                Menampilkan {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} log
            </p>
            {{ $logs->links() }}
        </div>
    </div>

    {{-- MODAL: Activity Detail with Before/After Comparison --}}
    @if($showDetailModal && $selectedLog)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" wire:click="closeDetailModal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto" wire:click.stop>
            {{-- Header --}}
            <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-4 rounded-t-2xl flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <h3 class="text-xl font-bold">Activity Detail</h3>
                </div>
                <button wire:click="closeDetailModal" class="hover:bg-white/20 rounded-lg p-1 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="p-6 space-y-6">
                {{-- Basic Info --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs text-gray-500 mb-1">Timestamp</p>
                        <p class="font-bold text-gray-800">{{ $selectedLog->created_at->format('d M Y, H:i:s') }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs text-gray-500 mb-1">User</p>
                        <p class="font-bold text-gray-800">{{ $selectedLog->user_name }} <span class="text-xs text-gray-500">({{ $selectedLog->role }})</span></p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs text-gray-500 mb-1">Module</p>
                        <p class="font-bold text-gray-800">{{ $selectedLog->module }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs text-gray-500 mb-1">Action</p>
                        <p class="font-bold text-gray-800">{{ $selectedLog->action }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs text-gray-500 mb-1">Reference</p>
                        <p class="font-bold text-blue-600 font-mono">{{ $selectedLog->target_ref ?: '-' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs text-gray-500 mb-1">IP Address</p>
                        <p class="font-mono text-sm text-gray-800">{{ $selectedLog->ip_address }}</p>
                    </div>
                </div>

                {{-- Description --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-xs text-blue-600 font-bold mb-2">DESCRIPTION</p>
                    <p class="text-gray-800">{{ $selectedLog->description ?: 'No description' }}</p>
                </div>

                {{-- Before/After Comparison --}}
                @if($selectedLog->hasComparison())
                <div class="border-t border-gray-200 pt-6">
                    <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                        Changes Comparison
                    </h4>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        {{-- Before --}}
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <p class="text-xs text-red-600 font-bold mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                BEFORE
                            </p>
                            <div class="space-y-2 text-sm">
                                @foreach($selectedLog->changes as $field => $change)
                                <div class="bg-white rounded p-2">
                                    <p class="text-xs text-gray-500 font-medium">{{ ucfirst(str_replace('_', ' ', $field)) }}</p>
                                    <p class="text-gray-800 font-mono">{{ $change['old'] ?? 'null' }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- After --}}
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <p class="text-xs text-green-600 font-bold mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                AFTER
                            </p>
                            <div class="space-y-2 text-sm">
                                @foreach($selectedLog->changes as $field => $change)
                                <div class="bg-white rounded p-2">
                                    <p class="text-xs text-gray-500 font-medium">{{ ucfirst(str_replace('_', ' ', $field)) }}</p>
                                    <p class="text-gray-800 font-mono font-bold">{{ $change['new'] ?? 'null' }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-gray-500 text-sm">No comparison data available</p>
                </div>
                @endif

                {{-- User Agent --}}
                @if($selectedLog->user_agent)
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-xs text-gray-500 mb-1">User Agent</p>
                    <p class="text-xs text-gray-600 font-mono break-all">{{ $selectedLog->user_agent }}</p>
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="sticky bottom-0 bg-gray-50 px-6 py-4 rounded-b-2xl border-t border-gray-200">
                <button wire:click="closeDetailModal" class="w-full px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-lg font-medium transition">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
