@extends('layouts.admin')

@section('title', 'Survey Response Detail')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Breadcrumb --}}
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('admin.survey.dashboard') }}" class="inline-flex items-center text-sm text-gray-700 hover:text-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Survey Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="ml-1 text-sm text-gray-500 md:ml-2">Response #{{ $survey->id }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Response Overview Card --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b bg-gradient-to-r from-blue-600 to-blue-700">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold text-white">Survey Response Detail</h2>
                        <div class="flex items-center space-x-2">
                            @if($survey->is_flagged ?? false)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    🚩 Flagged
                                </span>
                            @endif
                            @php
                                $npsScore = $survey->nps_score ?? 0;
                                $npsLabel = 'Neutral';
                                $npsClass = 'bg-yellow-100 text-yellow-800';
                                if($npsScore >= 9) {
                                    $npsLabel = 'Promoter';
                                    $npsClass = 'bg-green-100 text-green-800';
                                } elseif($npsScore <= 6) {
                                    $npsLabel = 'Detractor';
                                    $npsClass = 'bg-red-100 text-red-800';
                                }
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $npsClass }}">
                                {{ $npsLabel }} ({{ $npsScore }}/10)
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    {{-- Respondent Info --}}
                    <div class="grid grid-cols-2 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                        <div>
                            <span class="text-sm text-gray-500">Nama Responden</span>
                            <p class="font-semibold text-gray-900">
                                {{ ($survey->is_anonymous ?? false) ? '🔒 Anonymous' : ($survey->respondent_name ?? '-') }}
                            </p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Perusahaan</span>
                            <p class="font-semibold text-gray-900">{{ $survey->company_name ?? '-' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Email</span>
                            <p class="font-semibold text-gray-900">
                                {{ ($survey->is_anonymous ?? false) ? '-' : ($survey->respondent_email ?? '-') }}
                            </p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Telepon</span>
                            <p class="font-semibold text-gray-900">
                                {{ ($survey->is_anonymous ?? false) ? '-' : ($survey->respondent_phone ?? '-') }}
                            </p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Tanggal Submit</span>
                            <p class="font-semibold text-gray-900">{{ $survey->created_at->format('d M Y, H:i') }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Periode Survey</span>
                            <p class="font-semibold text-gray-900">Q{{ $survey->survey_quarter ?? '-' }} {{ $survey->survey_year ?? '-' }}</p>
                        </div>
                    </div>

                    {{-- Rating Scores --}}
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Rating Scores</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @php
                                $ratings = [
                                    'service_quality' => 'Kualitas Layanan',
                                    'response_speed' => 'Kecepatan Respon',
                                    'communication' => 'Komunikasi',
                                    'pricing_fairness' => 'Kewajaran Harga',
                                    'document_accuracy' => 'Akurasi Dokumen',
                                    'problem_resolution' => 'Penyelesaian Masalah',
                                ];
                            @endphp

                            @foreach($ratings as $key => $label)
                                @php $rating = $survey->$key ?? null; @endphp
                                @if($rating)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <span class="text-gray-700">{{ $label }}</span>
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-5 h-5 {{ $i <= $rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                        @endfor
                                        <span class="ml-2 text-sm font-medium text-gray-600">({{ $rating }}/5)</span>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- NPS Score --}}
                    @if($survey->nps_score ?? null)
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">📈 Net Promoter Score (NPS)</h3>
                        <div class="p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-gray-700">Kemungkinan merekomendasikan M2B</span>
                                <span class="text-2xl font-bold {{ $survey->nps_score >= 9 ? 'text-green-600' : ($survey->nps_score <= 6 ? 'text-red-600' : 'text-yellow-600') }}">
                                    {{ $survey->nps_score }}/10
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="h-3 rounded-full {{ $survey->nps_score >= 9 ? 'bg-green-500' : ($survey->nps_score <= 6 ? 'bg-red-500' : 'bg-yellow-500') }}"
                                     style="width: {{ $survey->nps_score * 10 }}%"></div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Feedback Text --}}
                    @if(($survey->positive_feedback ?? null) || ($survey->improvement_suggestions ?? null) || ($survey->additional_comments ?? null))
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900">💬 Feedback & Komentar</h3>
                        
                        @if($survey->positive_feedback ?? null)
                        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                            <h4 class="font-medium text-green-800 mb-2">✅ Hal Positif</h4>
                            <p class="text-green-700">{{ $survey->positive_feedback }}</p>
                        </div>
                        @endif

                        @if($survey->improvement_suggestions ?? null)
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                            <h4 class="font-medium text-amber-800 mb-2">💡 Saran Perbaikan</h4>
                            <p class="text-amber-700">{{ $survey->improvement_suggestions }}</p>
                        </div>
                        @endif

                        @if($survey->additional_comments ?? null)
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <h4 class="font-medium text-blue-800 mb-2">📝 Komentar Tambahan</h4>
                            <p class="text-blue-700">{{ $survey->additional_comments }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Services Used --}}
                    @if($survey->services_used ?? null)
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">🚢 Layanan yang Digunakan</h3>
                        <div class="flex flex-wrap gap-2">
                            @php
                                $services = is_array($survey->services_used) ? $survey->services_used : json_decode($survey->services_used, true) ?? [];
                            @endphp
                            @foreach($services as $service)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    {{ $service }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions Card --}}
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">⚡ Actions</h3>
                
                <div class="space-y-3">
                    <form action="{{ route('admin.survey.toggle-flag', $survey->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center px-4 py-2 {{ ($survey->is_flagged ?? false) ? 'bg-gray-100 hover:bg-gray-200 text-gray-700' : 'bg-red-100 hover:bg-red-200 text-red-700' }} rounded-lg transition">
                            {{ ($survey->is_flagged ?? false) ? '✕ Remove Flag' : '🚩 Flag for Follow-up' }}
                        </button>
                    </form>

                    <form action="{{ route('admin.survey.delete', $survey->id) }}" method="POST" onsubmit="return confirm('Delete this response?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                            🗑️ Delete Response
                        </button>
                    </form>

                    <a href="{{ route('admin.survey.dashboard') }}" class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>

            {{-- Admin Notes --}}
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">📝 Admin Notes</h3>
                <form action="{{ route('admin.survey.update-notes', $survey->id) }}" method="POST">
                    @csrf
                    <textarea name="admin_notes" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Add notes...">{{ $survey->admin_notes ?? '' }}</textarea>
                    <button type="submit" class="mt-3 w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        💾 Save Notes
                    </button>
                </form>
            </div>

            {{-- Customer Info --}}
            @if($survey->customer ?? null)
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">👤 Linked Customer</h3>
                <div class="space-y-2 text-sm">
                    <p><span class="text-gray-500">Code:</span> <strong>{{ $survey->customer->customer_code ?? '-' }}</strong></p>
                    <p><span class="text-gray-500">Company:</span> <strong>{{ $survey->customer->company_name ?? '-' }}</strong></p>
                </div>
            </div>
            @endif

            {{-- Metadata --}}
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ℹ️ Metadata</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">ID</span><span class="font-mono">#{{ $survey->id }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Created</span><span>{{ $survey->created_at->format('d M Y, H:i') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">IP</span><span class="font-mono">{{ $survey->ip_address ?? 'N/A' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Anonymous</span><span>{{ ($survey->is_anonymous ?? false) ? 'Yes' : 'No' }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<script>
    alert('{{ session("success") }}');
</script>
@endif
@endsection
