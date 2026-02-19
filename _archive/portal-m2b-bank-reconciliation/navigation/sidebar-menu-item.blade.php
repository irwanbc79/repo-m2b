{{--
================================================================================
MENU ITEM UNTUK BANK RECONCILIATION
================================================================================

Tambahkan kode di bawah ini ke file navigasi sidebar Anda.
Biasanya file berada di:
- resources/views/layouts/navigation.blade.php
- resources/views/components/sidebar.blade.php
- atau file navigasi lainnya

COPY KODE DI BAWAH INI:
================================================================================
--}}

{{-- Menu Bank Reconciliation - Hanya untuk Admin --}}
@can('manage-admin')
<a href="{{ route('admin.bank-reconciliation') }}" 
   class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg transition {{ request()->routeIs('admin.bank-reconciliation') ? 'bg-blue-50 text-blue-700 font-medium' : '' }}">
    <span class="text-xl">🏦</span>
    <span>Rekonsiliasi Bank</span>
    @php
        $unreconciledCount = \App\Models\BankTransaction::unreconciled()->count();
    @endphp
    @if($unreconciledCount > 0)
        <span class="ml-auto px-2 py-0.5 text-xs bg-orange-100 text-orange-700 rounded-full">
            {{ $unreconciledCount }}
        </span>
    @endif
</a>
@endcan

{{--
================================================================================
ALTERNATIF: Jika menggunakan format array untuk menu
================================================================================
--}}

{{--
[
    'name' => 'Rekonsiliasi Bank',
    'route' => 'admin.bank-reconciliation',
    'icon' => '🏦',
    'permission' => 'manage-admin',
    'badge' => function() {
        return \App\Models\BankTransaction::unreconciled()->count();
    },
],
--}}

{{--
================================================================================
ALTERNATIF: Jika menggunakan Livewire Navigation Component
================================================================================
--}}

{{--
<x-nav-link :href="route('admin.bank-reconciliation')" :active="request()->routeIs('admin.bank-reconciliation')">
    <x-slot name="icon">🏦</x-slot>
    {{ __('Rekonsiliasi Bank') }}
</x-nav-link>
--}}
