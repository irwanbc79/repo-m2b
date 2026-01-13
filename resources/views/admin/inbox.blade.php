{{-- resources/views/admin/inbox.blade.php --}}
@extends('admin.blade.php') {{-- file layout Anda yang sebelumnya: admin.blade.php --}}

@section('header')
    M2B Admin — Communication Center
@endsection

@section('content')
<div class="grid grid-cols-12 gap-6">
    {{-- LEFT: sidebar mailbox list --}}
    <div class="col-span-12 lg:col-span-3">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-bold text-lg mb-4">M2B Portal</h3>
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-3">Mailboxes</p>

            <div class="space-y-2">
                @foreach($mailboxes ?? ['sales'=>'Sales','import'=>'Import','export'=>'Export'] as $key => $label)
                    <button wire:click="selectMailbox('{{ $key }}')"
                            class="w-full text-left px-4 py-3 rounded {{ $mailbox === $key ? 'bg-teal-500 text-white' : 'bg-gray-900 text-gray-200' }}">
                        <div class="flex justify-between items-center">
                            <span>{{ $label }}</span>
                            <span class="text-xs bg-gray-800 rounded-full px-2 py-0.5">{{ $emails_count[$key] ?? 0 }}</span>
                        </div>
                    </button>
                @endforeach
            </div>

            <div class="mt-6">
                <input wire:model="search" class="w-full rounded border px-3 py-2 bg-gray-900 text-gray-100" placeholder="Search mail...">
            </div>

            <div class="mt-4">
                <button wire:click="refresh" class="w-full rounded bg-teal-600 text-white px-4 py-2">Refresh Inbox</button>
            </div>
        </div>
    </div>

    {{-- CENTER: list --}}
    <div class="col-span-12 lg:col-span-6">
        <div class="bg-white rounded-lg shadow p-6">
            @if($error)
                <div class="text-sm text-red-600 mb-4">{{ $error }}</div>
            @endif

            @if(empty($emails))
                <div class="text-gray-500">Inbox Kosong</div>
            @else
                <div class="space-y-4">
                    @foreach($emails as $mail)
                        <div wire:click="openEmail('{{ $mail['id'] }}')" style="cursor:pointer"
                             class="border rounded p-4 {{ $mail['seen'] ? 'bg-white' : 'bg-teal-50 border-teal-200' }}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-semibold">{{ $mail['subject'] }}</div>
                                    <div class="text-xs text-gray-400">{{ $mail['from'] }}</div>
                                </div>
                                <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($mail['date'] ?? now())->format('Y-m-d H:i') }}</div>
                            </div>
                            <div class="text-sm text-gray-500 mt-2">{!! Str::limit(strip_tags($mail['body'] ?? ''), 150) !!}</div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 text-center">
                    <button wire:click="loadMore" class="px-4 py-2 bg-white border rounded">Load more</button>
                </div>
            @endif
        </div>
    </div>

    {{-- RIGHT: preview --}}
    <div class="col-span-12 lg:col-span-3">
        <div class="bg-white rounded-lg shadow p-6 h-full">
            <h4 class="text-lg font-semibold mb-4">Preview</h4>

            @if($selectedEmail)
                <div class="text-sm text-gray-600 mb-3">
                    <div class="font-bold text-base">{{ $selectedEmail['subject'] }}</div>
                    <div class="text-xs text-gray-400">{{ $selectedEmail['from'] }} • {{ $selectedEmail['date'] }}</div>
                </div>

                <div class="text-sm text-gray-700 overflow-auto max-h-[60vh]">
                    {!! $selectedEmail['body'] !!}
                </div>

                @if(!empty($selectedEmail['attachments'] ?? []))
                    <div class="mt-4">
                        <h5 class="font-semibold">Attachments</h5>
                        <ul class="list-disc pl-5 text-sm">
                        @foreach($selectedEmail['attachments'] as $att)
                            <li><a href="{{ $att['url'] ?? '#' }}" target="_blank" class="text-indigo-600">{{ $att['name'] }}</a></li>
                        @endforeach
                        </ul>
                    </div>
                @endif
            @else
                <div class="text-gray-400">Pilih email untuk membaca detail</div>
            @endif
        </div>
    </div>
</div>
@endsection
