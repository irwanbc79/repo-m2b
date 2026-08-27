<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Controllers\Admin\EmailAttachmentController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class EmailBodyCleaningTest extends TestCase
{
    use RefreshDatabase;

    public function test_clean_email_body_strips_mime_and_dkim_headers()
    {
        $rawEmail = "Delivered-To: finance@m2b.co.id\r\n"
            . "DKIM-Signature: v=1; a=rsa-sha256; d=m2b.co.id;\r\n"
            . "\th=Message-ID:Subject:To:From;\r\n"
            . "From: M2B Portal <no_reply@m2b.co.id>\r\n"
            . "To: finance@m2b.co.id\r\n"
            . "Subject: [Portal M2B] CROSS-CHECK: 1 transaksi belum terbukukan\r\n"
            . "Content-Type: text/plain; charset=utf-8\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . "PORTAL M2B =E2=80=94 CROSS-CHECK PEMBUKUAN\n"
            . "==========================================\n\n"
            . "Ditemukan 1 transaksi yang SUDAH tercatat.";

        $cleaned = EmailAttachmentController::cleanEmailBody($rawEmail);

        $this->assertStringNotContainsString('Delivered-To:', $cleaned);
        $this->assertStringNotContainsString('DKIM-Signature:', $cleaned);
        $this->assertStringNotContainsString('Content-Transfer-Encoding:', $cleaned);
        $this->assertStringContainsString('PORTAL M2B — CROSS-CHECK PEMBUKUAN', $cleaned);
        $this->assertStringContainsString('Ditemukan 1 transaksi yang SUDAH tercatat.', $cleaned);
    }

    public function test_show_body_endpoint_renders_cleaned_plain_text_with_modern_wrapper()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $emailId = DB::table('emails')->insertGetId([
            'mailbox' => 'finance',
            'uid' => 12345,
            'message_id' => '<test@m2b.co.id>',
            'subject' => '[Portal M2B] CROSS-CHECK: 1 transaksi belum terbukukan',
            'from_email' => 'no_reply@m2b.co.id',
            'from_name' => 'M2B Portal',
            'body' => "Delivered-To: finance@m2b.co.id\nDKIM-Signature: v=1;\n\nROBOT ACCOUNTANT M2B — BRIEFING HARIAN\nThursday, 27 August 2026\n==========================================\n\nKAS\nHari ini : masuk Rp 0",
            'is_read' => false,
            'email_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.inbox.body', $emailId));

        $response->assertOk();
        $response->assertDontSee('Delivered-To:');
        $response->assertDontSee('DKIM-Signature:');
        $response->assertSee('ROBOT ACCOUNTANT M2B — BRIEFING HARIAN');
        $response->assertSee('KAS');
    }
}
