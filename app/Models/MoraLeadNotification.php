<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoraLeadNotification extends Model
{
    const SCORES = [
        'hot'  => '🔥 Hot',
        'warm' => '⚡ Warm',
        'cold' => 'Cold',
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

    const STAGES = [
        'new'         => 'Baru',
        'contacted'   => 'Dihubungi',
        'qualified'   => 'Qualified',
        'negotiating' => 'Negosiasi',
        'won'         => 'Won (Deal)',
        'lost'        => 'Lost (Gagal)',
    ];

    protected $fillable = [
        'remote_lead_id', 'name', 'company', 'phone', 'email',
        'score', 'source', 'service_interest', 'product_links', 'summary', 'chat_history', 'read_at',
        'status', 'assigned_to', 'follow_up_at', 'sales_notes', 'deal_value',
    ];

    protected $casts = [
        'chat_history' => 'array',
        'sales_notes'  => 'array',
        'read_at'      => 'datetime',
        'follow_up_at' => 'datetime',
        'deal_value'   => 'decimal:2',
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

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

    public function stageLabel(): string
    {
        return self::STAGES[$this->status] ?? $this->status;
    }

    public static function getWaTemplates(string $name, string $service = ''): array
    {
        $serviceName = $service ? $service : 'Layanan Logistik';
        return [
            'intro' => "Halo Kak {$name}, terima kasih telah menghubungi M2B Logistics. Saya dari Tim M2B Logistics, ada yang bisa kami bantu terkait kebutuhan logistiknya?",
            // Untuk lead dari pendaftaran portal (mereka mendaftar sendiri, bukan menghubungi kita)
            'portal' => "Halo Kak {$name}, terima kasih sudah mendaftar di Portal M2B Logistics. Saya dari Tim M2B Logistics. Boleh kami bantu kebutuhan impor / ekspor / pengiriman domestiknya? Boleh dibantu info rute asal-tujuan dan jenis barangnya ya.",
            'service' => "Halo Kak {$name}, saya melihat Kakak tertarik dengan layanan *{$serviceName}* di M2B Logistics. Boleh tahu detail rencana pengirimannya (seperti rute asal-tujuan dan jenis barang)?",
            'followup' => "Halo Kak {$name}, sekadar follow-up apakah penawaran harga kami kemarin sudah sempat dibaca? Jika ada yang perlu ditanyakan atau disesuaikan, silakan berkabar ya. Terima kasih!",
            'deal' => "Halo Kak {$name}, terima kasih atas kepercayaan memilih M2B Logistics. Kami sedang mempersiapkan instruksi kerja selanjutnya. Mohon ditunggu ya."
        ];
    }

    /**
     * Apakah lead punya nomor HP valid untuk di-follow-up via WhatsApp.
     * Signup portal (login Google) sering tanpa HP (phone = '-').
     */
    public function hasValidPhone(): bool
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $this->phone);
        return strlen($digits) >= 8;
    }
}
