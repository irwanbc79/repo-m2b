<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">📊 Survey Dashboard</h1>
        <p class="text-gray-600">Analisis Kepuasan Pelanggan M2B</p>
    </div>

    <!-- Filter Controls -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Year Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tahun</label>
                <select wire:model.live="selectedYear" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    @foreach($years as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Quarter Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quarter</label>
                <select wire:model.live="selectedQuarter" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Semua Quarter</option>
                    @foreach($quarters as $quarter)
                        <option value="{{ $quarter }}">{{ $quarter }}</option>
                    @endforeach
                </select>
            </div>

            <!-- View Mode -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">View</label>
                <select wire:model.live="viewMode" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="overview">Overview</option>
                    <option value="responses">Responses</option>
                    <option value="reports">Reports</option>
                </select>
            </div>

            <!-- Export -->
            <div class="flex items-end">
                <button wire:click="exportExcel" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    📥 Export Excel
                </button>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    {{-- OVERVIEW MODE --}}
    @if($viewMode === 'overview')
        <!-- Key Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <!-- Total Responses -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Responden</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $dashboardData['total_responses'] }}</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- NPS Score -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Net Promoter Score</p>
                        <p class="text-3xl font-bold {{ $dashboardData['nps']['nps_score'] >= 50 ? 'text-green-600' : ($dashboardData['nps']['nps_score'] >= 0 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $dashboardData['nps']['nps_score'] }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $dashboardData['nps']['nps_score'] >= 70 ? 'Excellent' : ($dashboardData['nps']['nps_score'] >= 50 ? 'Great' : ($dashboardData['nps']['nps_score'] >= 30 ? 'Good' : ($dashboardData['nps']['nps_score'] >= 0 ? 'Fair' : 'Poor'))) }}
                        </p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Avg Satisfaction -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Avg Satisfaction</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $dashboardData['satisfaction']['average'] }}/5</p>
                        <div class="flex mt-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= round($dashboardData['satisfaction']['average']) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            @endfor
                        </div>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Flagged -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Perlu Follow-up</p>
                        <p class="text-3xl font-bold text-red-600">{{ $dashboardData['flagged_count'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Feedback negatif</p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- NPS Breakdown -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">NPS Breakdown</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Promoters -->
                <div class="border-l-4 border-green-500 bg-green-50 p-4 rounded">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-semibold text-green-700">Promoters (9-10)</span>
                        <span class="text-2xl font-bold text-green-700">{{ $dashboardData['nps']['promoters_percent'] }}%</span>
                    </div>
                    <div class="w-full bg-green-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $dashboardData['nps']['promoters_percent'] }}%"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">{{ $dashboardData['nps']['promoters'] }} responden</p>
                </div>

                <!-- Passives -->
                <div class="border-l-4 border-yellow-500 bg-yellow-50 p-4 rounded">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-semibold text-yellow-700">Passives (7-8)</span>
                        <span class="text-2xl font-bold text-yellow-700">{{ $dashboardData['nps']['passives_percent'] }}%</span>
                    </div>
                    <div class="w-full bg-yellow-200 rounded-full h-2">
                        <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ $dashboardData['nps']['passives_percent'] }}%"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">{{ $dashboardData['nps']['passives'] }} responden</p>
                </div>

                <!-- Detractors -->
                <div class="border-l-4 border-red-500 bg-red-50 p-4 rounded">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-semibold text-red-700">Detractors (0-6)</span>
                        <span class="text-2xl font-bold text-red-700">{{ $dashboardData['nps']['detractors_percent'] }}%</span>
                    </div>
                    <div class="w-full bg-red-200 rounded-full h-2">
                        <div class="bg-red-600 h-2 rounded-full" style="width: {{ $dashboardData['nps']['detractors_percent'] }}%"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">{{ $dashboardData['nps']['detractors'] }} responden</p>
                </div>
            </div>
        </div>

        <!-- Category Performance -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Performance by Category -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Performance by Category</h2>
                
                @php
                    $categories = [
                        'operational' => ['name' => 'Operasional', 'icon' => '🚚', 'color' => 'blue'],
                        'communication' => ['name' => 'Komunikasi', 'icon' => '💬', 'color' => 'green'],
                        'pricing' => ['name' => 'Harga', 'icon' => '💰', 'color' => 'yellow'],
                        'portal' => ['name' => 'Portal Digital', 'icon' => '💻', 'color' => 'purple']
                    ];
                @endphp

                @foreach($categories as $key => $cat)
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-700 font-medium">{{ $cat['icon'] }} {{ $cat['name'] }}</span>
                            <span class="text-lg font-bold text-{{ $cat['color'] }}-600">{{ $dashboardData['categories'][$key] ?? 'N/A' }}/5</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-{{ $cat['color'] }}-600 h-3 rounded-full" style="width: {{ ($dashboardData['categories'][$key] ?? 0) * 20 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Top Strengths & Improvements -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Insights</h2>
                
                <!-- Top Strengths -->
                <div class="mb-4">
                    <h3 class="font-semibold text-green-700 mb-2">✅ Top 3 Strengths</h3>
                    <ul class="space-y-1">
                        @foreach($dashboardData['top_strengths'] as $strength => $score)
                            <li class="text-sm text-gray-700">
                                <span class="font-medium">{{ $strength }}</span>
                                <span class="text-green-600 float-right">{{ $score }}/5</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Areas for Improvement -->
                <div>
                    <h3 class="font-semibold text-red-700 mb-2">⚠️ Top 3 Areas to Improve</h3>
                    <ul class="space-y-1">
                        @foreach($dashboardData['improvements'] as $area => $score)
                            <li class="text-sm text-gray-700">
                                <span class="font-medium">{{ $area }}</span>
                                <span class="text-red-600 float-right">{{ $score }}/5</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- Service Usage -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Service Usage Distribution</h2>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                @foreach($dashboardData['service_usage'] as $service => $data)
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <p class="text-3xl font-bold text-blue-600">{{ $data['percent'] }}%</p>
                        <p class="text-sm text-gray-700 mt-2 capitalize">{{ str_replace('_', ' ', $service) }}</p>
                        <p class="text-xs text-gray-500">{{ $data['count'] }} responses</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- RESPONSES MODE --}}
    @if($viewMode === 'responses')
        <div class="bg-white rounded-lg shadow">
            <!-- Search & Filter -->
            <div class="p-4 border-b">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="text" wire:model.live.debounce.300ms="search" 
                           placeholder="Cari nama perusahaan atau feedback..."
                           class="px-4 py-2 border border-gray-300 rounded-lg">
                    
                    <select wire:model.live="filterNpsCategory" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Semua NPS Category</option>
                        <option value="promoters">Promoters (9-10)</option>
                        <option value="passives">Passives (7-8)</option>
                        <option value="detractors">Detractors (0-6)</option>
                    </select>
                </div>
            </div>

            <!-- Responses Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perusahaan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Satisfaction</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NPS</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($responses as $response)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $response->response_date->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium text-gray-800">{{ $response->display_name }}</div>
                                    <div class="text-xs text-gray-500">{{ ucfirst($response->respondent_position) }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center">
                                        <span class="text-lg font-bold mr-2">{{ $response->overall_satisfaction }}</span>
                                        <span class="text-xs text-gray-500">/5</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $response->nps_category === 'Promoter' ? 'bg-green-100 text-green-800' : 
                                           ($response->nps_category === 'Passive' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $response->nps_score }} - {{ $response->nps_category }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($response->is_flagged)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            🚩 Flagged
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex space-x-2">
                                        <button wire:click="flagResponse({{ $response->id }})" 
                                                class="text-yellow-600 hover:text-yellow-800" title="Flag/Unflag">
                                            🏴
                                        </button>
                                        <a href="{{ route('admin.survey.view', $response->id) }}" 
                                           class="text-blue-600 hover:text-blue-800" title="View Details">
                                            👁️
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    Tidak ada data survey.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t">
                {{ $responses->links() }}
            </div>
        </div>
    @endif

    {{-- REPORTS MODE --}}
    @if($viewMode === 'reports')
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">📑 Export & Reports</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button wire:click="exportExcel" class="p-6 border-2 border-dashed border-gray-300 rounded-lg hover:border-green-500 hover:bg-green-50 transition">
                    <div class="text-4xl mb-2">📊</div>
                    <div class="font-semibold text-gray-800">Export to Excel</div>
                    <div class="text-sm text-gray-600">Raw data & analytics</div>
                </button>

                <button class="p-6 border-2 border-dashed border-gray-300 rounded-lg hover:border-red-500 hover:bg-red-50 transition">
                    <div class="text-4xl mb-2">📄</div>
                    <div class="font-semibold text-gray-800">Export to PDF</div>
                    <div class="text-sm text-gray-600">Executive summary</div>
                </button>

                <button class="p-6 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition">
                    <div class="text-4xl mb-2">📧</div>
                    <div class="font-semibold text-gray-800">Email Report</div>
                    <div class="text-sm text-gray-600">Schedule monthly report</div>
                </button>
            </div>
        </div>
    @endif
</div>
