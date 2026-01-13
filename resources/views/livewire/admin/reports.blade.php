<div class="space-y-6">
    @section('header', 'Laporan Pengiriman')

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 print:hidden">
        <h3 class="font-bold text-gray-800 mb-4">Filter Laporan</h3>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Dari Tanggal</label>
                <input type="date" wire:model.live="startDate" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Sampai Tanggal</label>
                <input type="date" wire:model.live="endDate" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Status</label>
                <select wire:model.live="status" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">Semua Status</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase">Customer</label>
                <select wire:model.live="customerId" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">Semua Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button onclick="window.print()" class="w-full bg-gray-800 hover:bg-black text-white px-4 py-2 rounded-lg shadow-sm flex justify-center items-center gap-2 font-bold transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Cetak / PDF
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8" id="printableArea">
        <div class="text-center border-b-2 border-gray-800 pb-6 mb-6">
            <h1 class="text-3xl font-black text-m2b-primary tracking-tighter">M2B LOGISTIC</h1>
            <p class="text-sm text-gray-500 tracking-widest uppercase">Laporan Rekapitulasi Pengiriman</p>
            <p class="text-xs text-gray-400 mt-2">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="bg-gray-50 p-4 rounded border border-gray-100 text-center">
                <p class="text-xs text-gray-500 uppercase font-bold">Total Semua Kiriman</p>
                <p class="text-3xl font-black text-gray-800 mt-1">{{ $summary['total_count'] }}</p>
                <p class="text-[10px] text-gray-400">Dalam Periode Ini</p>
            </div>

            <div class="bg-green-50 p-4 rounded border border-green-100 text-center">
                <p class="text-xs text-green-600 uppercase font-bold">Kiriman Selesai (Completed)</p>
                <p class="text-3xl font-black text-green-700 mt-1">{{ $summary['completed_count'] }}</p>
                <p class="text-[10px] text-green-500">Barang Diterima</p>
            </div>

            <div class="bg-blue-50 p-4 rounded border border-blue-100 text-center">
                <p class="text-xs text-blue-600 uppercase font-bold">Sedang Berjalan (Active)</p>
                <p class="text-3xl font-black text-blue-800 mt-1">{{ $summary['active_count'] }}</p>
                <p class="text-[10px] text-blue-500">Pending / In Transit</p>
            </div>
        </div>

        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="border-b-2 border-gray-800">
                    <th class="py-3 font-bold text-gray-700">Tgl</th>
                    <th class="py-3 font-bold text-gray-700">Ref No</th>
                    <th class="py-3 font-bold text-gray-700">Customer</th>
                    <th class="py-3 font-bold text-gray-700">Rute</th>
                    <th class="py-3 font-bold text-gray-700">Layanan</th>
                    <th class="py-3 font-bold text-gray-700 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($shipments as $shipment)
                <tr>
                    <td class="py-3">{{ $shipment->created_at->format('d/m/y') }}</td>
                    <td class="py-3 font-mono font-bold">{{ $shipment->awb_number }}</td>
                    <td class="py-3">
                        {{ $shipment->customer->company_name }}
                        <br><span class="text-xs text-gray-400">{{ $shipment->customer->customer_code }}</span>
                    </td>
                    <td class="py-3">{{ $shipment->origin }} &rarr; {{ $shipment->destination }}</td>
                    <td class="py-3 capitalize">
                        {{ $shipment->service_type }} 
                        <span class="text-xs text-gray-500">({{ $shipment->container_mode }})</span>
                    </td>
                    <td class="py-3 text-center">
                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase border
                            @if($shipment->status == 'completed') border-green-200 text-green-800 bg-green-50
                            @elseif($shipment->status == 'pending') border-yellow-200 text-yellow-800 bg-yellow-50
                            @else border-blue-200 text-blue-800 bg-blue-50 @endif">
                            {{ str_replace('_', ' ', $shipment->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-8 text-center text-gray-500 italic">Tidak ada data pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-16 flex justify-end print:mt-24">
            <div class="text-center w-48">
                <p class="text-xs text-gray-500 mb-20">Dicetak pada: {{ now()->format('d M Y H:i') }}</p>
                <p class="text-sm font-bold underline">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-500 uppercase">M2B {{ auth()->user()->role }}</p>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            #printableArea, #printableArea * { visibility: visible; }
            #printableArea { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; }
            .print\:hidden { display: none !important; }
        }
    </style>
</div>