<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8 px-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <img src="{{ asset('images/m2b-logo.png') }}" alt="M2B Logo" class="h-16 mx-auto mb-4">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Survey Kepuasan Pelanggan M2B</h1>
            <p class="text-gray-600">Bantu kami meningkatkan layanan dengan mengisi survey ini</p>
            <p class="text-sm text-gray-500 mt-2">⏱️ Estimasi waktu: 5-7 menit</p>
        </div>

        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">Progress</span>
                <span class="text-sm font-medium text-gray-700">{{ round(($currentStep / $totalSteps) * 100) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" 
                     style="width: {{ ($currentStep / $totalSteps) * 100 }}%"></div>
            </div>
            <div class="flex justify-between mt-2">
                @for($i = 1; $i <= $totalSteps; $i++)
                    <span class="text-xs {{ $currentStep >= $i ? 'text-blue-600 font-semibold' : 'text-gray-400' }}">
                        Step {{ $i }}
                    </span>
                @endfor
            </div>
        </div>

        <!-- Survey Form Card -->
        <div class="bg-white rounded-lg shadow-xl p-8">
            <form wire:submit.prevent="submitSurvey">
                
                {{-- STEP 1: INFORMASI RESPONDEN --}}
                @if($currentStep === 1)
                    <div class="space-y-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">📋 Informasi Responden</h2>
                        
                        <!-- Anonymous Toggle -->
                        <div class="flex items-center space-x-3 bg-yellow-50 p-4 rounded-lg">
                            <input type="checkbox" id="is_anonymous" wire:model.live="is_anonymous" class="w-5 h-5 text-blue-600">
                            <label for="is_anonymous" class="text-gray-700 font-medium">Saya ingin mengisi secara anonim</label>
                        </div>

                        <!-- Company Name -->
                        @if(!$is_anonymous)
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">Nama Perusahaan <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="company_name" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="PT. Contoh Perusahaan">
                                @error('company_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <!-- Position -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Jabatan Anda <span class="text-red-500">*</span></label>
                            <select wire:model="respondent_position" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Pilih Jabatan</option>
                                <option value="owner">Owner / Pemilik</option>
                                <option value="director">Direktur / Direksi</option>
                                <option value="manager">Manager</option>
                                <option value="staff">Staff</option>
                                <option value="other">Lainnya</option>
                            </select>
                            @error('respondent_position') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        @if($respondent_position === 'other')
                            <div>
                                <input type="text" wire:model="respondent_position_other" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                       placeholder="Sebutkan jabatan Anda">
                            </div>
                        @endif

                        <!-- Services Used -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-3">Layanan yang Pernah Digunakan <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-blue-50 cursor-pointer">
                                    <input type="checkbox" wire:model="services_used" value="import" class="w-5 h-5 text-blue-600">
                                    <span class="text-gray-700">Import</span>
                                </label>
                                <label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-blue-50 cursor-pointer">
                                    <input type="checkbox" wire:model="services_used" value="export" class="w-5 h-5 text-blue-600">
                                    <span class="text-gray-700">Export</span>
                                </label>
                                <label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-blue-50 cursor-pointer">
                                    <input type="checkbox" wire:model="services_used" value="domestic" class="w-5 h-5 text-blue-600">
                                    <span class="text-gray-700">Domestic Logistics</span>
                                </label>
                                <label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-blue-50 cursor-pointer">
                                    <input type="checkbox" wire:model="services_used" value="customs" class="w-5 h-5 text-blue-600">
                                    <span class="text-gray-700">Customs Clearance / PPJK</span>
                                </label>
                                <label class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-blue-50 cursor-pointer">
                                    <input type="checkbox" wire:model="services_used" value="freight" class="w-5 h-5 text-blue-600">
                                    <span class="text-gray-700">Freight Forwarding</span>
                                </label>
                            </div>
                            @error('services_used') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                {{-- STEP 2: KEPUASAN UMUM --}}
                @if($currentStep === 2)
                    <div class="space-y-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">⭐ Kepuasan Umum</h2>
                        <p class="text-gray-600 mb-6">Berikan rating 1-5 (1 = Sangat Tidak Puas, 5 = Sangat Puas)</p>

                        <!-- Overall Satisfaction -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-3">
                                Seberapa puas Anda secara keseluruhan terhadap layanan M2B? <span class="text-red-500">*</span>
                            </label>
                            <div class="flex justify-center space-x-4">
                                @for($i = 1; $i <= 5; $i++)
                                    <button type="button" wire:click="$set('overall_satisfaction', {{ $i }})"
                                            class="w-16 h-16 rounded-full border-2 transition-all
                                                   {{ $overall_satisfaction == $i ? 'bg-blue-600 text-white border-blue-600 scale-110' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400' }}">
                                        <div class="text-2xl">{{ $i === 1 ? '😞' : ($i === 2 ? '😐' : ($i === 3 ? '🙂' : ($i === 4 ? '😊' : '😍'))) }}</div>
                                        <div class="text-xs">{{ $i }}</div>
                                    </button>
                                @endfor
                            </div>
                            @error('overall_satisfaction') <span class="text-red-500 text-sm block text-center mt-2">{{ $message }}</span> @enderror
                        </div>

                        <!-- Service Fit -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-3">
                                Seberapa sesuai layanan M2B dengan kebutuhan bisnis Anda? <span class="text-red-500">*</span>
                            </label>
                            <div class="flex justify-center space-x-4">
                                @for($i = 1; $i <= 5; $i++)
                                    <button type="button" wire:click="$set('service_fit_needs', {{ $i }})"
                                            class="w-16 h-16 rounded-full border-2 transition-all
                                                   {{ $service_fit_needs == $i ? 'bg-blue-600 text-white border-blue-600 scale-110' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400' }}">
                                        <div class="text-2xl">{{ $i === 1 ? '😞' : ($i === 2 ? '😐' : ($i === 3 ? '🙂' : ($i === 4 ? '😊' : '😍'))) }}</div>
                                        <div class="text-xs">{{ $i }}</div>
                                    </button>
                                @endfor
                            </div>
                            @error('service_fit_needs') <span class="text-red-500 text-sm block text-center mt-2">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif

                {{-- STEP 3: KUALITAS OPERASIONAL --}}
                @if($currentStep === 3)
                    <div class="space-y-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">🚚 Kualitas Layanan Operasional</h2>
                        
                        @php
                            $operationalQuestions = [
                                'timely_delivery' => 'Ketepatan waktu pengiriman / penyelesaian dokumen',
                                'shipment_info_clarity' => 'Kejelasan informasi status shipment',
                                'document_accuracy' => 'Akurasi dokumen (invoice, BL/AWB, PIB/PEB, dll)',
                                'problem_handling' => 'Penanganan masalah / kendala di lapangan',
                                'coordination_quality' => 'Kualitas koordinasi tim M2B dengan pihak terkait'
                            ];
                        @endphp

                        @foreach($operationalQuestions as $field => $question)
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="block text-gray-700 font-medium mb-3">{{ $question }} <span class="text-red-500">*</span></label>
                                <div class="flex justify-between items-center">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" wire:click="$set('{{ $field }}', {{ $i }})"
                                                class="flex flex-col items-center p-2 rounded-lg transition-all
                                                       {{ $this->$field == $i ? 'bg-blue-600 text-white scale-110' : 'bg-white text-gray-600 hover:bg-blue-100' }}">
                                            <span class="text-xl mb-1">{{ $i }}</span>
                                            <span class="text-xs">{{ $i === 1 ? 'Buruk' : ($i === 5 ? 'Sangat Baik' : '') }}</span>
                                        </button>
                                    @endfor
                                </div>
                                @error($field) <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- STEP 4: KOMUNIKASI & PRICING --}}
                @if($currentStep === 4)
                    <div class="space-y-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">💬 Komunikasi & Harga</h2>
                        
                        @php
                            $combinedQuestions = [
                                'responsiveness' => 'Responsivitas tim M2B dalam menjawab pertanyaan',
                                'explanation_clarity' => 'Kejelasan penjelasan dari tim M2B',
                                'staff_professionalism' => 'Sikap profesional dan keramahan staf',
                                'contact_ease' => 'Kemudahan menghubungi tim M2B (WA/Email/Telepon)',
                                'price_fairness' => 'Kewajaran harga dibanding kualitas layanan',
                                'cost_transparency' => 'Transparansi biaya (tidak ada biaya tersembunyi)',
                                'invoice_accuracy' => 'Kesesuaian invoice dengan kesepakatan awal'
                            ];
                        @endphp

                        @foreach($combinedQuestions as $field => $question)
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="block text-gray-700 font-medium mb-3">{{ $question }} <span class="text-red-500">*</span></label>
                                <div class="flex justify-between items-center">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" wire:click="$set('{{ $field }}', {{ $i }})"
                                                class="flex flex-col items-center p-2 rounded-lg transition-all
                                                       {{ $this->$field == $i ? 'bg-blue-600 text-white scale-110' : 'bg-white text-gray-600 hover:bg-blue-100' }}">
                                            <span class="text-xl mb-1">{{ $i }}</span>
                                            <span class="text-xs">{{ $i === 1 ? 'Buruk' : ($i === 5 ? 'Sangat Baik' : '') }}</span>
                                        </button>
                                    @endfor
                                </div>
                                @error($field) <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- STEP 5: PORTAL EXPERIENCE --}}
                @if($currentStep === 5)
                    <div class="space-y-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">💻 Pengalaman Portal M2B</h2>
                        
                        <!-- Portal Usage -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" wire:model.live="portal_used" class="w-5 h-5 text-blue-600">
                                <span class="text-gray-700 font-medium">Saya pernah menggunakan Portal M2B (portal.m2b.co.id)</span>
                            </label>
                        </div>

                        @if($portal_used)
                            @php
                                $portalQuestions = [
                                    'portal_ease_of_use' => 'Kemudahan penggunaan sistem / portal M2B',
                                    'portal_info_clarity' => 'Kejelasan informasi yang ditampilkan di sistem',
                                    'portal_usefulness' => 'Manfaat sistem M2B dalam monitoring shipment'
                                ];
                            @endphp

                            @foreach($portalQuestions as $field => $question)
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <label class="block text-gray-700 font-medium mb-3">{{ $question }} <span class="text-red-500">*</span></label>
                                    <div class="flex justify-between items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            <button type="button" wire:click="$set('{{ $field }}', {{ $i }})"
                                                    class="flex flex-col items-center p-2 rounded-lg transition-all
                                                           {{ $this->$field == $i ? 'bg-blue-600 text-white scale-110' : 'bg-white text-gray-600 hover:bg-blue-100' }}">
                                                <span class="text-xl mb-1">{{ $i }}</span>
                                                <span class="text-xs">{{ $i === 1 ? 'Buruk' : ($i === 5 ? 'Sangat Baik' : '') }}</span>
                                            </button>
                                        @endfor
                                    </div>
                                    @error($field) <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <p>Anda belum pernah menggunakan Portal M2B? Tidak masalah!</p>
                                <p class="text-sm mt-2">Klik "Lanjut" untuk melanjutkan ke step berikutnya</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- STEP 6: LOYALTY & FEEDBACK --}}
                @if($currentStep === 6)
                    <div class="space-y-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">🎯 Loyalitas & Saran</h2>
                        
                        <!-- Likelihood to Reuse -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-3">
                                Seberapa besar kemungkinan Anda menggunakan kembali jasa M2B? <span class="text-red-500">*</span>
                            </label>
                            <div class="flex justify-center space-x-4">
                                @for($i = 1; $i <= 5; $i++)
                                    <button type="button" wire:click="$set('likelihood_reuse', {{ $i }})"
                                            class="w-16 h-16 rounded-full border-2 transition-all
                                                   {{ $likelihood_reuse == $i ? 'bg-green-600 text-white border-green-600 scale-110' : 'bg-white text-gray-600 border-gray-300 hover:border-green-400' }}">
                                        <div class="text-xl font-bold">{{ $i }}</div>
                                    </button>
                                @endfor
                            </div>
                            @error('likelihood_reuse') <span class="text-red-500 text-sm block text-center mt-2">{{ $message }}</span> @enderror
                        </div>

                        <!-- NPS Score -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-3">
                                Seberapa besar kemungkinan Anda merekomendasikan M2B ke rekan bisnis? <span class="text-red-500">*</span>
                            </label>
                            <p class="text-sm text-gray-600 mb-3">Skala 0-10 (0 = Tidak Mungkin, 10 = Sangat Mungkin)</p>
                            <div class="flex justify-between">
                                @for($i = 0; $i <= 10; $i++)
                                    <button type="button" wire:click="$set('nps_score', {{ $i }})"
                                            class="w-10 h-10 rounded-lg border transition-all
                                                   {{ $nps_score == $i ? 'bg-blue-600 text-white border-blue-600 scale-110' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400' }}">
                                        {{ $i }}
                                    </button>
                                @endfor
                            </div>
                            @error('nps_score') <span class="text-red-500 text-sm block text-center mt-2">{{ $message }}</span> @enderror
                        </div>

                        <!-- Open Feedback -->
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Hal apa yang paling Anda apresiasi dari M2B?</label>
                            <textarea wire:model="appreciate_most" rows="3" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                      placeholder="Tulis feedback positif Anda..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">{{ strlen($appreciate_most) }}/500 karakter</p>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Hal apa yang perlu diperbaiki M2B?</label>
                            <textarea wire:model="needs_improvement" rows="3" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                      placeholder="Saran perbaikan Anda..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">{{ strlen($needs_improvement) }}/500 karakter</p>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Layanan atau fitur apa yang Anda harapkan di tahun 2026?</label>
                            <textarea wire:model="future_features" rows="3" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                      placeholder="Usulan fitur baru..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">{{ strlen($future_features) }}/500 karakter</p>
                        </div>

                        <!-- Follow-up -->
                        <div class="bg-green-50 p-4 rounded-lg">
                            <label class="flex items-center space-x-3 cursor-pointer mb-3">
                                <input type="checkbox" wire:model.live="willing_to_contact" class="w-5 h-5 text-green-600">
                                <span class="text-gray-700 font-medium">Saya bersedia dihubungi untuk diskusi peningkatan layanan</span>
                            </label>

                            @if($willing_to_contact)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                                    <div>
                                        <input type="email" wire:model="contact_email" 
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                               placeholder="Email Anda">
                                        @error('contact_email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <input type="text" wire:model="contact_phone" 
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                               placeholder="No. WhatsApp (Opsional)">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Navigation Buttons -->
                <div class="flex justify-between mt-8 pt-6 border-t">
                    @if($currentStep > 1)
                        <button type="button" wire:click="previousStep" 
                                class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            ← Kembali
                        </button>
                    @else
                        <div></div>
                    @endif

                    @if($currentStep < $totalSteps)
                        <button type="button" wire:click="nextStep" 
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Lanjut →
                        </button>
                    @else
                        <button type="submit" 
                                class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                            ✓ Kirim Survey
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Help Text -->
        <div class="text-center mt-6 text-sm text-gray-600">
            <p>Pertanyaan? Hubungi kami di <a href="mailto:sales@m2b.co.id" class="text-blue-600 hover:underline">sales@m2b.co.id</a></p>
        </div>
    </div>
</div>
