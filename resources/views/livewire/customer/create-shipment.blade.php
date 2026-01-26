<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Create New Booking</h2>
            <p class="text-gray-500 text-sm">Submit a new shipment order request.</p>
        </div>
        <a href="{{ route('customer.shipments.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">&larr; Cancel & Back</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 md:p-8 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Origin (City/Port)</label>
                    <input type="text" wire:model="origin" class="w-full border-gray-300 rounded-lg focus:ring-m2b-primary focus:border-m2b-primary" placeholder="e.g. Shanghai, China">
                    @error('origin') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Destination (City/Port)</label>
                    <input type="text" wire:model="destination" class="w-full border-gray-300 rounded-lg focus:ring-m2b-primary focus:border-m2b-primary" placeholder="e.g. Jakarta, Indonesia">
                    @error('destination') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <hr class="border-gray-100">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Service Type</label>
                    <select wire:model="service_type" class="w-full border-gray-300 rounded-lg bg-gray-50">
                        <option value="import">Import</option>
                        <option value="export">Export</option>
                        <option value="domestic">Domestic</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Transport Mode</label>
                    <select wire:model="shipment_type" class="w-full border-gray-300 rounded-lg bg-gray-50">
                        <option value="sea">Sea Freight (Laut)</option>
                        <option value="air">Air Freight (Udara)</option>
                        <option value="land">Land Freight (Darat)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Container Mode</label>
                    <select wire:model="container_mode" class="w-full border-gray-300 rounded-lg bg-gray-50">
                        <option value="LCL">LCL (Less Container)</option>
                        <option value="FCL">FCL (Full Container)</option>
                        <option value="Non-Container">Non-Container / Bulk</option>
                    </select>
                </div>
            </div>

            <div class="bg-blue-50 p-5 rounded-lg border border-blue-100">
                <h4 class="text-sm font-bold text-blue-800 uppercase mb-4 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Cargo Details
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 mb-1">Container Info / Dimensions</label>
                        <input type="text" wire:model="container_info" class="w-full border-gray-300 rounded-lg text-sm" placeholder="e.g. Description of Goods/ 2x40HC or 120x100x80 cm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Weight (Kg)</label>
                        <input type="number" step="0.01" wire:model="weight" class="w-full border-gray-300 rounded-lg text-sm text-right" placeholder="0.00">
                        @error('weight') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Volume (CBM)</label>
                        <input type="number" step="0.001" wire:model="volume" class="w-full border-gray-300 rounded-lg text-sm text-right" placeholder="0.000">
                        @error('volume') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Qty</label>
                            <input type="number" wire:model="pieces" class="w-full border-gray-300 rounded-lg text-sm text-center" placeholder="0">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Kemasan</label>
                                <select wire:model="package_type" class="w-full border-gray-300 rounded-lg text-sm">
                                    <option value="">-- Pilih --</option>
                                    <optgroup label="📦 Packaging">
                                        <option value="Ctn">Ctn</option>
                                        <option value="Box">Box</option>
                                        <option value="Pkgs">Pkgs</option>
                                        <option value="Plt">Pallet</option>
                                        <option value="Crate">Crate</option>
                                        <option value="Case">Case</option>
                                        <option value="Skid">Skid</option>
                                    </optgroup>
                                    <optgroup label="🔗 Bundle">
                                        <option value="Bdl">Bundle</option>
                                        <option value="Bale">Bale</option>
                                        <option value="Coil">Coil</option>
                                        <option value="Roll">Roll</option>
                                        <option value="Reel">Reel</option>
                                    </optgroup>
                                    <optgroup label="🔢 Satuan">
                                        <option value="Pcs">Pcs</option>
                                        <option value="Unit">Unit</option>
                                        <option value="Set">Set</option>
                                        <option value="Pair">Pair</option>
                                        <option value="Dozen">Dozen</option>
                                        <option value="Ea">Each</option>
                                    </optgroup>
                                    <optgroup label="🛢️ Wadah">
                                        <option value="Bag">Bag</option>
                                        <option value="Sack">Sack</option>
                                        <option value="Drum">Drum</option>
                                        <option value="Barrel">Barrel</option>
                                        <option value="IBC">IBC</option>
                                        <option value="Jerrycan">Jerrycan</option>
                                        <option value="Bottle">Bottle</option>
                                        <option value="Can">Can</option>
                                        <option value="Cylinder">Cylinder</option>
                                        <option value="Tubes">Tubes</option>
                                        <option value="Tote">Tote</option>
                                    </optgroup>
                                    <optgroup label="⚖️ Berat">
                                        <option value="Kg">Kg</option>
                                        <option value="Ton">Ton</option>
                                        <option value="MT">MT</option>
                                        <option value="Lbs">Lbs</option>
                                        <option value="Gram">Gram</option>
                                    </optgroup>
                                    <optgroup label="📐 Volume">
                                        <option value="M3">M3</option>
                                        <option value="CBM">CBM</option>
                                        <option value="Ltr">Liter</option>
                                        <option value="Gal">Gallon</option>
                                        <option value="CFT">CFT</option>
                                    </optgroup>
                                    <optgroup label="📏 Ukuran">
                                        <option value="Mtr">Meter</option>
                                        <option value="Ft">Feet</option>
                                        <option value="Yard">Yard</option>
                                        <option value="SQM">SQM</option>
                                        <option value="SQF">SQF</option>
                                    </optgroup>
                                    <optgroup label="🚢 Logistik">
                                        <option value="TEU">TEU</option>
                                        <option value="FEU">FEU</option>
                                        <option value="Lot">Lot</option>
                                        <option value="Shipment">Shipment</option>
                                    </optgroup>
                                    <optgroup label="📋 Lainnya">
                                        <option value="Other">Other</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                    </div>
                    {{-- HS Code dengan Autocomplete --}}
                    <div x-data="hsCodeAutocomplete()" class="relative mt-3">
                        <label class="block text-xs font-bold text-gray-500 mb-1">HS Code (Opsional)</label>
                        <input type="text" x-model="search" @input.debounce.300ms="fetchResults" @focus="showDropdown = true" @click.away="showDropdown = false" wire:model="hs_code" class="w-full border-gray-300 rounded-lg text-sm font-mono" placeholder="Ketik HS Code atau nama barang..." autocomplete="off">
                        <div x-show="showDropdown && results.length > 0" x-cloak class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="item in results" :key="item.hs_code">
                                <div @click="selectItem(item)" class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100">
                                    <span class="font-mono text-sm font-bold text-blue-600" x-text="item.hs_code"></span>
                                    <p class="text-xs text-gray-600 mt-0.5 line-clamp-1" x-text="item.description_id"></p>
                                </div>
                            </template>
                        </div>
                        <p class="text-xs text-gray-400 mt-1" x-show="!selectedDesc">Kode tarif kepabeanan (BTKI)</p>
                        <p class="text-xs text-green-600 mt-1" x-show="selectedDesc" x-text="selectedDesc"></p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Notes / Instructions</label>
                <textarea wire:model="notes" rows="3" class="w-full border-gray-300 rounded-lg" placeholder="Any special handling instructions?"></textarea>
            </div>

            <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100">
                <a href="{{ route('customer.shipments.index') }}" class="text-gray-500 hover:text-gray-800 font-medium text-sm px-4 py-2">Cancel</a>
                <button wire:click="save" class="bg-m2b-primary hover:bg-blue-900 text-white font-bold py-3 px-8 rounded-lg shadow-lg transform transition hover:-translate-y-0.5 flex items-center">
                    <svg wire:loading.remove wire:target="save" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Submit Booking
                </button>
            </div>

        </div>
    </div>
<script>
function hsCodeAutocomplete() {
    return {
        search: "",
        results: [],
        showDropdown: false,
        selectedDesc: "",
        async fetchResults() {
            if (this.search.length < 2) { this.results = []; return; }
            try {
                const response = await fetch(`/api/hs-codes/search?q=${encodeURIComponent(this.search)}`);
                this.results = await response.json();
            } catch (e) { this.results = []; }
        },
        selectItem(item) {
            this.search = item.hs_code;
            this.selectedDesc = item.description_id;
            this.showDropdown = false;
            this.$wire.set("hs_code", item.hs_code);
        }
    }
}
</script>
</div>