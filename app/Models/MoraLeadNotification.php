<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoraLeadNotification extends Model
{
    const SCORES = [
        'hot'  => '🔥 Hot',
        'warm' => '⚡ Warm',
        'cold' => '📩 Cold',
    ];

    const SERVICES = [
        'export'       => 'Ekspor',
        'import'       => 'Impor',
        'customs'      => 'Customs Clearance',
        'undername'    => 'Undername Import',
        'door_to_door' => 'Door-to-Door',
        'consultation' => 'Konsultasi',
        'other'        => 'Lainnya',
    ];

    protected $fillable = [
        'remote_lead_id', 'name', 'company', 'phone', 'email',
        'score', 'source', 'service_interest', 'summary', 'chat_history', 'read_at',
    ];

    protected $casts = [
        'chat_history' => 'array',
        'read_at'      => 'datetime',
    ];

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function waUrl(): string
    {
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        $phone = preg_replace('/^0/', '62', $phone);
        return "https://wa.me/{$phone}";
    }

    public function scoreLabel(): string
    {
        return self::SCORES[$this->score] ?? $this->score;
    }

    public function serviceLabel(): ?string
    {
        return $this->service_interest ? (self::SERVICES[$this->service_interest] ?? $this->service_interest) : null;
    }
}
