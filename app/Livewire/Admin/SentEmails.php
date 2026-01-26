<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SentEmail;

class SentEmails extends Component
{
    use WithPagination;

    public $activeMailbox = 'all';
    public $mailboxes = ['sales', 'import', 'export', 'finance', 'gmail', 'no_reply'];
    public $search = '';
    public $selectedEmail = null;

    public function switchMailbox($mailbox)
    {
        $this->activeMailbox = $mailbox;
        $this->resetPage();
    }

    public function viewEmail($id)
    {
        $this->selectedEmail = SentEmail::find($id);
    }

    public function closeDetail()
    {
        $this->selectedEmail = null;
    }

    public function deleteEmail($id)
    {
        SentEmail::destroy($id);
        $this->selectedEmail = null;
        session()->flash('message', 'Email berhasil dihapus');
    }

    public function render()
    {
        $query = SentEmail::query()->latest();

        if ($this->activeMailbox !== 'all') {
            $query->where('mailbox', $this->activeMailbox);
        }

        if ($this->search) {
            $s = $this->search;
            $query->where(function($q) use ($s) {
                $q->where('to_email', 'like', '%'.$s.'%')
                  ->orWhere('subject', 'like', '%'.$s.'%');
            });
        }

        return view('livewire.admin.sent-emails', [
            'emails' => $query->paginate(20)
        ])->layout('layouts.admin');
    }
}
