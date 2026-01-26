<div class="p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">📤 Email Terkirim</h1>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-12 gap-4">
        <!-- Sidebar Mailbox -->
        <div class="col-span-2">
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="font-semibold text-gray-700 mb-3 text-sm">MAILBOXES</h3>
                <div class="space-y-1">
                    <button wire:click="switchMailbox('all')" class="w-full text-left px-3 py-2 rounded text-sm {{ $activeMailbox === 'all' ? 'bg-teal-600 text-white' : 'hover:bg-gray-100' }}">
                        📁 Semua
                    </button>
                    @foreach($mailboxes as $box)
                    <button wire:click="switchMailbox('{{ $box }}')" class="w-full text-left px-3 py-2 rounded text-sm {{ $activeMailbox === $box ? 'bg-teal-600 text-white' : 'hover:bg-gray-100' }}">
                        📁 {{ ucfirst($box) }}
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Email List -->
        <div class="col-span-{{ $selectedEmail ? '4' : '10' }}">
            <div class="bg-white rounded-lg shadow">
                <div class="p-3 border-b">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari email..." class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div class="divide-y" style="max-height: 500px; overflow-y: auto;">
                    @forelse($emails as $email)
                    <div wire:click="viewEmail({{ $email->id }})" class="p-3 hover:bg-gray-50 cursor-pointer {{ $selectedEmail && $selectedEmail->id === $email->id ? 'bg-teal-50 border-l-4 border-teal-500' : '' }}">
                        <div class="flex justify-between items-start">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 text-xs rounded bg-gray-200 text-gray-700">{{ $email->mailbox }}</span>
                                    <span class="font-medium text-gray-800 text-sm truncate">{{ $email->to_email }}</span>
                                </div>
                                <p class="text-sm text-gray-600 truncate">{{ $email->subject }}</p>
                                <p class="text-xs text-gray-400 mt-1">oleh: {{ $email->user_name }}</p>
                            </div>
                            <span class="text-xs text-gray-500 whitespace-nowrap ml-2">{{ $email->created_at->format('d M H:i') }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="p-8 text-center text-gray-500">Belum ada email terkirim</div>
                    @endforelse
                </div>
                <div class="p-3 border-t">{{ $emails->links() }}</div>
            </div>
        </div>

        <!-- Email Detail -->
        @if($selectedEmail)
        <div class="col-span-6">
            <div class="bg-white rounded-lg shadow">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="font-bold text-gray-800">Detail Email</h3>
                    <button wire:click="closeDetail" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                </div>
                <div class="p-4">
                    <table class="w-full text-sm mb-4">
                        <tr class="border-b">
                            <td class="py-2 text-gray-500 w-24">Mailbox</td>
                            <td class="py-2"><span class="px-2 py-1 bg-teal-100 text-teal-800 rounded text-xs">{{ $selectedEmail->mailbox }}</span></td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 text-gray-500">Kepada</td>
                            <td class="py-2 font-medium">{{ $selectedEmail->to_email }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 text-gray-500">Subject</td>
                            <td class="py-2">{{ $selectedEmail->subject }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 text-gray-500">Waktu</td>
                            <td class="py-2">{{ $selectedEmail->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-500">Oleh</td>
                            <td class="py-2">{{ $selectedEmail->user_name }}</td>
                        </tr>
                    </table>
                    
                    <div class="border-t pt-4">
                        <p class="text-gray-500 text-sm mb-2">Isi Pesan:</p>
                        <div class="p-4 bg-gray-50 rounded-lg text-sm text-gray-700 whitespace-pre-wrap" style="max-height: 250px; overflow-y: auto;">{{ $selectedEmail->body }}</div>
                    </div>
                </div>
                <div class="p-4 border-t">
                    <button wire:click="deleteEmail({{ $selectedEmail->id }})" wire:confirm="Yakin hapus email ini?" class="w-full px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 text-sm">
                        🗑️ Hapus Email
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
